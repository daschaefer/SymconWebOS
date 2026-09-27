# Changelog

## 1.6.1
- Vollflächige Fernbedienung wird auf Handy-Größe umgerechnet und skaliert (iOS-WebFront zeigte sie sonst sehr klein).

## 1.6
- `WEBOS_ShowImage`: Bild (Medienobjekt, Datei oder URL, z. B. Kamera-Snapshot) bildschirmfüllend im Browser des TVs anzeigen, mit Text, Aktualisierung alle 2 s und automatischer Rückkehr zur vorherigen App/Quelle (`WEBOS_ReturnFromImage`).
- Einstellung „Symcon-Adresse für Bilder“ (leer = automatisch).

## 1.5.1
- `WEBOS_NotifyIcon`: Lehnt der TV die Meldung mit Symbol ab (webOS 26 antwortet nicht), wird automatisch nur der Text geschickt. Kürzere Debug-Ausgabe.

## 1.5
- Fernbedienung: Darstellung wählbar – „Fernbedienung (mit Gehäuse)“ oder „Vollflächig (ganze Seite)“.
- WebFront: Höhe 0 = automatisch (füllt den freien Bildschirm), Apps als wischbare Zeile.

## 1.4.1
- Alle öffentlichen Funktionen mit festen Parametertypen (behebt Warnung beim Modul-Update).

## 1.4
- Meldungen auf dem TV: `WEBOS_Notify`, `WEBOS_NotifyIcon`, `WEBOS_Alert`, Variable „Meldung an TV“, Standard-Symbol, Test im Formular.
- Wake-on-LAN robuster: mehrere Pakete an Broadcast des TV-Netzes, TV-IP und 255.255.255.255 (Port 9 und 7), optionale Broadcast-Adresse.
- README komplett überarbeitet inkl. PHP-Befehlsreferenz und Beispielen.

## 1.3
- Registrierung zuerst ohne Signatur (webOS 26), Fallback auf die signierte Registrierung für ältere Geräte.
- Per Häkchen wählbare, schaltbare Variablen: Power, Lautstärke, Stumm, Lautstärke −/+, Eingang, aktuelle App, App starten, Fernbedienung, Wiedergabe, Sender, Tonausgabe.
- Zyklische Statusabfrage (Intervall einstellbar).
- Eingänge und Apps werden vom TV gelesen, Auswahl per Häkchen im Formular.
- Fernbedienungstasten über den Pointer-Socket (`WEBOS_SendButton`).
- Fernbedienung als Kachel (HTML-SDK) und für das alte WebFront (HTMLBox + WebHook).
- Neue Kommunikation: vollständiges WebSocket-Framing (große Antworten, Ping), Antworten per ID.
- Fehlerbehebungen: Stummschaltung, Profile, Warnungen beim Übernehmen.

## 1.2
- Pairing mit webOS 26: Fallback auf die unsignierte Registrierung („403 blacklisted certificate“).
