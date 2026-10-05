# Changelog

## 1.7.3
### Behoben
- Log-Level „Debug“: Bei ausgeschaltetem TV landete alle ~12 Sekunden „Connection … failed“ im Meldungsfenster. Die regelmäßige Statusabfrage schreibt jetzt nur noch in den Debug-Reiter; im Meldungsfenster steht nur einmal „TV nicht erreichbar“ bzw. „TV wieder erreichbar“.

## 1.7.2
### Behoben
- Kachel-Visualisierung: Die Fernbedienung ließ sich nicht bedienen und zeigte immer „aus“. Ursache war ein Namenskonflikt mit Symcons Kachel-Skript; das Skript der Fernbedienung läuft jetzt abgeschottet.
### Dokumentation
- Meldungen: Unterschied zwischen `WEBOS_Notify`, `WEBOS_DisplayMessage` (alter Name, identisch) und `WEBOS_Alert` (Hinweisfenster mit Anzeigedauer) erklärt.

## 1.7.1
- Kompakt: nutzt im WebFront (Handy) die ganze Höhe – Steuerkreuz größer, VOL/CH direkt daneben, restliche Höhe gleichmäßig verteilt; im Querformat schlank und mittig.

## 1.7
- Neue Darstellung **Kompakt**: vollflächig ohne Gehäuse, Power-Taste mittig, VOL/CH neben dem Steuerkreuz, ohne Farbtasten.
- Kachel-Visualisierung: oben bleibt Platz für Kachelnamen und Vergrößern-Symbol (Power-Taste wurde verdeckt, Name überlappte).
- **Live TV** (Antenne/Kabel/SAT) als Eintrag in der Eingangsauswahl (`WEBOS_SetInput($id, "LIVETV")`).
- **Favoriten** für Apps: neue Spalte im Formular, Favoriten erscheinen als Schnellwahl-Knöpfe.
- Formular: Namen und IDs in den Tabellen „Eingänge“/„Apps“ werden wieder angezeigt und gespeichert.
- README: Tipps zum Einschalten (Einstellung unter „Verbindung“, MAC aus dem Router).

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
