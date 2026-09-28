# LG WebOS Module für IP-Symcon

Dieses Modul steuert LG-Fernseher (und andere webOS-Geräte) über das Netzwerk aus IP-Symcon – inklusive Statusvariablen, Fernbedienung für die Kachel-Visualisierung und das WebFront sowie Meldungen auf dem TV.

Entwickelt von Daniel Schaefer ([daschaefer/SymconWebOS](https://github.com/daschaefer/SymconWebOS)). Erweiterungen ab Version 1.3 (webOS-26-Pairing, Variablen, Fernbedienung, Meldungen, Bildanzeige) von Peter Chrisben. Die Änderungen je Version stehen im [CHANGELOG](CHANGELOG.md).

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichtung der Instanz](#4-einrichtung-der-instanz)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung / Fernbedienung](#6-visualisierung--fernbedienung)
7. [Einschalten (Wake-on-LAN)](#7-einschalten-wake-on-lan)
8. [Meldungen auf dem TV](#8-meldungen-auf-dem-tv)
9. [PHP-Befehlsreferenz](#9-php-befehlsreferenz)
10. [Hinweise zu webOS 26](#10-hinweise-zu-webos-26)
11. [Fehlersuche](#11-fehlersuche)

---

## 1. Funktionsumfang

- Ein- und Ausschalten (Einschalten per Wake-on-LAN)
- Lautstärke, Stummschaltung, Lautstärke +/- (auch bei Soundbar an ARC/eARC)
- Eingänge (HDMI usw. und Live TV für Antenne/Kabel/SAT) wählen, Apps starten, Sender wechseln, Wiedergabe steuern
- Tonausgabe umschalten (TV-Lautsprecher, ARC, Optisch, Bluetooth …)
- Fernbedienungstasten (Pfeiltasten, OK, Zurück, Home, Farbtasten …)
- Zyklische Statusabfrage: Power, Lautstärke, Stumm, Eingang, aktuelle App, Sender, Tonausgabe
- Per Häkchen wählbare Variablen – schaltbar in der Visualisierung
- Fernbedienung als Kachel (Kachel-Visualisierung) und als HTMLBox (altes WebFront)
- Meldungen auf dem TV: Einblendungen (optional mit Symbol/Kamerabild) und Hinweisfenster
- Unterstützt webOS 26 (neues Pairing ohne Signatur) und ältere Geräte

## 2. Voraussetzungen

- IP-Symcon ab Version 8.1 (Kachel-Fernbedienung: HTML-SDK ab 7.1)
- LG-Gerät mit webOS im selben Netzwerk
- Am TV aktiviert: **Einstellungen → Allgemein → Geräte → TV-Verwaltung → „Mit Mobilgerät einschalten“ / „Über Wi-Fi/LAN einschalten“** (für Wake-on-LAN; Bezeichnung je nach Modell)
- Feste IP-Adresse des TVs (z. B. DHCP-Reservierung im Router)

## 3. Installation

1. In der Verwaltungskonsole unter **Kern Instanzen → Modules** das Repository hinzufügen:
   `https://github.com/daschaefer/SymconWebOS`
2. Eine neue Instanz **„WebOSDevice“** (Hersteller LG) anlegen.

**Update von Version 1.1/1.2:** Bestehende Instanzen bleiben erhalten. Nach dem Update einmal **„Gerät registrieren“** klicken (am TV bestätigen – die angefragten Rechte haben sich geändert) und **„Eingänge und Apps neu einlesen“**. Danach die gewünschten Variablen im Formular anhaken.

## 4. Einrichtung der Instanz

| Einstellung | Beschreibung |
|---|---|
| IP-Adresse | IP-Adresse des TVs |
| MAC-Adresse | MAC-Adresse des TVs (für Einschalten per Wake-on-LAN), z. B. `AA:BB:CC:DD:EE:FF`. Bei LAN-Kabel die MAC des LAN-Anschlusses, bei WLAN die des WLANs |
| Broadcast-Adresse für Einschalten | Optional. Leer = automatisch. Nur nötig, wenn Symcon in einem anderen Netz/Docker läuft, z. B. `192.168.2.255` |
| Port | `3001` (verschlüsselt, Standard). `3000` nur bei sehr alten Geräten |
| Status abfragen alle | Intervall der Statusabfrage in Sekunden, `0` = aus (Standard 10) |
| Variablen | Häkchen für die Variablen, die unter der Instanz angelegt werden sollen (siehe Kapitel 5) |
| Apps und Eingänge | Tabellen mit den vom TV gelesenen Apps/Eingängen (inkl. „Live TV“). „In Auswahl“ = erscheint in den Variablen „Eingang“/„App starten“ und in der Eingangsauswahl der Fernbedienung. „Favorit“ = erscheint als Schnellwahl-Knopf auf der Fernbedienung |
| Fernbedienung (Visualisierung) | Darstellung (mit Gehäuse, Kompakt oder vollflächig), Kachel-Fernbedienung und/oder HTMLBox fürs alte WebFront (siehe Kapitel 6) |
| Meldungen auf dem TV | Standard-Symbol für Meldungen (Medienobjekt, optional) und Symcon-Adresse für Bilder (leer = automatisch) |
| Log-Level | `Debug` schreibt zusätzlich ins Meldungsfenster. Der Debug-Reiter der Instanz zeigt immer alle Details |

**Erste Einrichtung**

1. IP- und MAC-Adresse eintragen, **Übernehmen**.
2. **Gerät registrieren** klicken und die Kopplungsabfrage am TV innerhalb von 60 Sekunden bestätigen.
3. **Eingänge und Apps neu einlesen** klicken, gewünschte Häkchen setzen, **Übernehmen**.

**Knöpfe im Formular**

| Knopf | Funktion |
|---|---|
| Gerät registrieren | Koppelt Symcon mit dem TV (Abfrage am TV bestätigen) |
| Geräteverbindung testen | Zeigt eine Testmeldung auf dem TV |
| Status jetzt abfragen | Aktualisiert alle Variablen sofort |
| Eingänge und Apps neu einlesen | Liest Eingänge und installierte Apps vom TV (Häkchen bleiben erhalten) |
| Meldung testen | Einblendung oder Hinweisfenster mit eigenem Text senden |

## 5. Statusvariablen und Profile

Alle Variablen werden per Häkchen im Formular angelegt bzw. entfernt.

| Häkchen | Ident | Name | Typ | Schaltbar | Beschreibung |
|---|---|---|---|---|---|
| Power | `Power` | Power | Boolean | ja | Ein (Wake-on-LAN) / Aus |
| Lautstärke und Stummschaltung | `Volume` | Lautstärke | Integer | ja | 0–100 (bei Soundbar an ARC meldet der TV 0) |
| | `Muted` | Stumm | Boolean | ja | Stummschaltung |
| | `VolumeStep` | Lautstärke − / + | Integer | ja | 0 = leiser, 1 = lauter (funktioniert auch mit Soundbar per CEC) |
| Eingang | `Input` | Eingang | Integer | ja | Auswahl der angehakten Eingänge, `-1` = andere Quelle |
| Aktuelle App | `AppName` | Aktuelle App | String | nein | Name der laufenden App bzw. des Eingangs |
| App starten | `AppLaunch` | App starten | Integer | ja | Auswahl der angehakten Apps |
| Fernbedienung | `RemoteKey` | Fernbedienung | Integer | ja | Hoch, Runter, Links, Rechts, OK, Zurück, Exit, Home, Einstellungen, Info, Farbtasten |
| Wiedergabe | `MediaControl` | Wiedergabe | Integer | ja | Play, Pause, Stop, Zurückspulen, Vorspulen |
| Sender | `ChannelName` | Aktueller Sender | String | nein | Nummer und Name (nur bei Live-TV) |
| | `ChannelControl` | Sender wechseln | Integer | ja | 0 = Sender −, 1 = Sender + |
| Tonausgabe | `SoundOutput` | Tonausgabe | Integer | ja | TV-Lautsprecher, HDMI ARC/eARC, Optisch, Bluetooth, Kopfhörer, TV + Extern |
| Meldung an TV | `Notification` | Meldung an TV | String | ja | Text eintragen = Einblendung am TV |
| Altes WebFront | `RemoteHTML` | Fernbedienung | String (~HTMLBox) | nein | Fernbedienung fürs WebFront |

**Profile:** `WEBOS.Volume`, `WEBOS.Mute`, `WEBOS.VolumeStep`, `WEBOS.Remote`, `WEBOS.Media`, `WEBOS.Channel`, `WEBOS.SoundOutput` sowie je Instanz `WEBOS.Input.<InstanzID>` und `WEBOS.Apps.<InstanzID>`.

## 6. Visualisierung / Fernbedienung

**Darstellung** (gilt für Kachel und WebFront):

- **Fernbedienung (mit Gehäuse)** – sieht aus wie eine echte Fernbedienung und skaliert auf die verfügbare Fläche.
- **Kompakt** – vollflächig ohne Gehäuse, platzsparend: Power-Taste mittig, VOL- und CH-Wippe links und rechts neben dem Steuerkreuz, ohne Farbtasten. Ideal für Kacheln.
- **Vollflächig (ganze Seite)** – die Bedienelemente nutzen die komplette Fläche wie eine App; das Steuerkreuz wird so groß wie der freie Platz. Ideal fürs Handy. Im Querformat wechselt die Ansicht automatisch auf zwei Spalten.

**Kachel-Visualisierung**

1. In der Instanz unter „Fernbedienung (Visualisierung)“ **„Kachel-Visualisierung: Instanz als Fernbedienung darstellen“** anhaken, Übernehmen.
2. In der Kachel-Visualisierung im Bearbeitungsmodus eine Kachel hinzufügen und die **TV-Instanz** auswählen (oder im Objektbaum einen Link auf die Instanz anlegen).
3. Kachel groß ziehen (z. B. 2 × 4 hochkant). Bei breiten Kacheln wechselt die Fernbedienung in eine zweispaltige Ansicht.

**Altes WebFront**

1. **„Altes WebFront: Variable Fernbedienung (HTMLBox) anlegen“** anhaken. **Höhe `0` = automatisch** (empfohlen): Die Fernbedienung passt sich der Breite an und wird nie höher als der Bildschirm – ideal fürs Handy im Hochformat. Ein fester Wert (z. B. `620`) setzt die Höhe in Pixel.
2. Die Variable „Fernbedienung“ im WebFront verlinken.
3. Das Modul registriert dafür den WebHook `/hook/webos<InstanzID>`. Der Hook ist wie alle Symcon-Hooks im Netzwerk erreichbar; bei Bedarf im WebHook Control Zugangsdaten setzen.

Die Fernbedienung enthält: Power, Eingangsauswahl (inkl. Live TV), Home, Einstellungen, Steuerkreuz mit OK, Zurück/Info/Exit, Lautstärke- und Senderwippe, Stumm, Wiedergabe, Farbtasten sowie Apps als Schnellwahl.

**Schnellwahl-Knöpfe (Apps):** In der Tabelle „Apps“ die Spalte **„Favorit“** anhaken – dann erscheinen nur diese Apps als Knöpfe. Ohne Favoriten werden alle Apps mit „In Auswahl“ angezeigt.

In der Kachel-Visualisierung bleibt oben ein schmaler Streifen frei, damit Kachelname und Vergrößern-Symbol nichts verdecken.

## 7. Einschalten (Wake-on-LAN)

Ein ausgeschalteter TV ist über das Netzwerk nicht erreichbar und wird per Wake-on-LAN geweckt (`WEBOS_PowerOn`, Variable „Power“, Power-Taste der Fernbedienung). Damit das auch nach längerer Zeit im Tiefschlaf zuverlässig klappt, sendet das Modul das Weck-Paket mehrfach (3 Durchgänge) an:

- die eingestellte Broadcast-Adresse (optional),
- die Broadcast-Adresse des TV-Netzes (z. B. `192.168.2.255`),
- direkt an die IP des TVs und
- `255.255.255.255`,

jeweils auf Port 9 und 7.

**Einstellungen am TV** (Bezeichnungen je nach Modell):

- **Allgemein → Geräte → TV-Verwaltung → „Mit Mobilgerät einschalten“ / „Über Wi-Fi einschalten“ bzw. „Über LAN einschalten“** aktivieren.
- Bei neueren Geräten (webOS 23 und neuer) liegt die Einstellung meist unter **Einstellungen → Alle Einstellungen → Verbindung → Einstellungen für Mobilgeräte-Verbindung → „Mit Mobilgerät einschalten“**. Am schnellsten findet man sie über die **Suche** in den Einstellungen („einschalten“).
- **MAC-Adresse aus dem Router ablesen** (Eintrag mit der IP des TVs). Die im TV-Menü angezeigte MAC kann abweichen.
- Optional **„Schnellstart+“ (Quick Start+)** aktivieren – der TV reagiert dann schneller, verbraucht im Standby aber etwas mehr Strom.

## 8. Meldungen auf dem TV

| Art | Befehl | Beschreibung |
|---|---|---|
| Einblendung | `WEBOS_Notify` | Kurze Meldung unten am Bildschirm, verschwindet nach einigen Sekunden |
| Einblendung mit Symbol | `WEBOS_NotifyIcon` | Wie oben, mit kleinem Bild (Medienobjekt, Datei oder URL, PNG/JPG). Wird automatisch verkleinert |
| Bild groß anzeigen | `WEBOS_ShowImage` | Zeigt ein Bild (z. B. Kamera-Snapshot) bildschirmfüllend im Browser des TVs, optional mit Text. Das Bild aktualisiert sich alle 2 Sekunden. Nach X Sekunden wechselt der TV zurück zur vorherigen App bzw. zum vorherigen Eingang |
| Hinweisfenster | `WEBOS_Alert` | Fenster mit Titel, Text und OK-Knopf; schließt sich optional nach X Sekunden (max. 60) |
| Variable | `Notification` | Text in die Variable „Meldung an TV“ schreiben (z. B. aus einem Ablaufplan) |

**Hinweis zu Bildern:** webOS 26 zeigt Einblendungen mit Symbol (`WEBOS_NotifyIcon`) nicht an – das Modul schickt dann automatisch nur den Text. Für Bilder daher `WEBOS_ShowImage` verwenden.

**So funktioniert `WEBOS_ShowImage`:** Das Modul stellt das Bild über seinen WebHook (`/hook/webos<InstanzID>`) bereit und öffnet diese Seite im Browser des TVs. Der TV muss Symcon dafür erreichen können: Die Adresse wird automatisch ermittelt (IP von Symcon, Port 3777). Läuft Symcon z. B. in Docker oder auf einem anderen Port, die Adresse im Formular unter „Symcon-Adresse für Bilder“ eintragen (z. B. `http://192.168.2.10:3777`). Hat das WebHook Control Zugangsdaten, fragt der TV-Browser danach. Das laufende Programm wird für die Anzeige unterbrochen.

Ist ein **Standard-Symbol** eingestellt, wird es bei `WEBOS_Notify` und der Variable automatisch mitgeschickt. Meldungen erscheinen nur, wenn der TV eingeschaltet ist.

## 9. PHP-Befehlsreferenz

`$id` ist jeweils die ID der WebOSDevice-Instanz. Befehle, die Werte abfragen, liefern die Antwort des TVs als Objekt zurück (bzw. `null`, wenn der TV nicht erreichbar ist).

### Einrichtung und Status

```php
WEBOS_RegisterDevice(int $id);          // Koppeln (Abfrage am TV bestätigen)
WEBOS_Test(int $id);                    // Testmeldung auf dem TV
WEBOS_Update(int $id);                  // Status abfragen und Variablen aktualisieren
WEBOS_RefreshLists(int $id);            // Eingänge und Apps neu einlesen
WEBOS_GetPowerState(int $id);           // Antwort des TVs, z. B. payload->state = "Active"
WEBOS_GetSystemInfo(int $id);           // Systeminfos (auf webOS 26 evtl. nicht erlaubt)
```

### Ein/Aus

```php
WEBOS_PowerOn(int $id);                 // Wake-on-LAN, mehrfach an alle Broadcast-Ziele (MAC-Adresse nötig)
WEBOS_PowerOff(int $id);
```

### Lautstärke und Ton

```php
WEBOS_VolumeUp(int $id);
WEBOS_VolumeDown(int $id);
WEBOS_SetVolume(int $id, int $Value);   // 0–100 (nicht bei Soundbar an ARC/eARC)
WEBOS_GetVolume(int $id);               // Lautstärke als Zahl, 0 wenn stumm
WEBOS_Mute(int $id);                    // Stummschaltung umschalten
WEBOS_SetMute(int $id, bool $Value);    // true = stumm, false = Ton an
WEBOS_GetAudioStatus(int $id);
WEBOS_GetSoundOutput(int $id);          // payload->soundOutput, z. B. "external_arc"
WEBOS_SetSoundOutput(int $id, string $Value); // "tv_speaker", "external_arc", "external_optical", "bt_soundbar", "headphone"
```

### Eingänge, Apps, Sender

```php
WEBOS_SetInput(int $id, string $Value);   // Eingangs-ID, z. B. "HDMI_1" oder "LIVETV" für Antenne/Kabel/SAT (siehe Tabelle „Eingänge“)
WEBOS_GetInputList(int $id);
WEBOS_LaunchApp(int $id, string $Value);  // App-ID, z. B. "netflix" (siehe Tabelle „Apps“ im Formular)
WEBOS_CloseApp(int $id, string $Value);
WEBOS_LaunchNetflix(int $id);
WEBOS_CloseNetflix(int $id);
WEBOS_LaunchYouTube(int $id);
WEBOS_CloseYouTube(int $id);
WEBOS_GetCurrentApp(int $id);             // payload->appId der laufenden App
WEBOS_GetAppList(int $id);                // installierte Apps (payload->launchPoints)
WEBOS_ChannelUp(int $id);
WEBOS_ChannelDown(int $id);
WEBOS_SetChannel(int $id, string $Value); // Sendernummer, z. B. "2"
WEBOS_GetChannelList(int $id);
```

### Fernbedienung und Wiedergabe

```php
WEBOS_SendButton(int $id, string $Name);  // Taste senden, siehe Liste unten
WEBOS_Play(int $id);
WEBOS_Pause(int $id);
WEBOS_Stop(int $id);
WEBOS_Rewind(int $id);
WEBOS_FastForward(int $id);
WEBOS_SendKey(int $id, string $Value);    // alte Methode (IME), bevorzugt WEBOS_SendButton verwenden
```

Tasten für `WEBOS_SendButton`: `UP`, `DOWN`, `LEFT`, `RIGHT`, `ENTER`, `BACK`, `EXIT`, `HOME`, `MENU`, `INFO`, `RED`, `GREEN`, `YELLOW`, `BLUE`, `0`–`9`, `VOLUMEUP`, `VOLUMEDOWN`, `MUTE`, `CHANNELUP`, `CHANNELDOWN`, `PLAY`, `PAUSE`, `STOP`, `REWIND`, `FASTFORWARD`, `GUIDE`, `LIST`, `QMENU`

### Meldungen

```php
WEBOS_Notify(int $id, string $Message);
WEBOS_NotifyIcon(int $id, string $Message, string $Icon); // Icon: Medien-ID, Dateipfad oder URL
WEBOS_Alert(int $id, string $Title, string $Message, int $Seconds); // 0 = bleibt bis OK
WEBOS_ShowImage(int $id, string $Image, string $Text, int $Seconds); // Bild groß im TV-Browser, 0 = bleibt bis „Zurück“ (max. 600)
WEBOS_ReturnFromImage(int $id);                                      // Bildanzeige beenden, zurück zur vorherigen App/Quelle
WEBOS_DisplayMessage(int $id, string $Value);             // wie WEBOS_Notify (ältere Bezeichnung)
```

### Beispiele

```php
<?php
$tv = 12345; // ID der WebOSDevice-Instanz

// Türklingel: Kamerabild (Medienobjekt 23456) 20 Sekunden groß zeigen, danach zurück
WEBOS_ShowImage($tv, '23456', 'Es hat an der Haustür geklingelt', 20);

// Waschmaschine fertig: Hinweisfenster, schließt nach 15 Sekunden
WEBOS_Alert($tv, 'Waschmaschine', 'Die Wäsche ist fertig.', 15);

// Kino-Szene: TV an, HDMI 3, Netflix
WEBOS_PowerOn($tv);
IPS_Sleep(8000);                 // TV hochfahren lassen
WEBOS_SetInput($tv, 'HDMI_3');
WEBOS_LaunchApp($tv, 'netflix');

// Im Menü navigieren
foreach (['DOWN', 'DOWN', 'ENTER'] as $key) {
    WEBOS_SendButton($tv, $key);
}

// Nur wenn der TV läuft: Status per Variable abfragen
if (GetValue(IPS_GetObjectIDByIdent('Power', $tv))) {
    WEBOS_Notify($tv, 'Gute Nacht!');
}
```

## 10. Hinweise zu webOS 26

- Ab webOS 26 lehnen LG-Geräte die bisher verwendete signierte Registrierung ab („403 Pairing rejected: blacklisted certificate detected“) bzw. vergeben damit nur Grundrechte.
- Das Modul registriert sich deshalb zuerst **ohne Signatur**; am TV erscheint die normale Kopplungsabfrage. Lehnt ein älteres Gerät das ab, wird automatisch die bisherige signierte Registrierung verwendet.
- Ohne Signatur vergibt der TV einige geschützte Rechte nicht mehr (z. B. Bildeinstellungen ändern). Die Funktionen dieses Moduls sind davon nicht betroffen.
- Nach einem Modul-Update, das die angefragten Rechte ändert, kann die Kopplungsabfrage am TV einmal erneut erscheinen.

## 11. Fehlersuche

| Problem | Lösung |
|---|---|
| Kopplungsabfrage erscheint nicht | Port 3001 prüfen, TV eingeschaltet? Im Debug-Reiter der Instanz die Antwort ansehen |
| App-Liste leer / „401 insufficient permissions“ | **Gerät registrieren** erneut ausführen und am TV bestätigen, danach **Eingänge und Apps neu einlesen** |
| Einschalten klappt nicht (vor allem nach längerer Zeit) | MAC-Adresse prüfen (LAN oder WLAN, je nach Verbindung), TV-Einstellung „Mit Mobilgerät / Über Wi-Fi einschalten“ aktivieren, ggf. Broadcast-Adresse eintragen. Im Debug-Reiter steht, wohin die Pakete gesendet wurden |
| Bild wird nicht angezeigt (`WEBOS_ShowImage`) | Im Debug-Reiter steht die geöffnete Adresse. Diese im Browser eines anderen Geräts im Heimnetz testen; ggf. „Symcon-Adresse für Bilder“ eintragen |
| Lautstärke bleibt 0 | Ton läuft über ARC/eARC (Soundbar) – dann `VolumeStep` bzw. Lauter/Leiser verwenden |
| Fernbedienungs-Kachel zeigt Variablen | Visualisierung neu laden, Häkchen „Instanz als Fernbedienung darstellen“ prüfen |
