<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Song;
use App\Models\Playlist;
use App\Models\Station;
use App\Models\Jingle;
use App\Helpers\Logger;

class LiquidsoapService
{
    private string $host;
    private int $telnetPort;
    private string $configPath;
    private string $playlistsDir;

    public function __construct()
    {
        $this->host = config('radio.liquidsoap.host', '127.0.0.1');
        $this->telnetPort = (int) config('radio.liquidsoap.telnet_port', 1234);
        $this->configPath = config('radio.paths.generated_config', '/etc/radio/radio.liq');
        $this->playlistsDir = config('radio.paths.playlists_dir', '/var/lib/radio/playlists');
    }

    /**
     * Send command to Liquidsoap Telnet interface
     */
    public function sendTelnet(string $command): string
    {
        $fp = @fsockopen($this->host, $this->telnetPort, $errno, $errstr, 2);
        if (!$fp) {
            Logger::warning("Could not connect to Liquidsoap telnet on {$this->host}:{$this->telnetPort} ($errstr)");
            return "ERROR: Connection refused ({$errstr})";
        }

        stream_set_timeout($fp, 3);
        fwrite($fp, $command . "\n");

        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            if ($line === false) break;
            $response .= $line;
            if (trim($line) === 'END') break;
        }

        fwrite($fp, "quit\n");
        fclose($fp);

        return trim($response);
    }

    /**
     * Skip currently playing track
     */
    public function skipTrack(): bool
    {
        $res = $this->sendTelnet('autodj.skip');
        if (str_contains($res, 'ERROR')) {
            // Also try fallback output skip
            $res = $this->sendTelnet('output_icecast.skip');
        }
        return !str_contains($res, 'ERROR');
    }

    /**
     * Export all active playlists to .m3u files on filesystem
     */
    public function syncPlaylistFiles(): array
    {
        if (!is_dir($this->playlistsDir)) {
            @mkdir($this->playlistsDir, 0755, true);
        }

        $written = [];

        // 1. All active songs fallback playlist
        $allSongs = Song::find([
            '$or' => [
                ['enabled' => true],
                ['enabled' => 1],
                ['enabled' => '1'],
                ['enabled' => ['$exists' => false]],
            ]
        ]);
        $defaultM3u = $this->playlistsDir . '/default.m3u';
        $lines = [];
        foreach ($allSongs as $song) {
            if (!empty($song['filepath']) && file_exists($song['filepath'])) {
                $lines[] = $song['filepath'];
            }
        }

        // Direct storage folder scan: Ensure any uploaded audio in music dir is included
        $musicDir = config('radio.paths.music', '/var/lib/radio/music');
        if (is_dir($musicDir)) {
            $scanned = glob($musicDir . '/*.{mp3,wav,ogg,flac,m4a,aac}', GLOB_BRACE) ?: [];
            foreach ($scanned as $f) {
                if (file_exists($f)) {
                    $lines[] = $f;
                }
            }
        }

        $lines = array_values(array_unique(array_filter($lines)));

        // If no music exists, use fallback safety audio
        if (empty($lines)) {
            $fallback = config('radio.paths.fallback', '/var/lib/radio/fallback/default.mp3');
            $lines[] = $fallback;
        }

        @file_put_contents($defaultM3u, implode("\n", $lines) . "\n");
        @chmod($defaultM3u, 0664);
        $written['default'] = $defaultM3u;

        // 2. Export named active playlists (Export both safe-name and Mongo ObjectId formats)
        $playlists = Playlist::find(['status' => 'active']);
        foreach ($playlists as $pl) {
            $plSongs = Playlist::getSongs((string) $pl['_id']);
            $plLines = [];
            foreach ($plSongs as $s) {
                if (!empty($s['filepath']) && file_exists($s['filepath'])) {
                    $plLines[] = $s['filepath'];
                }
            }

            if (!empty($plLines)) {
                $content = implode("\n", $plLines) . "\n";
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $pl['name']);
                $plFile = $this->playlistsDir . "/playlist_{$safeName}.m3u";
                $plIdFile = $this->playlistsDir . "/playlist_" . ((string) $pl['_id']) . ".m3u";

                @file_put_contents($plFile, $content);
                @file_put_contents($plIdFile, $content);

                $written[(string) $pl['_id']] = $plFile;
                $written[(string) $pl['_id'] . '_id'] = $plIdFile;
            }
        }

        // 3. Export Jingles playlist
        $jingles = Jingle::find(['enabled' => true]);
        $jingleLines = [];
        foreach ($jingles as $j) {
            if (!empty($j['filepath']) && file_exists($j['filepath'])) {
                $jingleLines[] = $j['filepath'];
            }
        }
        $jingleFile = $this->playlistsDir . '/jingles.m3u';
        @file_put_contents($jingleFile, implode("\n", $jingleLines) . "\n");
        $written['jingles'] = $jingleFile;

        return $written;
    }

    /**
     * Generate complete production Liquidsoap 2.x script (radio.liq)
     */
    public function generateConfig(): string
    {
        $station = Station::getPrimary();
        $stream = $station['stream'] ?? [];

        $mount = $stream['mountpoint'] ?? config('radio.icecast.mountpoint', '/live');
        $bitrate = (int) ($stream['bitrate'] ?? config('radio.stream.bitrate', 128));
        $format = $stream['format'] ?? config('radio.stream.format', 'mp3');

        $icecastHost = config('radio.icecast.host', '127.0.0.1');
        $icecastPort = (int) config('radio.icecast.port', 8000);
        $sourcePassword = config('radio.icecast.source_password', 'hackme_source');

        $harborPort = (int) config('radio.liquidsoap.harbor_port', 8005);
        $harborMount = config('radio.liquidsoap.harbor_mount', '/live-dj');
        $harborUser = config('radio.liquidsoap.harbor_user', 'source');
        $harborPass = config('radio.liquidsoap.harbor_password', 'dj_live_harbor_pass');

        $telnetPort = $this->telnetPort;
        $playlistsDir = $this->playlistsDir;
        $fallbackAudio = config('radio.paths.fallback', '/var/lib/radio/fallback/default.mp3');

        $apiUrl = rtrim(config('app.url', 'http://127.0.0.1:8080'), '/');

        // Escaper helper for safe string interpolation into Liquidsoap 2.x syntax
        $liqEsc = static function ($val): string {
            return addcslashes((string) ($val ?? ''), "\"\\");
        };

        $mountEsc = $liqEsc($mount);
        $icecastHostEsc = $liqEsc($icecastHost);
        $sourcePasswordEsc = $liqEsc($sourcePassword);
        $harborMountEsc = $liqEsc($harborMount);
        $harborUserEsc = $liqEsc($harborUser);
        $harborPassEsc = $liqEsc($harborPass);
        $playlistsDirEsc = $liqEsc($playlistsDir);
        $fallbackAudioEsc = $liqEsc($fallbackAudio);
        $stationNameEsc = $liqEsc($station['name'] ?? 'Radio Agro');
        $stationDescEsc = $liqEsc($station['description'] ?? 'Radio Komunitas Agro');
        $stationGenreEsc = $liqEsc($station['genre'] ?? 'Various');
        $apiUrlEsc = $liqEsc($apiUrl);

        // Build Liquidsoap Script (Compatible with Liquidsoap 2.2.x on Ubuntu 24.04 LTS)
        $liq = <<<LIQ
#!/usr/bin/liquidsoap

# ==============================================================================
# RADIO PLATFORM - AUTOMATED LIQUIDSOAP CONFIGURATION
# Generated dynamically by PHP Management Service (Ubuntu 24.04 LTS / Liquidsoap 2.2.x)
# ==============================================================================

# 1. Global Server Settings
settings.log.file.path.set("/var/log/radio/liquidsoap.log")
settings.log.level.set(3)
settings.server.telnet.set(true)
settings.server.telnet.bind_addr.set("127.0.0.1")
settings.server.telnet.port.set({$telnetPort})

# 2. Audio Processing Parameters
settings.frame.audio.samplerate.set(44100)
settings.frame.audio.channels.set(2)

# 3. Notification Callbacks
def notify_metadata(m) =
  artist = m["artist"]
  title = m["title"]
  t_artist = if artist != "" then artist else "Unknown Artist" end
  t_title = if title != "" then title else "Unknown Title" end
  print("TRACK TRANSITION: #{t_artist} - #{t_title}")
  # Notify local web API in background
  ignore(http.get("{$apiUrlEsc}/api/internal/liquidsoap/on-track?artist=" ^ url.encode(t_artist) ^ "&title=" ^ url.encode(t_title)))
end

def notify_live_connect(m) =
  print("LIVE DJ CONNECTED")
  ignore(http.get("{$apiUrlEsc}/api/internal/liquidsoap/on-live-connect"))
end

def notify_live_disconnect() =
  print("LIVE DJ DISCONNECTED")
  ignore(http.get("{$apiUrlEsc}/api/internal/liquidsoap/on-live-disconnect"))
end

# 4. Emergency Fallback Source (Plays safety file or tone if all sources fail)
security_file = single(id="security_single", "{$fallbackAudioEsc}")
security_tone = sine(id="emergency_sine", 440.0)
emergency_source = fallback(track_sensitive=false, [security_file, security_tone])

# 5. Auto DJ Music Playlist Sources
autodj_playlist = playlist(
  id="autodj",
  mode="randomize",
  reload_mode="watch",
  "{$playlistsDirEsc}/default.m3u"
)

# Optional Jingle Rotation (1 jingle every 4 tracks if jingles.m3u has content)
jingles_playlist = playlist(
  id="jingles",
  mode="randomize",
  reload_mode="watch",
  "{$playlistsDirEsc}/jingles.m3u"
)

autodj_mixed = rotate(weights=[1, 4], [jingles_playlist, autodj_playlist])
autodj_source = fallback(track_sensitive=false, [autodj_mixed, autodj_playlist, emergency_source])

# 6. Live DJ Harbor Source (Accepts Icecast / Shoutcast connections from Mixxx, BUTT, OBS)
live_harbor = input.harbor(
  id="live_dj",
  "{$harborMountEsc}",
  port={$harborPort},
  auth=fun(args) -> ((args.user == "{$harborUserEsc}" or args.user == "source" or args.user == "") and args.password == "{$harborPassEsc}"),
  on_connect=notify_live_connect,
  on_disconnect=notify_live_disconnect
)

# 7. Priority Fallback & Smart Crossfade Switching
# Live DJ has highest priority (1). When DJ disconnects, Auto DJ smoothly resumes.
def transition_fade(a, b) =
  add(normalize=false, [fade.initial(duration=2.0, b), fade.final(duration=2.0, a)])
end

radio_stream = fallback(
  id="main_switch",
  track_sensitive=false,
  transitions=[transition_fade, transition_fade],
  [live_harbor, autodj_source, emergency_source]
)

# Apply metadata monitoring hook
radio_stream = on_metadata(notify_metadata, radio_stream)

# Apply Crossfade for AutoDJ tracks
radio_stream = crossfade(
  duration=3.0,
  fade_in=2.0,
  fade_out=2.0,
  radio_stream
)

# 8. Output to Icecast2 Server (mksafe guarantees infallible output stream)
output.icecast(
  %mp3(bitrate={$bitrate}),
  id="output_icecast",
  host="{$icecastHostEsc}",
  port={$icecastPort},
  password="{$sourcePasswordEsc}",
  mount="{$mountEsc}",
  name="{$stationNameEsc}",
  description="{$stationDescEsc}",
  genre="{$stationGenreEsc}",
  url="{$apiUrlEsc}",
  public=false,
  mksafe(radio_stream)
)
LIQ;

        // Ensure target directory exists and save
        $dir = dirname($this->configPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($this->configPath, $liq);
        @chmod($this->configPath, 0755);

        return $liq;
    }

    /**
     * Reload Liquidsoap daemon safely without stopping playback longer than needed
     */
    public function reloadService(): array
    {
        $this->syncPlaylistFiles();
        $this->generateConfig();

        // 1. Instant telnet commands without needing sudo privileges
        $telnetReload = $this->sendTelnet('autodj.reload');
        $telnetSkip = $this->sendTelnet('autodj.skip');

        // 2. Also attempt systemctl if permitted
        $output = @shell_exec('sudo systemctl reload radio-liquidsoap 2>&1');
        if (!$output || str_contains($output, 'Failed')) {
            $output = @shell_exec('sudo systemctl restart radio-liquidsoap 2>&1');
        }

        return [
            'success' => true,
            'telnet_reload' => $telnetReload,
            'telnet_skip' => $telnetSkip,
            'output' => trim($output ?? 'Liquidsoap configuration generated'),
        ];
    }
}
