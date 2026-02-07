<?php

class WebOSDevice extends IPSModule
{     
    private $socket = null;
    private $connected = false;

    // PUBLIC ACCESSIBLE FUNCTIONS

    public function __destruct()
    {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }

    public function Create()
    {
        parent::Create();
        
        // Public properties
        $this->RegisterPropertyString("DEVICE_IP", "");
        $this->RegisterPropertyInteger("DEVICE_PORT", "3001");
        $this->RegisterPropertyString("DEVICE_CODE", "");
        $this->RegisterPropertyString("DEVICE_WSKEY", base64_encode($this->generateRandomString(16, false, true)));

        $this->RegisterPropertyInteger("DEVICE_SOCKET", 0);

        // Private properties
        $this->RegisterPropertyString("DEVICE_PATH", "/");
        $this->RegisterPropertyInteger("DEVICE_STATE", 0); // 0=disconnected, 1=connecting, 2=connected
        $this->RegisterPropertyInteger("LOGLEVEL", 0);
    }
    
    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function Test() {
        $this->Log("Test function called, sending Test message to device");
        $this->RequestAction('Message', 'Testverbindung mit IP-Symcon erfolgreich!');
    }

    public function RegisterDevice() {
        $this->Log("RegisterDevice function called");
        $this->Connect();
        $this->lg_handshake();
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

                if($response) {
                    if(property_exists($response, 'payload') && property_exists($response->payload, 'volumeStatus')) {
                        if($response->payload->volumeStatus->muteStatus == 1)
                            $response = 0;
                        else
                            $response = $response->payload->volumeStatus->volume;
                    }
                }

                break;
            case 'Mute':
                $response = $this->RequestAction('GetAudioStatus', '');

                $SetMute = '1'; // default at power on is unmuted, reversed state to set mute to
                if(property_exists($response, 'payload') && property_exists($response->payload, 'volumeStatus')) {
                    if($response->payload->volumeStatus->muteStatus == 1)
                        $SetMute = '0';
                } else {
                    $this->Log("Error getting current volume for mute toggle, aborting mute command!");
                    return null;
                }

                $command = '{"id":"mute","type":"request","uri":"ssap://audio/setMute","payload":{"mute":'.$SetMute.'}}';
                $response = $this->send_command($command);
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
            case 'AppList': // TODO: 404 insufficient permissions
                $command = '{"id":"appList","type":"request","uri":"ssap://com.webos.applicationManager/listApps"}';
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
                $command = '{"id":"getSoundOutput","type":"request","uri":"ssap://audio/getStatus"}';
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
                // TODO: WOL
                $command = '{"id":"powerOn","type":"request","uri":"ssap://system/turnOn"}';
                $response = $this->send_command($command);
                break;
            case 'CurrentPowerState':
                $command = '{"id":"currentPowerState","type":"request","uri":"ssap://com.webos.service.tvpower/power/getPowerState"}';
                $response = $this->send_command($command);
                break;

            // Misc
            case 'Message':
                $command = '{"id":"message","type":"request","uri":"ssap://system.notifications/createToast","payload":{"message":"'.$Value.'"}}';
                $response = $this->send_command($command);
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

    private function Connect()
    {
        IPS_SetProperty($this->InstanceID, "DEVICE_STATE", 1); // connecting
        
        $ws_handshake_cmd = "GET ".IPS_GetProperty($this->InstanceID, "DEVICE_PATH")." HTTP/1.1\r\n";
        $ws_handshake_cmd.= "Upgrade: websocket\r\n";
        $ws_handshake_cmd.= "Connection: Upgrade\r\n";
        $ws_handshake_cmd.= "Sec-WebSocket-Version: 13\r\n";            
        $ws_handshake_cmd.= "Sec-WebSocket-Key: ".IPS_GetProperty($this->InstanceID, "DEVICE_WSKEY")."\r\n";
        $ws_handshake_cmd.= "Host: ssl://".IPS_GetProperty($this->InstanceID, "DEVICE_IP").":".IPS_GetProperty($this->InstanceID, "DEVICE_PORT")."\r\n\r\n";
        //$this->sock = fsockopen($this->host, $this->port, $errno, $errstr, 2);

        $context = stream_context_create(['ssl' => [
            //'ciphers' => 'RC4-MD5',
            'verify_host' => FALSE,
            'verify_peer_name' => FALSE,
            'verify_peer' => FALSE
        ]]);

        $this->sock = stream_socket_client('ssl://'.IPS_GetProperty($this->InstanceID, "DEVICE_IP").':'.IPS_GetProperty($this->InstanceID, "DEVICE_PORT"), $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

        socket_set_timeout($this->sock, 0, 10000);

        $this->Log("Sending WS handshake\n$ws_handshake_cmd");

        $response = $this->send($ws_handshake_cmd);
        if ($response)
        {
            $this->Log("WS Handshake Response:\n$response");
        } 
        else { 
            $this->Log("ERROR during WS handshake!");
            IPS_SetProperty($this->InstanceID, "DEVICE_STATE", 0); // disconnected
        }
        
        preg_match('#Sec-WebSocket-Accept:\s(.*)$#mU', $response, $matches);
        if ($matches) 
        {
            $keyAccept = trim($matches[1]);
            $expectedResonse = base64_encode(pack('H*', sha1(IPS_GetProperty($this->InstanceID, "DEVICE_WSKEY") . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $this->connected = ($keyAccept === $expectedResonse) ? true : false;
        } 
        else { 
            $this->connected=false;
            IPS_SetProperty($this->InstanceID, "DEVICE_STATE", 0); // disconnected
        }
        
        if ($this->connected) {
            $this->Log("Sucessfull WS connection to ".IPS_GetProperty($this->InstanceID, "DEVICE_IP").":".IPS_GetProperty($this->InstanceID, "DEVICE_PORT"));
            IPS_SetProperty($this->InstanceID, "DEVICE_STATE", 2); // connected
        }

        return $this->connected;  
    }

    private function lg_handshake() {
        $this->Log("Starting LG Handshake");
        $this->Log("Connection state: ".$this->connected);
        if (is_null($this->socket)) $this->Connect();

        if ($this->connected)
        {
            $handshake = '{"type":"register","id":"register_0","payload":{"forcePairing":false,"pairingType":"PROMPT","client-key":"HANDSHAKEKEYGOESHERE","manifest":{"manifestVersion":1,"appVersion":"1.1","signed":{"created":"20140509","appId":"com.lge.test","vendorId":"com.lge","localizedAppNames":{"":"LG Remote App","ko-KR":"ë¦¬ëª¨ì»¨ ì•±","zxx-XX":"Ð›Ð“ RÑ�Ð¼otÑ� AÐŸÐŸ"},"localizedVendorNames":{"":"LG Electronics"},"permissions":["TEST_SECURE","CONTROL_INPUT_TEXT","CONTROL_MOUSE_AND_KEYBOARD","READ_INSTALLED_APPS","READ_LGE_SDX","READ_NOTIFICATIONS","SEARCH","WRITE_SETTINGS","WRITE_NOTIFICATION_ALERT","CONTROL_POWER","READ_CURRENT_CHANNEL","READ_RUNNING_APPS","READ_UPDATE_INFO","UPDATE_FROM_REMOTE_APP","READ_LGE_TV_INPUT_EVENTS","READ_TV_CURRENT_TIME"],"serial":"2f930e2d2cfe083771f68e4fe7bb07"},"permissions":["LAUNCH","LAUNCH_WEBAPP","APP_TO_APP","CLOSE","TEST_OPEN","TEST_PROTECTED","CONTROL_AUDIO","CONTROL_DISPLAY","CONTROL_INPUT_JOYSTICK","CONTROL_INPUT_MEDIA_RECORDING","CONTROL_INPUT_MEDIA_PLAYBACK","CONTROL_INPUT_TV","CONTROL_POWER","READ_APP_STATUS","READ_CURRENT_CHANNEL","READ_INPUT_DEVICE_LIST","READ_NETWORK_STATE","READ_RUNNING_APPS","READ_TV_CHANNEL_LIST","WRITE_NOTIFICATION_TOAST","READ_POWER_STATE","READ_COUNTRY_INFO"],"signatures":[{"signatureVersion":1,"signature":"eyJhbGdvcml0aG0iOiJSU0EtU0hBMjU2Iiwia2V5SWQiOiJ0ZXN0LXNpZ25pbmctY2VydCIsInNpZ25hdHVyZVZlcnNpb24iOjF9.hrVRgjCwXVvE2OOSpDZ58hR+59aFNwYDyjQgKk3auukd7pcegmE2CzPCa0bJ0ZsRAcKkCTJrWo5iDzNhMBWRyaMOv5zWSrthlf7G128qvIlpMT0YNY+n/FaOHE73uLrS/g7swl3/qH/BGFG2Hu4RlL48eb3lLKqTt2xKHdCs6Cd4RMfJPYnzgvI4BNrFUKsjkcu+WD4OO2A27Pq1n50cMchmcaXadJhGrOqH5YmHdOCj5NSHzJYrsW0HPlpuAx/ECMeIZYDh6RMqaFM2DXzdKX9NmmyqzJ3o/0lkk/N97gfVRLW5hA29yeAwaCViZNCP8iC9aO0q9fQojoa7NQnAtw=="}]}}}';
            if (strlen(IPS_GetProperty($this->InstanceID, "DEVICE_CODE") > 0))
                $handshake = str_replace('HANDSHAKEKEYGOESHERE',IPS_GetProperty($this->InstanceID, "DEVICE_CODE"),$handshake);
            else  
                $handshake = '{"type":"register","id":"register_0","payload":{"forcePairing":false,"pairingType":"PROMPT","manifest":{"manifestVersion":1,"appVersion":"1.1","signed":{"created":"20140509","appId":"com.lge.test","vendorId":"com.lge","localizedAppNames":{"":"LG Remote App","ko-KR":"ë¦¬ëª¨ì»¨ ì•±","zxx-XX":"Ð›Ð“ RÑ�Ð¼otÑ� AÐŸÐŸ"},"localizedVendorNames":{"":"LG Electronics"},"permissions":["TEST_SECURE","CONTROL_INPUT_TEXT","CONTROL_MOUSE_AND_KEYBOARD","READ_INSTALLED_APPS","READ_LGE_SDX","READ_NOTIFICATIONS","SEARCH","WRITE_SETTINGS","WRITE_NOTIFICATION_ALERT","CONTROL_POWER","READ_CURRENT_CHANNEL","READ_RUNNING_APPS","READ_UPDATE_INFO","UPDATE_FROM_REMOTE_APP","READ_LGE_TV_INPUT_EVENTS","READ_TV_CURRENT_TIME"],"serial":"2f930e2d2cfe083771f68e4fe7bb07"},"permissions":["LAUNCH","LAUNCH_WEBAPP","APP_TO_APP","CLOSE","TEST_OPEN","TEST_PROTECTED","CONTROL_AUDIO","CONTROL_DISPLAY","CONTROL_INPUT_JOYSTICK","CONTROL_INPUT_MEDIA_RECORDING","CONTROL_INPUT_MEDIA_PLAYBACK","CONTROL_INPUT_TV","CONTROL_POWER","READ_APP_STATUS","READ_CURRENT_CHANNEL","READ_INPUT_DEVICE_LIST","READ_NETWORK_STATE","READ_RUNNING_APPS","READ_TV_CHANNEL_LIST","WRITE_NOTIFICATION_TOAST","READ_POWER_STATE","READ_COUNTRY_INFO"],"signatures":[{"signatureVersion":1,"signature":"eyJhbGdvcml0aG0iOiJSU0EtU0hBMjU2Iiwia2V5SWQiOiJ0ZXN0LXNpZ25pbmctY2VydCIsInNpZ25hdHVyZVZlcnNpb24iOjF9.hrVRgjCwXVvE2OOSpDZ58hR+59aFNwYDyjQgKk3auukd7pcegmE2CzPCa0bJ0ZsRAcKkCTJrWo5iDzNhMBWRyaMOv5zWSrthlf7G128qvIlpMT0YNY+n/FaOHE73uLrS/g7swl3/qH/BGFG2Hu4RlL48eb3lLKqTt2xKHdCs6Cd4RMfJPYnzgvI4BNrFUKsjkcu+WD4OO2A27Pq1n50cMchmcaXadJhGrOqH5YmHdOCj5NSHzJYrsW0HPlpuAx/ECMeIZYDh6RMqaFM2DXzdKX9NmmyqzJ3o/0lkk/N97gfVRLW5hA29yeAwaCViZNCP8iC9aO0q9fQojoa7NQnAtw=="}]}}}';
            
            $this->Log("Sending LG handshake\n$handshake");
            $response = $this->send($this->hybi10Encode($handshake));
            if ($response)
            {
                $this->Log("LG Handshake Response\n".$this->json_string($response));
                $result = $this->json_array($response);
                if ($result && array_key_exists('id',$result) &&  $result['id']=='result_0' && array_key_exists('client-key',$result['payload']))
                {
                    if (IPS_GetProperty($this->InstanceID, "DEVICE_CODE") == $result['payload']['client-key']);
                        $this->Log("LG Client-Key successfully approved"); 
                } 
                else if ($result && array_key_exists('id',$result) &&  $result['id']=='register_0' && array_key_exists('pairingType',$result['payload']) && array_key_exists('returnValue',$result['payload']))
                {
                    if ($result['payload']['pairingType'] == "PROMPT" && $result['payload']['returnValue'] == "true") 
                    {
                        $starttime = microtime(1);
                        $lg_key_received = false;
                        $error_received = false;
                        do
                        {
                            $response = @fread($this->sock, 8192);
                            $result = $this->json_array($response);
                            if ($result && array_key_exists('id',$result) &&  $result['id']=='register_0' && is_array($result['payload']) && array_key_exists('client-key',$result['payload']))
                            {
                                $lg_key_received = true;
                                $lg_key = $result['payload']['client-key'];
                                IPS_SetProperty($this->InstanceID, "DEVICE_CODE", $lg_key);
                                IPS_ApplyChanges($this->InstanceID);
                                $this->Log("LG Client-Key successfully received and applied: $lg_key"); 
                            } 
                            else if ($result && array_key_exists('id',$result) &&  $result['id']=='register_0' && array_key_exists('error',$result))
                            {
                                $error_received = true;
                                $this->Log("ERROR: ".$result['error']);
                            }
                            usleep(200000);
                            $time = microtime(1);
                        } 
                        while ($time-$starttime<60 && !$lg_key_received && !$error_received);
                    }
                }
            } 
            else $this->Log("ERROR during LG handshake:");
        } 
        else return FALSE; 
    }

    private function send($msg)
    {
        @fwrite($this->sock, $msg);
        usleep(250000);
        $response = @fread($this->sock, 8192);
        return $response;
    }

    private function send_command($cmd)
	{
        $response = null;

        $this->Log("Current Connection State: ".IPS_GetProperty($this->InstanceID, "DEVICE_STATE"));

		$this->lg_handshake();

		if (IPS_GetProperty($this->InstanceID, "DEVICE_STATE") == 2)
		{
			$this->Log("Sending command: ".$cmd);
			$response = $this->send($this->hybi10Encode($cmd));
			
            if ($response) {
                $this->Log("raw Command response: ".$response);
                $response = $this->json_string($response);
				$this->Log("Command response: ".$response);
            }
			else 
				$this->Log("Command did not send response or error during send!");			
		} 

        $this->Log("Current JSON Response: ".print_r(json_decode($response), true));
        return json_decode($response);
	}
    
    
    protected function RegisterProfileString($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 3);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 3)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        @IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }

    protected function RegisterProfileStringEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileString($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }   
    }
    
    
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 0)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);  
    }
    
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    private function hybi10Encode($payload, $type = 'text', $masked = true) {
        $frameHead = array();
        $frame = '';
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                $frameHead[0] = 129;
                break;

            case 'close':
                $frameHead[0] = 136;
                break;

            case 'ping':
                $frameHead[0] = 137;
                break;

            case 'pong':
                $frameHead[0] = 138;
                break;
        }

        if ($payloadLength > 65535)
        {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) 
            {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            if ($frameHead[2] > 127) 
            {
                $this->close(1004);
                return false;
            }
        } 
        elseif ($payloadLength > 125) 
        {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } 
        else    
        {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }
        foreach (array_keys($frameHead) as $i) 
        {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) 
        {
            $mask = array();
            for ($i = 0; $i < 4; $i++)
            {
                $mask[$i] = chr(rand(0, 255));
            }
            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        for ($i = 0; $i < $payloadLength; $i++) 
        {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }
        return $frame;
    }

    private function json_array($str)
    {
        $result = json_decode($this->json_string($str),true);
        return $result;
    }
    
    private function json_string($str)
    {
        $from = strpos($str,"{");
        $to = strripos($str,"}");
        $len = $to-$from+1;
        $result = substr($str,$from,$len);
        if(!json_validate($result)) {
            $this->Log("NON Valid JSON string extracted: ".$result);
            $from = strpos($str,"{")+1;
            $to = strripos($str,"}");
            $len = $to-$from+1;
            $result = substr($str,$from,$len);
        }
        return $result;
    }

    private function generateRandomString($length = 10, $addSpaces = true, $addNumbers = true)
    {  
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"Â§$%&/()=[]{}';
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

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    private function Log($message) {
        if(IPS_GetProperty($this->InstanceID, "LOGLEVEL") == 1)
            IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], $message);
    }
}