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
        $allSongs = Song::find(['enabled' => true]);
        $defaultM3u = $this->playlistsDir . '/default.m3u';
        $lines = [];
        foreach ($allSongs as $song) {
            if (!empty($song['filepath']) && file_exists($song['filepath'])) {
                $lines[] = $song['filepath'];
            }
        }

        // If no music exists, use fallback file
        if (empty($lines)) {
            $fallback = config('radio.paths.fallback', '/var/lib/radio/fallback/default.mp3');
            $lines[] = $fallback;
        }

        @file_put_contents($defaultM3u, implode("\n", $lines) . "\n");
        $written['default'] = $defaultM3u;

        // 2. Export named active playlists
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
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $pl['name']);
                $plFile = $this->playlistsDir . "/playlist_{$safeName}.m3u";
                @file_put_contents($plFile, implode("\n", $plLines) . "\n");
                $written[(string) $pl['_id']] = $plFile;
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
  ignore(http.get("{$apiUrl}/api/internal/liquidsoap/on-track?artist=" ^ url.encode(t_artist) ^ "&title=" ^ url.encode(t_title)))
end

def notify_live_connect(m) =
  print("LIVE DJ CONNECTED")
  ignore(http.get("{$apiUrl}/api/internal/liquidsoap/on-live-connect"))
end

def notify_live_disconnect() =
  print("LIVE DJ DISCONNECTED")
  ignore(http.get("{$apiUrl}/api/internal/liquidsoap/on-live-disconnect"))
end

# 4. Emergency Fallback Source (Plays safety file or tone if all sources fail)
security_file = single(id="security_single", "{$fallbackAudio}")
security_tone = sine(id="emergency_sine", 440.0)
emergency_source = fallback(track_sensitive=false, [security_file, security_tone])

# 5. Auto DJ Music Playlist Sources
autodj_playlist = playlist(
  id="autodj",
  mode="randomize",
  reload_mode="watch",
  "{$playlistsDir}/default.m3u"
)

# Optional Jingle Rotation (1 jingle every 4 tracks if jingles.m3u has content)
jingles_playlist = playlist(
  id="jingles",
  mode="randomize",
  reload_mode="watch",
  "{$playlistsDir}/jingles.m3u"
)

autodj_mixed = rotate(weights=[1, 4], [jingles_playlist, autodj_playlist])
autodj_source = fallback(track_sensitive=false, [autodj_mixed, autodj_playlist, emergency_source])

# 6. Live DJ Harbor Source (Accepts Icecast / Shoutcast connections from Mixxx, BUTT, OBS)
live_harbor = input.harbor(
  id="live_dj",
  "{$harborMount}",
  port={$harborPort},
  auth=fun(user, pass) -> (user == "{$harborUser}" and pass == "{$harborPass}"),
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
  host="{$icecastHost}",
  port={$icecastPort},
  password="{$sourcePassword}",
  mount="{$mount}",
  name="{$station['name']}",
  description="{$station['description']}",
  genre="{$station['genre']}",
  url="{$apiUrl}",
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

        // Check if systemctl is available
        $output = @shell_exec('sudo systemctl reload radio-liquidsoap 2>&1');
        if (!$output || str_contains($output, 'Failed')) {
            // Try restart if reload is not supported
            $output = @shell_exec('sudo systemctl restart radio-liquidsoap 2>&1');
        }

        return [
            'success' => true,
            'output' => trim($output ?? 'Liquidsoap configuration generated'),
        ];
    }
}
