<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Setting;
use App\Services\LiquidsoapService;
use App\Services\IcecastService;

class SettingController extends BaseController
{
    public function index(): void
    {
        $settings = Setting::getAll();

        $icecast = new IcecastService();
        $icecastStatus = $icecast->getStatus();

        $this->view('admin.settings.index', [
            'settings' => $settings,
            'icecastStatus' => $icecastStatus,
        ]);
    }

    public function update(): void
    {
        $keys = [
            'site_title', 'public_tagline', 'contact_email', 'contact_whatsapp',
            'facebook_url', 'instagram_url', 'youtube_url', 'twitter_url',
            'auto_dj_crossfade_duration', 'jingle_frequency'
        ];

        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                Setting::set($k, trim((string) $_POST[$k]));
            }
        }

        log_activity('update_settings', ['keys' => $keys]);

        $this->redirect('admin/settings', ['success' => 'System settings saved successfully!']);
    }

    public function reloadLiquidsoap(): void
    {
        $liq = new LiquidsoapService();
        $res = $liq->reloadService();

        log_activity('reload_liquidsoap', $res);

        $this->redirect('admin/settings', ['success' => 'Liquidsoap configuration recompiled and service reloaded!']);
    }
}
