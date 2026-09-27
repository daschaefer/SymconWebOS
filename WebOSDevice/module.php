<?php

class WebOSDevice extends IPSModule
{
    private $sock = null;
    private $connected = false;
    private $registered = false;
    private $msgCounter = 0;

    // Signed registration manifest (LG test certificate). Accepted by webOS up to 25,
    // rejected by webOS 26+ ("blacklisted certificate") -> unsigned fallback, see buildUnsignedHandshake().
    const SIGNED_HANDSHAKE = '{"type":"register","id":"register_0","payload":{"forcePairing":false,"pairingType":"PROMPT","manifest":{"manifestVersion":1,"appVersion":"1.1","signed":{"created":"20140509","appId":"com.lge.test","vendorId":"com.lge","localizedAppNames":{"":"LG Remote App","ko-KR":"ë¦¬ëª¨ì»¨ ì•±","zxx-XX":"Ð›Ð“ RÑ�Ð¼otÑ� AÐŸÐŸ"},"localizedVendorNames":{"":"LG Electronics"},"permissions":["TEST_SECURE","CONTROL_INPUT_TEXT","CONTROL_MOUSE_AND_KEYBOARD","READ_INSTALLED_APPS","READ_LGE_SDX","READ_NOTIFICATIONS","SEARCH","WRITE_SETTINGS","WRITE_NOTIFICATION_ALERT","CONTROL_POWER","READ_CURRENT_CHANNEL","READ_RUNNING_APPS","READ_UPDATE_INFO","UPDATE_FROM_REMOTE_APP","READ_LGE_TV_INPUT_EVENTS","READ_TV_CURRENT_TIME"],"serial":"2f930e2d2cfe083771f68e4fe7bb07"},"permissions":["LAUNCH","LAUNCH_WEBAPP","APP_TO_APP","CLOSE","TEST_OPEN","TEST_PROTECTED","CONTROL_AUDIO","CONTROL_DISPLAY","CONTROL_INPUT_JOYSTICK","CONTROL_INPUT_MEDIA_RECORDING","CONTROL_INPUT_MEDIA_PLAYBACK","CONTROL_INPUT_TV","CONTROL_POWER","READ_APP_STATUS","READ_CURRENT_CHANNEL","READ_INPUT_DEVICE_LIST","READ_NETWORK_STATE","READ_RUNNING_APPS","READ_TV_CHANNEL_LIST","WRITE_NOTIFICATION_TOAST","READ_POWER_STATE","READ_COUNTRY_INFO"],"signatures":[{"signatureVersion":1,"signature":"eyJhbGdvcml0aG0iOiJSU0EtU0hBMjU2Iiwia2V5SWQiOiJ0ZXN0LXNpZ25pbmctY2VydCIsInNpZ25hdHVyZVZlcnNpb24iOjF9.hrVRgjCwXVvE2OOSpDZ58hR+59aFNwYDyjQgKk3auukd7pcegmE2CzPCa0bJ0ZsRAcKkCTJrWo5iDzNhMBWRyaMOv5zWSrthlf7G128qvIlpMT0YNY+n/FaOHE73uLrS/g7swl3/qH/BGFG2Hu4RlL48eb3lLKqTt2xKHdCs6Cd4RMfJPYnzgvI4BNrFUKsjkcu+WD4OO2A27Pq1n50cMchmcaXadJhGrOqH5YmHdOCj5NSHzJYrsW0HPlpuAx/ECMeIZYDh6RMqaFM2DXzdKX9NmmyqzJ3o/0lkk/N97gfVRLW5hA29yeAwaCViZNCP8iC9aO0q9fQojoa7NQnAtw=="}]}}}';

    // Remote buttons for the pointer input socket: value => [button name, caption]
    const REMOTE_BUTTONS = [
        0  => ['UP', 'Hoch'],
        1  => ['DOWN', 'Runter'],
        2  => ['LEFT', 'Links'],
        3  => ['RIGHT', 'Rechts'],
        4  => ['ENTER', 'OK'],
        5  => ['BACK', 'Zurück'],
        6  => ['EXIT', 'Exit'],
        7  => ['HOME', 'Home'],
        8  => ['MENU', 'Einstellungen'],
        9  => ['INFO', 'Info'],
        10 => ['RED', 'Rot'],
        11 => ['GREEN', 'Grün'],
        12 => ['YELLOW', 'Gelb'],
        13 => ['BLUE', 'Blau']
    ];

    // Media controls: value => [ssap command, caption]
    const MEDIA_CONTROLS = [
        0 => ['play', 'Play'],
        1 => ['pause', 'Pause'],
        2 => ['stop', 'Stop'],
        3 => ['rewind', 'Zurückspulen'],
        4 => ['fastForward', 'Vorspulen']
    ];

    // Sound outputs: value => [webOS id, caption]
    const SOUND_OUTPUTS = [
        0 => ['tv_speaker', 'TV-Lautsprecher'],
        1 => ['external_arc', 'HDMI ARC / eARC'],
        2 => ['external_optical', 'Optisch'],
        3 => ['bt_soundbar', 'Bluetooth'],
        4 => ['headphone', 'Kopfhörer'],
        5 => ['tv_external_speaker', 'TV + Extern']
    ];

    // PUBLIC ACCESSIBLE FUNCTIONS

    public function __destruct()
    {
        $this->disconnect();
    }

    public function Create()
    {
        parent::Create();

        // Public properties
        $this->RegisterPropertyString("DEVICE_IP", "");
        $this->RegisterPropertyString("DEVICE_MAC", "");
        $this->RegisterPropertyInteger("DEVICE_PORT", 3001);
        $this->RegisterPropertyString("DEVICE_CODE", "");
        $this->RegisterPropertyString("DEVICE_WSKEY", base64_encode($this->generateRandomString(16, false, true))); // unused, kept for compatibility

        $this->RegisterPropertyInteger("DEVICE_SOCKET", 0); // unused, kept for compatibility

        // Status polling interval in seconds (0 = off)
        $this->RegisterPropertyInteger("UPDATE_INTERVAL", 10);

        // Which status/action variables should be created
        $this->RegisterPropertyBoolean("VAR_POWER", true);
        $this->RegisterPropertyBoolean("VAR_VOLUME", true);
        $this->RegisterPropertyBoolean("VAR_INPUT", true);
        $this->RegisterPropertyBoolean("VAR_APP", true);
        $this->RegisterPropertyBoolean("VAR_APPLAUNCH", false);
        $this->RegisterPropertyBoolean("VAR_REMOTE", false);
        $this->RegisterPropertyBoolean("VAR_MEDIA", false);
        $this->RegisterPropertyBoolean("VAR_CHANNEL", false);
        $this->RegisterPropertyBoolean("VAR_SOUNDOUTPUT", false);

        // Private properties
        $this->RegisterPropertyString("DEVICE_PATH", "/");
        $this->RegisterPropertyInteger("DEVICE_STATE", 0); // unused, kept for compatibility
        $this->RegisterPropertyInteger("LOGLEVEL", 0);

        // Registration uses the unsigned manifest first (required for webOS 26+, where the signed LG test
        // manifest is blacklisted or only gets the basic permissions). If a TV refuses the unsigned manifest,
        // the module falls back to the signed one and remembers that here.
        $this->RegisterAttributeBoolean("SIGNED_PAIRING", false);

        // Lists read from the TV (JSON rows: id, title/label, appId, show) - shown and editable in the form
        $this->RegisterPropertyString("INPUT_LIST", "[]");
        $this->RegisterPropertyString("APP_LIST", "[]");

        $this->RegisterTimer("Update", 0, 'WEBOS_Update($_IPS[\'TARGET\']);');
    }

    public function Destroy()
    {
        if (!IPS_InstanceExists($this->InstanceID)) {
            foreach ([$this->inputProfileName(), $this->appProfileName()] as $profile) {
                if (IPS_VariableProfileExists($profile)) {
                    IPS_DeleteVariableProfile($profile);
                }
            }
        }
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Static profiles
        if (!IPS_VariableProfileExists('WEBOS.Volume')) {
            IPS_CreateVariableProfile('WEBOS.Volume', 1);
            IPS_SetVariableProfileIcon('WEBOS.Volume', 'Speaker');
            IPS_SetVariableProfileValues('WEBOS.Volume', 0, 100, 1);
            IPS_SetVariableProfileText('WEBOS.Volume', '', '');
        }
        $this->setProfileAssociations('WEBOS.Mute', 0, 'Speaker', [[false, 'Ton an'], [true, 'Stumm']]);
        $this->setProfileAssociations('WEBOS.Remote', 1, 'Move', array_map(function ($v, $b) { return [$v, $b[1]]; }, array_keys(self::REMOTE_BUTTONS), self::REMOTE_BUTTONS));
        $this->setProfileAssociations('WEBOS.Media', 1, 'Music', array_map(function ($v, $b) { return [$v, $b[1]]; }, array_keys(self::MEDIA_CONTROLS), self::MEDIA_CONTROLS));
        $this->setProfileAssociations('WEBOS.Channel', 1, 'TV', [[0, 'Sender –'], [1, 'Sender +']]);
        $this->setProfileAssociations('WEBOS.SoundOutput', 1, 'Speaker', array_merge([[-1, 'Andere']], array_map(function ($v, $b) { return [$v, $b[1]]; }, array_keys(self::SOUND_OUTPUTS), self::SOUND_OUTPUTS)));

        // Dynamic profiles (per instance) from cached lists
        $this->updateInputProfile();
        $this->updateAppProfile();

        // Variables (created/removed according to the check boxes in the form)
        $this->maintainVar('Power',          'Power',              0, '~Switch',                  10, 'VAR_POWER',       true);
        $this->maintainVar('Volume',         'Lautstärke',         1, 'WEBOS.Volume',             20, 'VAR_VOLUME',      true);
        $this->maintainVar('Muted',          'Stumm',              0, 'WEBOS.Mute',               21, 'VAR_VOLUME',      true);
        $this->maintainVar('Input',          'Eingang',            1, $this->inputProfileName(),  30, 'VAR_INPUT',       true);
        $this->maintainVar('AppName',        'Aktuelle App',       3, '',                         40, 'VAR_APP',         false);
        $this->maintainVar('AppLaunch',      'App starten',        1, $this->appProfileName(),    41, 'VAR_APPLAUNCH',   true);
        $this->maintainVar('RemoteKey',      'Fernbedienung',      1, 'WEBOS.Remote',             50, 'VAR_REMOTE',      true);
        $this->maintainVar('MediaControl',   'Wiedergabe',         1, 'WEBOS.Media',              51, 'VAR_MEDIA',       true);
        $this->maintainVar('ChannelName',    'Aktueller Sender',   3, '',                         60, 'VAR_CHANNEL',     false);
        $this->maintainVar('ChannelControl', 'Sender wechseln',    1, 'WEBOS.Channel',            61, 'VAR_CHANNEL',     true);
        $this->maintainVar('SoundOutput',    'Tonausgabe',         1, 'WEBOS.SoundOutput',        70, 'VAR_SOUNDOUTPUT', true);

        // Polling
        $interval = $this->ReadPropertyInteger('UPDATE_INTERVAL');
        $active = trim($this->ReadPropertyString('DEVICE_IP')) != '';
        $this->SetTimerInterval('Update', ($active && $interval > 0) ? $interval * 1000 : 0);
        $this->SetStatus($active ? 102 : 104);
    }

    public function Test() {
        $this->Log("Test function called, sending Test message to device");
        $this->RequestAction('Message', 'Testverbindung mit IP-Symcon erfolgreich!');
    }

    public function RegisterDevice() {
        $this->Log("RegisterDevice function called");
        $this->disconnect();
        if ($this->lg_handshake()) {
            $this->Log("Registration successful");
            $this->RefreshLists();
            $this->Update();
            return true;
        }
        $this->Log("Registration failed");
        return false;
    }

    // Reads status from the TV and updates the variables (called by timer)
    public function Update()
    {
        // Without a client key the TV would show the pairing prompt on every poll -> only after registration
        if (trim($this->ReadPropertyString('DEVICE_IP')) == '' || $this->ReadPropertyString('DEVICE_CODE') == '') {
            return false;
        }

        $on = false;
        if ($this->lg_handshake(2)) {
            $p = $this->ssap('ssap://com.webos.service.tvpower/power/getPowerState');
            $state = is_array($p) ? ($p['state'] ?? 'Active') : 'Active';
            $on = in_array($state, ['Active', 'Screen Off', 'Screen Saver']) && !isset($p['processing']);
        }
        $this->setVar('Power', $on);

        if (!$on) {
            $this->setVar('AppName', '');
            $this->setVar('ChannelName', '');
            $this->disconnect();
            return true;
        }

        // Load input/app lists once
        $changed = false;
        if ($this->ReadPropertyBoolean('VAR_INPUT') && count($this->getInputs()) == 0) {
            $changed = $this->refreshInputs() || $changed;
        }
        if (($this->ReadPropertyBoolean('VAR_APP') || $this->ReadPropertyBoolean('VAR_APPLAUNCH')) && count($this->getApps()) == 0) {
            $changed = $this->refreshApps() || $changed;
        }
        if ($changed) {
            IPS_ApplyChanges($this->InstanceID);
        }

        if ($this->ReadPropertyBoolean('VAR_VOLUME')) {
            $p = $this->ssap('ssap://audio/getVolume');
            if (is_array($p)) {
                [$volume, $muted] = $this->parseVolume($p);
                if ($volume !== null) $this->setVar('Volume', $volume);
                if ($muted !== null) $this->setVar('Muted', $muted);
            }
        }

        $needApp = $this->ReadPropertyBoolean('VAR_APP') || $this->ReadPropertyBoolean('VAR_INPUT') || $this->ReadPropertyBoolean('VAR_CHANNEL');
        $appId = '';
        if ($needApp) {
            $p = $this->ssap('ssap://com.webos.applicationManager/getForegroundAppInfo');
            $appId = is_array($p) ? ($p['appId'] ?? '') : '';

            $inputIndex = -1;
            $name = $appId;
            foreach ($this->getInputs() as $i => $input) {
                if (($input['appId'] ?? '') != '' && $input['appId'] == $appId) {
                    if (!empty($input['show'])) $inputIndex = $i; // only inputs that are part of the selection
                    $name = $input['label'];
                }
            }
            foreach ($this->getApps() as $app) {
                if ($app['id'] == $appId) {
                    $name = $app['title'];
                }
            }
            $this->setVar('AppName', $name);
            $this->setVar('Input', $inputIndex);
        }

        if ($this->ReadPropertyBoolean('VAR_CHANNEL')) {
            $channel = '';
            if ($appId == 'com.webos.app.livetv') {
                $p = $this->ssap('ssap://tv/getCurrentChannel');
                if (is_array($p)) {
                    $channel = trim(($p['channelNumber'] ?? '') . ' ' . ($p['channelName'] ?? ''));
                }
            }
            $this->setVar('ChannelName', $channel);
        }

        if ($this->ReadPropertyBoolean('VAR_SOUNDOUTPUT')) {
            $p = $this->ssap('ssap://com.webos.service.apiadapter/audio/getSoundOutput');
            if (is_array($p) && isset($p['soundOutput'])) {
                $index = -1;
                foreach (self::SOUND_OUTPUTS as $v => $o) {
                    if ($o[0] == $p['soundOutput']) $index = $v;
                }
                $this->setVar('SoundOutput', $index);
            }
        }

        $this->disconnect();
        return true;
    }

    // Reads inputs (HDMI ...) and installed apps from the TV and updates the selection profiles
    public function RefreshLists()
    {
        if (!$this->lg_handshake()) {
            $this->Log("RefreshLists: TV not reachable");
            return false;
        }
        $changed = $this->refreshInputs();
        $changed = $this->refreshApps() || $changed;
        if ($changed) {
            IPS_ApplyChanges($this->InstanceID);
        }
        $this->ReloadForm();
        return true;
    }

    // Sends a remote control button via the pointer input socket (e.g. "UP", "ENTER", "BACK", "HOME")
    public function SendButton(string $Name) {
        return $this->sendPointerButton($Name);
    }

    public function SetVolume(int $Value) {
        return $this->RequestAction('Volume', $Value);
    }

    public function SetMute(bool $Value) {
        return $this->RequestAction('Muted', $Value);
    }

    public function PowerOn() {
        return $this->RequestAction('PowerOn', '');
    }

    public function PowerOff() {
        return $this->RequestAction('PowerOff', '');
    }

    public function GetPowerState() {
        return $this->RequestAction('CurrentPowerState', '');
    }

    public function SendKey($Value) {
        return $this->RequestAction('SendKey', $Value);
    }
    public function Mute() {
        return $this->RequestAction('Mute', '');
    }

    public function VolumeUp() {
        return $this->RequestAction('VolumeUp', '');
    }

    public function VolumeDown() {
        return $this->RequestAction('VolumeDown', '');
    }

    public function GetVolume() {
        return $this->RequestAction('CurrentVolume', '');
    }

    public function ChannelUp() {
        return $this->RequestAction('ChannelUp', '');
    }

    public function ChannelDown() {
        return $this->RequestAction('ChannelDown', '');
    }

    public function SetChannel($Value) {
        return $this->RequestAction('SetChannel', $Value);
    }

    public function GetChannelList() {
        return $this->RequestAction('ChannelList', '');
    }


    public function SetInput($Value) {
        return $this->RequestAction('InputSource', $Value);
    }

    public function GetInputList() {
        return $this->RequestAction('InputList', '');
    }


    public function LaunchApp($Value) {
        $this->RequestAction('LaunchApp', $Value);
    }

    public function CloseApp($Value) {
        $this->RequestAction('CloseApp', $Value);
    }

    public function LaunchNetflix() {
        $this->RequestAction('LaunchNetflix', '');
    }

    public function CloseNetflix() {
        $this->RequestAction('CloseNetflix', '');
    }

    public function LaunchYouTube() {
        $this->RequestAction('LaunchYouTube', '');
    }

    public function CloseYouTube() {
        $this->RequestAction('CloseYouTube', '');
    }

    public function GetCurrentApp() {
        return $this->RequestAction('CurrentApp', '');
    }
    public function GetAppList() {
        return $this->RequestAction('AppList', '');
    }

    public function Play() {
        $this->RequestAction('Play', '');
    }

    public function Pause() {
        $this->RequestAction('Pause', '');
    }

    public function Stop() {
        $this->RequestAction('Stop', '');
    }

    public function Rewind() {
        $this->RequestAction('Rewind', '');
    }

    public function FastForward() {
        $this->RequestAction('FastForward', '');
    }

    public function DisplayMessage($Value) {
        $this->RequestAction('Message', $Value);
    }

    public function GetSystemInfo() {
        return $this->RequestAction('SystemInfo', '');
    }

    public function GetAudioStatus() {
        return $this->RequestAction('getAudioStatus', '');
    }

    public function GetSoundOutput() {
        return $this->RequestAction('getSoundOutput', '');
    }

    public function SetSoundOutput($Value) {
        return $this->RequestAction('setSoundOutput', $Value);
    }

    public function RequestAction($Ident, $Value)
    {
        $response = null;

        switch ($Ident)
        {
            // Variable actions (switchable variables below the instance)
            case 'Power':
                $response = $Value ? $this->RequestAction('PowerOn', '') : $this->RequestAction('PowerOff', '');
                $this->setVar('Power', (bool)$Value);
                break;
            case 'Volume':
                $response = $this->ssap('ssap://audio/setVolume', ['volume' => max(0, min(100, (int)$Value))]);
                $this->setVar('Volume', (int)$Value);
                break;
            case 'Muted':
                $response = $this->ssap('ssap://audio/setMute', ['mute' => (bool)$Value]);
                $this->setVar('Muted', (bool)$Value);
                break;
            case 'Input':
                $inputs = $this->getInputs();
                if (isset($inputs[$Value])) {
                    $response = $this->ssap('ssap://tv/switchInput', ['inputId' => $inputs[$Value]['id']]);
                    $this->setVar('Input', (int)$Value);
                }
                break;
            case 'AppLaunch':
                $apps = $this->getApps();
                if (isset($apps[$Value])) {
                    $response = $this->ssap('ssap://system.launcher/launch', ['id' => $apps[$Value]['id']]);
                    $this->setVar('AppLaunch', (int)$Value);
                }
                break;
            case 'RemoteKey':
                if (isset(self::REMOTE_BUTTONS[$Value])) {
                    $response = $this->sendPointerButton(self::REMOTE_BUTTONS[$Value][0]);
                }
                break;
            case 'MediaControl':
                if (isset(self::MEDIA_CONTROLS[$Value])) {
                    $response = $this->ssap('ssap://media.controls/' . self::MEDIA_CONTROLS[$Value][0]);
                }
                break;
            case 'ChannelControl':
                $response = $this->ssap($Value ? 'ssap://tv/channelUp' : 'ssap://tv/channelDown');
                break;
            case 'SoundOutput':
                if (isset(self::SOUND_OUTPUTS[$Value])) {
                    $response = $this->ssap('ssap://audio/changeSoundOutput', ['output' => self::SOUND_OUTPUTS[$Value][0]]);
                    $this->setVar('SoundOutput', (int)$Value);
                }
                break;

            // TV Controls
            case 'SendKey':
                $command = '{"id":"sendKey","type":"request","uri":"ssap://com.webos.service.ime/sendKey","payload":{"keyCode":"'.$Value.'"}}';
                $response = $this->send_command($command);
                break;
            case 'VolumeUp':
                $command = '{"id":"volumeUp","type":"request","uri":"ssap://audio/volumeUp"}';
                $response = $this->send_command($command);
                break;
            case 'VolumeDown':
                $command = '{"id":"volumeDown","type":"request","uri":"ssap://audio/volumeDown"}';
                $response = $this->send_command($command);
                break;
            case 'CurrentVolume':
                $command = '{"id":"currentVolume","type":"request","uri":"ssap://audio/getVolume"}';
                $response = $this->send_command($command);

                if($response && property_exists($response, 'payload')) {
                    [$volume, $muted] = $this->parseVolume(json_decode(json_encode($response->payload), true) ?: []);
                    if ($volume !== null) {
                        $response = $muted ? 0 : $volume;
                    }
                }

                break;
            case 'Mute':
                $p = $this->ssap('ssap://audio/getVolume');
                if (!is_array($p)) {
                    $this->Log("Error getting current volume for mute toggle, aborting mute command!");
                    return null;
                }
                [$volume, $muted] = $this->parseVolume($p);
                $SetMute = $muted ? 'false' : 'true'; // toggle

                $command = '{"id":"mute","type":"request","uri":"ssap://audio/setMute","payload":{"mute":'.$SetMute.'}}';
                $response = $this->send_command($command);
                $this->setVar('Muted', !$muted);
                break;

            // Channel Controls
            case 'ChannelUp':
                $command = '{"id":"channelUp","type":"request","uri":"ssap://tv/channelUp"}';
                $response = $this->send_command($command);
                break;
            case 'ChannelDown':
                $command = '{"id":"channelDown","type":"request","uri":"ssap://tv/channelDown"}';
                $response = $this->send_command($command);
                break;
            case 'SetChannel':
                $command = '{"id":"setChannel","type":"request","uri":"ssap://tv/openChannel","payload":{"channelNumber":"'.$Value.'"}}';
                $response = $this->send_command($command);
                break;
            case 'ChannelList':
                $command = '{"id":"channelList","type":"request","uri":"ssap://tv/getChannelList"}';
                $response = $this->send_command($command);
                break;

            // Input Controls
            case 'InputSource':
                $command = '{"id":"setInputSource","type":"request","uri":"ssap://tv/switchInput","payload":{"inputId":"'.$Value.'"}}';
                $response = $this->send_command($command);
                break;
             case 'InputList':
                $command = '{"id":"getExternalInputList","type":"request","uri":"ssap://tv/getExternalInputList"}';
                $response = $this->send_command($command);
                break;

            // Apps
            case 'LaunchApp':
                $command = '{"id":"launchApp","type":"request","uri":"ssap://system.launcher/launch","payload":{"id":"'.$Value.'"}}';
                $response = $this->send_command($command);
                break;
            case 'CloseApp':
                $command = '{"id":"closeApp","type":"request","uri":"ssap://system.launcher/close","payload":{"id":"'.$Value.'"}}';
                $response = $this->send_command($command);
                break;
            case 'LaunchNetflix':
                $command = '{"id":"launchNetflix","type":"request","uri":"ssap://system.launcher/launch","payload":{"id":"netflix"}}';
                $response = $this->send_command($command);
                break;
            case 'CloseNetflix':
                $command = '{"id":"closeNetflix","type":"request","uri":"ssap://system.launcher/close","payload":{"id":"netflix"}}';
                $response = $this->send_command($command);
                break;
            case 'LaunchYouTube':
                $command = '{"id":"launchYouTube","type":"request","uri":"ssap://system.launcher/launch","payload":{"id":"youtube.leanback.v4"}}';
                $response = $this->send_command($command);
                break;
            case 'CloseYouTube':
                $command = '{"id":"closeYouTube","type":"request","uri":"ssap://system.launcher/close","payload":{"id":"youtube.leanback.v4"}}';
                $response = $this->send_command($command);
                break;
            case 'CurrentApp':
                $command = '{"id":"currentApp","type":"request","uri":"ssap://com.webos.applicationManager/getForegroundAppInfo"}';
                $response = $this->send_command($command);
                break;
            case 'AppList':
                $command = '{"id":"appList","type":"request","uri":"ssap://com.webos.applicationManager/listLaunchPoints"}';
                $response = $this->send_command($command);
                break;

            // Media Controls
            case 'Play':
                $command = '{"id":"play","type":"request","uri":"ssap://media.controls/play"}';
                $response = $this->send_command($command);
                break;
            case 'Pause':
                $command = '{"id":"pause","type":"request","uri":"ssap://media.controls/pause"}';
                $response = $this->send_command($command);
                break;
            case 'Stop':
                $command = '{"id":"stop","type":"request","uri":"ssap://media.controls/stop"}';
                $response = $this->send_command($command);
                break;
            case 'Rewind':
                $command = '{"id":"rewind","type":"request","uri":"ssap://media.controls/rewind"}';
                $response = $this->send_command($command);
                break;
            case 'FastForward':
                $command = '{"id":"fastForward","type":"request","uri":"ssap://media.controls/fastForward"}';
                $response = $this->send_command($command);
                break;

            // Extended Audio Controls
            case 'getAudioStatus':
                $command = '{"id":"getAudioStatus","type":"request","uri":"ssap://audio/getStatus"}';
                $response = $this->send_command($command);
                break;
            case 'getSoundOutput':
                $command = '{"id":"getSoundOutput","type":"request","uri":"ssap://com.webos.service.apiadapter/audio/getSoundOutput"}';
                $response = $this->send_command($command);
                break;
            case 'setSoundOutput':
                $command = '{"id":"setSoundOutput","type":"request","uri":"ssap://audio/changeSoundOutput","payload":{"output":"'.$Value.'"}}';
                $response = $this->send_command($command);
                break;

            // Power Controls
            case 'PowerOff':
                $command = '{"id":"powerOff","type":"request","uri":"ssap://system/turnOff"}';
                $response = $this->send_command($command);
                break;
            case 'PowerOn':
                $mac = $this->ReadPropertyString("DEVICE_MAC");
                if (!$mac || strlen(trim($mac)) == 0) {
                    $this->Log("PowerOn: No MAC address configured for WOL, aborting.");
                    return null;
                }
                $mac_clean = preg_replace('/[^0-9A-Fa-f]/', '', $mac);
                if (strlen($mac_clean) != 12) {
                    $this->Log("PowerOn: Invalid MAC address format: " . $mac . ", aborting WOL.");
                    return null;
                }
                $hw = pack('H*', $mac_clean);
                $packet = str_repeat(chr(0xFF), 6) . str_repeat($hw, 16);
                $port = 9;
                $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if ($sock === false) {
                    $this->Log("PowerOn: Failed to create socket for WOL.");
                    return null;
                }
                socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
                $sent = @socket_sendto($sock, $packet, strlen($packet), 0, '255.255.255.255', $port);
                socket_close($sock);
                if ($sent === false) {
                    $this->Log("PowerOn: WOL packet failed to send.");
                    return null;
                }
                $this->Log("PowerOn: WOL packet sent to MAC " . $mac);
                $response = true;
                break;
            case 'CurrentPowerState':
                $command = '{"id":"currentPowerState","type":"request","uri":"ssap://com.webos.service.tvpower/power/getPowerState"}';
                $response = $this->send_command($command);
                break;

            // Misc
            case 'Message':
                $response = $this->ssap('ssap://system.notifications/createToast', ['message' => (string)$Value]);
                break;

            case 'SystemInfo': // TODO: 404 insufficient permissions
                $command = '{"id":"currentSystemInfo","type":"request","uri":"ssap://system/getSystemInfo"}';
                $response = $this->send_command($command);
                break;

            default:
                $this->Log("Invalid Ident in RequestAction: ".$Ident);
                $this->Log("Value: ".$Value);
                break;
        }

        return $response;
    }

    // PRIVATE FUNCTIONS

    // ---------- Variables & profiles ----------

    private function maintainVar($Ident, $Name, $Type, $Profile, $Position, $Property, $Action)
    {
        $keep = $this->ReadPropertyBoolean($Property);
        $this->MaintainVariable($Ident, $Name, $Type, $Profile, $Position, $keep);
        if ($keep && $Action) {
            $this->EnableAction($Ident);
        }
    }

    private function setVar($Ident, $Value)
    {
        $id = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
        if ($id === false || $id == 0) {
            return;
        }
        if (GetValue($id) !== $Value) {
            $this->SetValue($Ident, $Value);
        }
    }

    private function inputProfileName()
    {
        return 'WEBOS.Input.' . $this->InstanceID;
    }

    private function appProfileName()
    {
        return 'WEBOS.Apps.' . $this->InstanceID;
    }

    // Creates the profile if needed and replaces all associations. $Associations = [[value, caption], ...]
    private function setProfileAssociations($Name, $Type, $Icon, $Associations)
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $Type);
        }
        IPS_SetVariableProfileIcon($Name, $Icon);

        // Nothing to do if the associations are already identical
        $current = [];
        foreach (IPS_GetVariableProfile($Name)['Associations'] as $old) {
            $current[(string)(float)$old['Value']] = $old['Name'];
        }
        $wanted = [];
        foreach ($Associations as $a) {
            $wanted[(string)(float)$a[0]] = $a[1];
        }
        if ($current == $wanted) {
            return;
        }

        // Remove outdated associations (not possible/needed for boolean profiles, those are just overwritten)
        if ($Type != 0) {
            foreach ($current as $value => $caption) {
                if (!isset($wanted[$value])) {
                    IPS_SetVariableProfileAssociation($Name, (float)$value, '', '', -1); // empty name removes the association
                }
            }
        }
        if ($Type != 0 && count($Associations) > 0) {
            IPS_SetVariableProfileValues($Name, $Associations[0][0], $Associations[count($Associations) - 1][0], 0);
        }
        foreach ($Associations as $a) {
            IPS_SetVariableProfileAssociation($Name, $a[0], $a[1], '', -1);
        }
    }

    private function updateInputProfile()
    {
        $assoc = [[-1, 'Andere']];
        foreach ($this->getInputs() as $i => $input) {
            if (empty($input['show'])) continue;
            $assoc[] = [$i, $input['label']];
        }
        $this->setProfileAssociations($this->inputProfileName(), 1, 'TV', $assoc);
    }

    private function updateAppProfile()
    {
        $assoc = [];
        foreach ($this->getApps() as $i => $app) {
            if (empty($app['show'])) continue;
            $assoc[] = [$i, $app['title']];
        }
        if (count($assoc) == 0) {
            $assoc[] = [-1, '–'];
        }
        $this->setProfileAssociations($this->appProfileName(), 1, 'Script', $assoc);
    }

    private function refreshInputs()
    {
        $p = $this->ssap('ssap://tv/getExternalInputList');
        if (!is_array($p) || !isset($p['devices']) || !is_array($p['devices'])) {
            $this->Log("Could not read input list");
            return false;
        }
        $show = $this->showFlags($this->getInputs());
        $inputs = [];
        foreach ($p['devices'] as $d) {
            if (!isset($d['id'])) continue;
            $inputs[] = [
                'label' => ($d['label'] ?? $d['id']),
                'id'    => $d['id'],
                'appId' => ($d['appId'] ?? ''),
                'show'  => $show[$d['id']] ?? true // new entries are shown by default
            ];
        }
        return $this->saveList('INPUT_LIST', $inputs);
    }

    private function refreshApps()
    {
        $p = $this->ssap('ssap://com.webos.applicationManager/listLaunchPoints', null, 8);
        if (!is_array($p) || !isset($p['launchPoints']) || !is_array($p['launchPoints'])) {
            $this->Log("Could not read app list");
            return false;
        }
        $apps = [];
        $seen = [];
        foreach ($p['launchPoints'] as $lp) {
            $id = $lp['id'] ?? ($lp['launchPointId'] ?? '');
            if ($id == '' || isset($seen[$id])) continue;
            $seen[$id] = true;
            $apps[] = ['title' => ($lp['title'] ?? $id), 'id' => $id];
        }
        usort($apps, function ($a, $b) { return strcasecmp($a['title'], $b['title']); });
        $apps = array_slice($apps, 0, 100);
        $show = $this->showFlags($this->getApps());
        foreach ($apps as &$app) {
            $app['show'] = $show[$app['id']] ?? true; // new entries are shown by default
        }
        unset($app);
        return $this->saveList('APP_LIST', $apps);
    }

    private function getInputs()
    {
        $list = json_decode($this->ReadPropertyString('INPUT_LIST'), true);
        return is_array($list) ? array_values($list) : [];
    }

    private function getApps()
    {
        $list = json_decode($this->ReadPropertyString('APP_LIST'), true);
        return is_array($list) ? array_values($list) : [];
    }

    // id => show flag of an existing list (keeps the user's selection when the list is read again)
    private function showFlags($list)
    {
        $flags = [];
        foreach ($list as $row) {
            if (isset($row['id'])) $flags[$row['id']] = !empty($row['show']);
        }
        return $flags;
    }

    // Stores a list in its property. Returns true if it changed (caller applies the changes).
    private function saveList($Property, $List)
    {
        $json = json_encode($List, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json == $this->ReadPropertyString($Property)) {
            return false;
        }
        IPS_SetProperty($this->InstanceID, $Property, $json);
        return true;
    }

    // Supports both payload formats: {volumeStatus:{volume,muteStatus}} (newer) and {volume,muted}/{volume,mute} (older)
    private function parseVolume($p)
    {
        $volume = null;
        $muted = null;
        if (isset($p['volumeStatus']) && is_array($p['volumeStatus'])) {
            $volume = $p['volumeStatus']['volume'] ?? null;
            $muted = $p['volumeStatus']['muteStatus'] ?? null;
        }
        if ($volume === null && isset($p['volume'])) $volume = $p['volume'];
        if ($muted === null && isset($p['muted'])) $muted = $p['muted'];
        if ($muted === null && isset($p['mute'])) $muted = $p['mute'];
        return [$volume === null ? null : (int)$volume, $muted === null ? null : (bool)$muted];
    }

    // ---------- Connection & protocol ----------

    private function Connect($timeout = 5)
    {
        $ip = trim($this->ReadPropertyString("DEVICE_IP"));
        $port = $this->ReadPropertyInteger("DEVICE_PORT");
        if ($ip == '') {
            $this->Log("No IP address configured");
            return false;
        }
        // Port 3000 = unencrypted (ws), otherwise TLS (wss, default 3001)
        $this->sock = $this->openWebSocket($ip, $port, $this->ReadPropertyString("DEVICE_PATH"), $port != 3000, $timeout);
        $this->connected = ($this->sock !== false);
        if (!$this->connected) {
            $this->sock = null;
        } else {
            $this->Log("Sucessfull WS connection to $ip:$port");
        }
        return $this->connected;
    }

    private function disconnect()
    {
        if ($this->sock) {
            @fclose($this->sock);
        }
        $this->sock = null;
        $this->connected = false;
        $this->registered = false;
    }

    // Opens a websocket connection and performs the HTTP upgrade. Returns the stream or false.
    private function openWebSocket($host, $port, $path, $secure, $timeout)
    {
        $context = stream_context_create(['ssl' => [
            'verify_host' => false,
            'verify_peer_name' => false,
            'verify_peer' => false,
            'allow_self_signed' => true
        ]]);
        $stream = @stream_socket_client(($secure ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$stream) {
            $this->Log("Connection to $host:$port failed: $errstr ($errno)");
            return false;
        }
        stream_set_timeout($stream, 0, 50000);

        $key = base64_encode(random_bytes(16));
        $request = "GET " . ($path == '' ? '/' : $path) . " HTTP/1.1\r\n"
                 . "Host: $host:$port\r\n"
                 . "Upgrade: websocket\r\n"
                 . "Connection: Upgrade\r\n"
                 . "Sec-WebSocket-Key: $key\r\n"
                 . "Sec-WebSocket-Version: 13\r\n\r\n";
        @fwrite($stream, $request);

        // Read HTTP response header line by line
        $header = '';
        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            $line = @fgets($stream, 1024);
            if ($line === false || $line === '') {
                if (feof($stream)) break;
                usleep(5000);
                continue;
            }
            $header .= $line;
            if ($line == "\r\n") break;
        }

        $expected = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if (strpos($header, ' 101 ') === false || !preg_match('#Sec-WebSocket-Accept:\s*(\S+)#i', $header, $m) || trim($m[1]) !== $expected) {
            $this->Log("WS handshake failed:\n$header");
            @fclose($stream);
            return false;
        }
        return $stream;
    }

    private function sendFrame($stream, $payload, $opcode = 0x1)
    {
        if (!$stream) return false;
        $len = strlen($payload);
        $frame = chr(0x80 | $opcode);
        if ($len <= 125) {
            $frame .= chr(0x80 | $len);
        } elseif ($len <= 65535) {
            $frame .= chr(0x80 | 126) . pack('n', $len);
        } else {
            $frame .= chr(0x80 | 127) . pack('J', $len);
        }
        $mask = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $len; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }
        $written = 0;
        while ($written < strlen($frame)) {
            $n = @fwrite($stream, substr($frame, $written));
            if ($n === false || $n === 0) return false;
            $written += $n;
        }
        return true;
    }

    private function readBytes($stream, $n, $deadline)
    {
        $buf = '';
        while (strlen($buf) < $n) {
            if (microtime(true) > $deadline) return null;
            $chunk = @fread($stream, $n - strlen($buf));
            if ($chunk === false || $chunk === '') { // false/'' = no data yet (read timeout)
                if (feof($stream)) {
                    $this->connected = false;
                    return null;
                }
                usleep(5000);
                continue;
            }
            $buf .= $chunk;
        }
        return $buf;
    }

    // Reads one complete text message (handles fragmentation, ping, close). Returns string or null.
    private function readMessage($stream, $deadline)
    {
        $message = '';
        while (true) {
            $head = $this->readBytes($stream, 2, $deadline);
            if ($head === null) return null;
            $fin = (ord($head[0]) & 0x80) != 0;
            $opcode = ord($head[0]) & 0x0F;
            $masked = (ord($head[1]) & 0x80) != 0;
            $len = ord($head[1]) & 0x7F;
            if ($len == 126) {
                $ext = $this->readBytes($stream, 2, $deadline);
                if ($ext === null) return null;
                $len = unpack('n', $ext)[1];
            } elseif ($len == 127) {
                $ext = $this->readBytes($stream, 8, $deadline);
                if ($ext === null) return null;
                $len = unpack('J', $ext)[1];
            }
            $mask = $masked ? $this->readBytes($stream, 4, $deadline) : '';
            $data = $len > 0 ? $this->readBytes($stream, $len, $deadline) : '';
            if ($data === null) return null;
            if ($masked) {
                for ($i = 0; $i < $len; $i++) $data[$i] = $data[$i] ^ $mask[$i % 4];
            }

            if ($opcode == 0x9) { // ping -> pong
                $this->sendFrame($stream, $data, 0xA);
                continue;
            }
            if ($opcode == 0xA) continue; // pong
            if ($opcode == 0x8) { // close
                $this->connected = false;
                return null;
            }
            $message .= $data;
            if ($fin) return $message;
        }
    }

    // Waits for the message with the given id. Returns decoded array or null on timeout.
    private function waitForId($id, $timeout)
    {
        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            $raw = $this->readMessage($this->sock, $deadline);
            if ($raw === null) return null;
            $msg = json_decode($raw, true);
            if (!is_array($msg)) {
                $this->Log("Non JSON message ignored: $raw");
                continue;
            }
            if (($msg['id'] ?? '') == $id) {
                $this->Log("Response: $raw");
                return $msg;
            }
            $this->Log("Other message ignored: $raw");
        }
        return null;
    }

    private function lg_handshake($connectTimeout = 5)
    {
        if ($this->registered && $this->connected) {
            return true;
        }
        $this->disconnect();
        if (!$this->Connect($connectTimeout)) {
            return false;
        }

        $key = $this->ReadPropertyString("DEVICE_CODE");
        $unsigned = !$this->ReadAttributeBoolean("SIGNED_PAIRING");
        $retried = false;
        $handshake = $unsigned ? $this->buildUnsignedHandshake($key) : $this->buildSignedHandshake($key);
        $this->Log("Sending LG handshake (" . ($unsigned ? "unsigned" : "signed") . ")\n$handshake");
        $this->sendFrame($this->sock, $handshake);

        $deadline = microtime(true) + 8;
        while (true) {
            $res = $this->waitForId('register_0', max(0.1, $deadline - microtime(true)));
            if ($res === null) {
                $this->Log("ERROR during LG handshake: no response / timeout");
                $this->disconnect();
                return false;
            }
            $type = $res['type'] ?? '';

            if ($type == 'registered') {
                $newKey = $res['payload']['client-key'] ?? '';
                $this->registered = true;
                if ($newKey != '' && $newKey != $key) {
                    IPS_SetProperty($this->InstanceID, "DEVICE_CODE", $newKey);
                    IPS_ApplyChanges($this->InstanceID);
                    $this->Log("LG Client-Key successfully received and applied: $newKey");
                } else {
                    $this->Log("LG Client-Key successfully approved");
                }
                return true;
            }

            if ($type == 'response' && ($res['payload']['pairingType'] ?? '') == 'PROMPT') {
                $this->Log("Please confirm the pairing request on the TV (60 s)");
                $deadline = microtime(true) + 60;
                continue;
            }

            if ($type == 'error') {
                $error = $res['error'] ?? '';
                $this->Log("Registration error: $error");
                // Pairing declined on the TV -> no retry
                if (stripos($error, 'cancel') !== false || stripos($error, 'denied') !== false) {
                    $this->disconnect();
                    return false;
                }
                // Try the other manifest once:
                // - signed rejected ("blacklisted certificate", webOS 26+) -> unsigned
                // - unsigned rejected (older firmware) -> signed
                if (!$retried) {
                    $retried = true;
                    $unsigned = !$unsigned;
                    $this->WriteAttributeBoolean("SIGNED_PAIRING", !$unsigned);
                    $this->disconnect();
                    if (!$this->Connect($connectTimeout)) return false;
                    $handshake = $unsigned ? $this->buildUnsignedHandshake($key) : $this->buildSignedHandshake($key);
                    $this->Log("Retrying LG handshake (" . ($unsigned ? "unsigned" : "signed") . ")\n$handshake");
                    $this->sendFrame($this->sock, $handshake);
                    $deadline = microtime(true) + 8;
                    continue;
                }
                $this->disconnect();
                return false;
            }
        }
    }

    private function buildSignedHandshake($clientKey)
    {
        $handshake = self::SIGNED_HANDSHAKE;
        if (strlen($clientKey) > 0) {
            $handshake = str_replace('"pairingType":"PROMPT",', '"pairingType":"PROMPT","client-key":' . json_encode($clientKey) . ',', $handshake);
        }
        return $handshake;
    }

    // Registration payload without the LG test signature (required for webOS 26+).
    // Permission list taken from lgtv2 (pairing.json) incl. CONTROL_INPUT_TEXT and
    // CONTROL_MOUSE_AND_KEYBOARD which are needed for key/pointer input without signature,
    // plus READ_INSTALLED_APPS for the app list.
    // Note: without signature the TV does not grant WRITE_SETTINGS and a few other protected permissions.
    private function buildUnsignedHandshake($clientKey)
    {
        $permissions = [
            "LAUNCH", "LAUNCH_WEBAPP", "APP_TO_APP", "CLOSE", "TEST_OPEN", "TEST_PROTECTED",
            "CONTROL_AUDIO", "CONTROL_DISPLAY", "CONTROL_INPUT_JOYSTICK", "CONTROL_INPUT_MEDIA_RECORDING",
            "CONTROL_INPUT_MEDIA_PLAYBACK", "CONTROL_INPUT_TV", "CONTROL_POWER", "READ_APP_STATUS",
            "READ_CURRENT_CHANNEL", "READ_INPUT_DEVICE_LIST", "READ_NETWORK_STATE", "READ_RUNNING_APPS",
            "READ_TV_CHANNEL_LIST", "WRITE_NOTIFICATION_TOAST", "READ_POWER_STATE", "READ_COUNTRY_INFO",
            "READ_SETTINGS", "CONTROL_TV_SCREEN", "CONTROL_TV_STANBY", "CONTROL_FAVORITE_GROUP",
            "CONTROL_USER_INFO", "CHECK_BLUETOOTH_DEVICE", "CONTROL_BLUETOOTH", "CONTROL_TIMER_INFO",
            "STB_INTERNAL_CONNECTION", "CONTROL_RECORDING", "READ_RECORDING_STATE", "WRITE_RECORDING_LIST",
            "READ_RECORDING_LIST", "READ_RECORDING_SCHEDULE", "WRITE_RECORDING_SCHEDULE",
            "READ_STORAGE_DEVICE_LIST", "READ_TV_PROGRAM_INFO", "CONTROL_BOX_CHANNEL",
            "READ_TV_ACR_AUTH_TOKEN", "READ_TV_CONTENT_STATE", "READ_TV_CURRENT_TIME",
            "ADD_LAUNCHER_CHANNEL", "SET_CHANNEL_SKIP", "RELEASE_CHANNEL_SKIP", "CONTROL_CHANNEL_BLOCK",
            "DELETE_SELECT_CHANNEL", "CONTROL_CHANNEL_GROUP", "SCAN_TV_CHANNELS", "CONTROL_TV_POWER",
            "CONTROL_WOL", "CONTROL_INPUT_TEXT", "CONTROL_MOUSE_AND_KEYBOARD",
            "READ_INSTALLED_APPS", "WRITE_NOTIFICATION_ALERT" // app list + alerts (as in ColorControl / LGTV Companion V3)
        ];

        $payload = [
            "forcePairing" => false,
            "pairingType"  => "PROMPT",
            "manifest"     => [
                "manifestVersion" => 1,
                "appVersion"      => "1.0",
                "permissions"     => $permissions
            ]
        ];
        if (strlen($clientKey) > 0) {
            $payload["client-key"] = $clientKey;
        }

        return json_encode(["type" => "register", "id" => "register_0", "payload" => $payload], JSON_UNESCAPED_SLASHES);
    }

    // Sends a ssap request and returns the payload as array (null on error/timeout)
    private function ssap($uri, $payload = null, $timeout = 4)
    {
        if (!$this->lg_handshake()) {
            return null;
        }
        $id = 'req_' . (++$this->msgCounter);
        $msg = ['id' => $id, 'type' => 'request', 'uri' => $uri];
        if ($payload !== null) {
            $msg['payload'] = $payload;
        }
        $json = json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->Log("Sending command: $json");
        if (!$this->sendFrame($this->sock, $json)) {
            $this->disconnect();
            return null;
        }
        $res = $this->waitForId($id, $timeout);
        if ($res === null) {
            $this->Log("No response for $uri");
            return null;
        }
        if (($res['type'] ?? '') == 'error') {
            $this->Log("Error for $uri: " . ($res['error'] ?? ''));
            return null;
        }
        return $res['payload'] ?? [];
    }

    // Legacy: sends a complete JSON command string and returns the full response as object (as before)
    private function send_command($cmd)
    {
        $req = json_decode($cmd, true);
        if (!is_array($req) || !isset($req['id'])) {
            $this->Log("Invalid command: $cmd");
            return null;
        }
        if (!$this->lg_handshake()) {
            return null;
        }
        $this->Log("Sending command: " . $cmd);
        if (!$this->sendFrame($this->sock, $cmd)) {
            $this->disconnect();
            return null;
        }
        $res = $this->waitForId($req['id'], 5);
        if ($res === null) {
            $this->Log("Command did not send response or error during send!");
            return null;
        }
        return json_decode(json_encode($res));
    }

    // Remote control buttons need a second websocket (pointer input socket)
    private function sendPointerButton($name)
    {
        $p = $this->ssap('ssap://com.webos.service.networkinput/getPointerInputSocket');
        if (!is_array($p) || empty($p['socketPath'])) {
            $this->Log("Could not get pointer input socket");
            return false;
        }
        $u = parse_url($p['socketPath']);
        if (!$u || !isset($u['host'])) {
            $this->Log("Invalid pointer socket path: " . $p['socketPath']);
            return false;
        }
        $secure = ($u['scheme'] ?? 'wss') == 'wss';
        $port = $u['port'] ?? ($secure ? 3001 : 3000);
        $path = ($u['path'] ?? '/') . (isset($u['query']) ? '?' . $u['query'] : '');
        $stream = $this->openWebSocket($u['host'], $port, $path, $secure, 3);
        if (!$stream) {
            return false;
        }
        $ok = $this->sendFrame($stream, "type:button\nname:" . strtoupper($name) . "\n\n");
        usleep(100000);
        @fclose($stream);
        $this->Log("Button $name sent");
        return $ok;
    }

    private function generateRandomString($length = 10, $addSpaces = true, $addNumbers = true)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
        $useChars = array();

        for($i = 0; $i < $length; $i++)
        {
            $useChars[] = $characters[mt_rand(0, strlen($characters)-1)];
        }

        if($addSpaces === true)
        {
            array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
        }

        if($addNumbers === true)
        {
            array_push($useChars, rand(0,9), rand(0,9), rand(0,9));
        }

        shuffle($useChars);

        $randomString = trim(implode('', $useChars));
        $randomString = substr($randomString, 0, $length);

        return $randomString;
    }

    private function Log($message) {
        $this->SendDebug('WebOS', $message, 0);
        if($this->ReadPropertyInteger("LOGLEVEL") == 1)
            IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], $message);
    }
}
