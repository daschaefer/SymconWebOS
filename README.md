LG WebOS Module für IP-Symcon
===
Dieses IP-Symcon PHP Modul ermöglicht die Interaktion mit WebOS basierten LG Geräten.

Vorraussetzungen:
- Ein LG WebOS Gerät (z.B. Fernseher, Soundbar) mit Netzwerkverbindung
- IP-Symcon Version 8.1 oder höher

Funktionen:
- Ein- und Ausschalten des Geräts
- Lautstärkeregelung (Lautstärke erhöhen, verringern, stummschalten)
- Kanalwechsel (nächster, vorheriger Kanal)
- Eingangsquellen wechseln
- Abrufen von Informationen über die aktuelle Lautstärke, den Kanal und die Eingangsquelle
- Abrufen von Listen der verfügbaren Kanäle und Eingangsquellen
- Abrufen und Ändern der Soundausgabe (TV-Lautsprecher, externes Audio)
- Unterstützung einer automatischen Registrierung des Geräts über einen Gerätecode und Websocket Schlüssel

Folgende Methoden werden bereitgestellt:
- `WEBOS_RegisterDevice`: Startet den Registrierungsprozess für das Gerät, um die erforderlichen Berechtigungen zu erhalten, damit das Modul mit dem Gerät kommunizieren kann.
- `WEBOS_PowerOn`: Schaltet das Gerät ein.
- `WEBOS_PowerOff`: Schaltet das Gerät aus.
- `WEBOS_GetPowerState`: Gibt den aktuellen Ein-/Ausschaltstatus des Geräts zurück.
- `WEBOS_VolumeUp`: Erhöht die Lautstärke um eine Stufe.
- `WEBOS_VolumeDown`: Verringert die Lautstärke um eine Stufe.
- `WEBOS_GetVolume`: Gibt die aktuelle Lautstärke zurück.
- `WEBOS_Mute`: Schaltet die Stummschaltung ein oder aus.
- `WEBOS_ChannelUp`: Wechselt zum nächsten Kanal.
- `WEBOS_ChannelDown`: Wechselt zum vorherigen Kanal.
- `WEBOS_SetInputSource`: Wechselt zur angegebenen Eingangsquelle.
- `WEBOS_GetVolume`: Gibt die aktuelle Lautstärke zurück.
- `WEBOS_GetChannel`: Gibt den aktuellen Kanal zurück.
- `WEBOS_GetInputSource`: Gibt die aktuelle Eingangsquelle zurück.
- `WEBOS_SetInput`: Wechselt zur angegebenen Eingangsquelle.
- `WEBOS_SetChannel`: Wechselt zum angegebenen Kanal.
- `WEBOS_GetChannelList`: Gibt eine Liste der verfügbaren Kanäle zurück.
- `WEBOS_GetInputSourceList`: Gibt eine Liste der verfügbaren Eingangsquellen zurück.
- `WEBOS_GetSoundOutput`: Gibt die aktuelle Soundausgabe zurück.
- `WEBOS_SetSoundOutput`: Ändert die Soundausgabe (z.B. TV-Lautsprecher, externes Audio).
- `WEBOS_SendKey`: Sendet einen Tastendruck an das Gerät (z.B. "VolumeUp", "ChannelDown", "Enter").
- `WEBOS_LaunchApp`: Startet eine App auf dem Gerät, basierend auf der App-ID (z.B. "netflix" für Netflix).
- `WEBOS_CloseApp`: Schließt eine App auf dem Gerät, basierend auf der App-ID (z.B. "netflix" für Netflix).
- `WEBOS_GetAppList`: Gibt eine Liste der installierten Apps auf dem Gerät zurück.
- `WEBOS_GetCurrentApp`: Gibt die aktuell laufende App zurück.
- `WEBOS_LaunchNetflix`: Startet die Netflix App auf dem Gerät.
- `WEBOS_LaunchYouTube`: Startet die YouTube App auf dem Gerät.
- `WEBOS_CloseNetflix`: Schließt die Netflix App auf dem Gerät.
- `WEBOS_CloseYouTube`: Schließt die YouTube App auf dem Gerät.
- `WEBOS_GetDeviceInfo`: Gibt Informationen über das Gerät zurück, wie z.B. Modell, Seriennummer und Softwareversion.
- `WEBOS_Play`: Startet die Wiedergabe von Medieninhalten auf dem Gerät, basierend auf einer URL.
- `WEBOS_Pause`: Pausiert die Wiedergabe von Medieninhalten auf dem Gerät.
- `WEBOS_Stop`: Stoppt die Wiedergabe von Medieninhalten auf dem Gerät
- `WEBOS_Rewind`: Spult die Wiedergabe von Medieninhalten auf dem Gerät zurück.
- `WEBOS_FastForward`: Spult die Wiedergabe von Medieninhalten auf dem Gerät vorwärts.
- `WEBOS_DisplayMessage`: Zeigt eine benutzerdefinierte Nachricht auf dem Bildschirm des Geräts an.

Installation:
1. Modul importieren
2. Erstellen Sie in IP-Symcon eine neue Instanz des Modultyps "WebOSDevice".
3. Konfigurieren Sie die Instanz mit der IP-Adresse, der MAC-Adresse (zum Einschalten notwendig) und dem Port (3001 ist Default) Ihres LG WebOS Gerätes.
4. Speichern Sie die Konfiguration und klicken zunächst auf "Gerät registrieren", folgen Sie den Anweisungen auf dem TV-Bildschirm, um die Registrierung abzuschließen.
5. Testen Sie die Verbindung, indem Sie auf "Testverbindung" klicken. Wenn die Verbindung erfolgreich ist, sollten Sie eine Bestätigungsmeldung auf dem TV-Bildschirm erhalten.
5. Verwenden Sie die bereitgestellten Funktionen, um Ihr LG WebOS Gerät zu steuern und Informationen abzurufen.

