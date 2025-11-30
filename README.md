# BountyOps SaaS Platform

> Eine umfassende, cloudbasierte Lösung zur Verwaltung und Durchführung von Live-Action-Verfolgungsjagd-Spielen mit GPS-Tracking, Echtzeit-Kommunikation und Production-Features.

## Inhaltsverzeichnis

- [Projektübersicht](#projektübersicht)
- [Vision & Ziele](#vision--ziele)
- [Systemarchitektur](#systemarchitektur)
- [Technologie-Stack](#technologie-stack)
- [Rollen & Berechtigungen](#rollen--berechtigungen)
- [Kern-Features](#kern-features)
- [Detaillierte Feature-Beschreibungen](#detaillierte-feature-beschreibungen)
- [API & Integrationen](#api--integrationen)
- [DSGVO & Compliance](#dsgvo--compliance)
- [Datenmodell](#datenmodell)
- [WebSocket-Architektur](#websocket-architektur)
- [Implementierungs-Roadmap](#implementierungs-roadmap)

---

## Projektübersicht

**BountyOps SaaS Platform** ermöglicht es Organisationen, komplexe BountyOps-Events durchzuführen, bei denen Spieler (Runner) für eine definierte Zeit (typisch 96 Stunden) einer professionellen Jäger-Taskforce (Hunter) zu entkommen versuchen.

### Kernkonzept

Die Spielleitung überwacht und steuert das Spiel über eine zentrale Kommandozentrale mit:
- Echtzeit-Kartenansicht aller Teilnehmer
- Event-Management und Regelwerk-Konfiguration
- Kommunikations-Tools
- Video- und Streaming-Integration
- Umfangreiche Analytics und Replay-Funktionen

### Hauptmerkmale

- **Multi-Tenancy**: Verschiedene Organisationen können unabhängige Spiele hosten
- **Echtzeit-Tracking**: GPS-basierte Live-Verfolgung mit Multi-Tracker-Support
- **Dynamische Regelwerke**: Vollständig konfigurierbare Spielmechaniken
- **Production-Ready**: Timeline-Replay, Video-Integration, Datenexport
- **Automatisierung**: KI-gestützte Event-Erkennung und Anomalie-Detection
- **Skalierbar**: Unbegrenzte gleichzeitige Spiele, flexible Teilnehmerzahlen
- **Kostenlos**: Keine Monetarisierung, komplett Open-Source-Ansatz

---

## Vision & Ziele

### Vision
Eine universelle Plattform zu schaffen, die es jedem ermöglicht, professionelle BountyOps-Events durchzuführen - von kleinen Community-Spielen bis hin zu großen, produzierten YouTube-Serien.

### Hauptziele

1. **Vollständige Spielverwaltung**: Alle Aspekte eines Manhunt-Spiels in einer Plattform
2. **Production-Excellence**: Tools für professionelle Video-Produktion und Live-Streaming  
3. **Flexibilität**: Anpassbar an verschiedene Spielformate und Regelwerke
4. **Sicherheit**: Umfassende Sicherheits- und Notfall-Features
5. **Datenintegrität**: Lückenlose Dokumentation aller Ereignisse
6. **Benutzerfreundlichkeit**: Intuitive Interfaces für alle Rollen

---

## Systemarchitektur

### Multi-Tenancy-Modell

```
┌─────────────────────────────────────────────────────────┐
│                    Super-Admin-Ebene                    │
│  (Plattform-Administration, Support, Fehlerbehandlung)  │
└──────────────────────────┬──────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
┌───────▼────────┐  ┌──────▼───────┐   ┌──────▼───────┐
│ Organisation A │  │ Organisation │   │ Organisation │
│(BountyOps Media)│ │  B (Gaming)  │   │  C (Events)  │
└───────┬────────┘  └──────┬───────┘   └──────┬───────┘
        │                  │                  │
    ┌───┴───┌───────┐  ┌───┴───┐───────┐  ┌───┴───┐
    │Spiel 1│Spiel 2│  │Spiel 3│Spiel 4│  │Spiel 5│
    └───────┴───────┘  └───────┴───────┘  └───────┘
```

### Ebenen-Hierarchie

1. **Super-Admin**: Plattform-weite Verwaltung, Support, Eingriffsmöglichkeiten
2. **Organisation**: Isolierte Tenant mit eigenen Benutzern, Spielen, Einstellungen
3. **Spiel**: Einzelne Manhunt-Events mit spezifischen Regeln und Teilnehmern
4. **Benutzer**: Personen mit Rollen innerhalb von Spielen

### Daten-Isolation

- Jede Organisation hat eine komplett isolierte Datenbank-Partition
- Organisationen können nicht auf Daten anderer Organisationen zugreifen
- Super-Admins können bei Bedarf auf alle Organisationen zugreifen (Support)
- Audit-Logs dokumentieren alle Super-Admin-Zugriffe

---

## Technologie-Stack

### Backend

- **Framework**: Laravel 12 (PHP 8.4)
- **Datenbank**: PostgreSQL mit PostGIS (räumliche Queries)
- **Cache**: Redis (Echtzeit-Daten, WebSocket-State)
- **Queue**: Redis Queue (asynchrone Tasks)
- **WebSockets**: Laravel Reverb / Soketi
- **Storage**: AWS S3 (Videos, Dokumente, Exports)

### Frontend

- **Framework**: Filament v4 (Admin-Panels)
- **Real-time UI**: Livewire v3
- **Mapping**: Leaflet.js / Mapbox GL JS
- **Charts**: Chart.js / Apache ECharts
- **PWA**: Service Workers für Offline-Fähigkeit

### Infrastructure

- **Hosting**: AWS Lambda (Serverless)
- **CDN**: CloudFront
- **DNS**: Route 53
- **Monitoring**: CloudWatch + NightWatch
- **CI/CD**: GitHub Actions

### Externe Integrationen

- **GPS-Tracker**: Garmin, Spot, Trackimo, Custom APIs
- **Banking**: Revolut API, Bunq API
- **Maps**: Mapbox, OpenStreetMap
- **Weather**: OpenWeatherMap API
- **PDF**: DomPDF / Browsershot
- **Video**: AWS MediaConvert (Transcoding)

### AWS Lambda Constraints

- **Payload-Limit**: 4 MB (Upload/Download)
- **Strategie**: Chunked Uploads für große Dateien via Pre-signed S3 URLs
- **Video-Upload**: Direkt vom Client zu S3 (Multipart-Upload)

---

## Rollen & Berechtigungen

### 1. Super-Admin
- **Zugriff**: Plattform-weit
- **Berechtigungen**: Alle Organisationen verwalten, technischer Support, System-Einstellungen
- **Einschränkungen**: Nur bei Support-Anfragen in Spielabläufe eingreifen

### 2. Organisations-Admin
- **Zugriff**: Eigene Organisation
- **Berechtigungen**: Organisationseinstellungen, Benutzer verwalten, Spiele erstellen/löschen

### 3. Spielleitung (Game Master)
- **Zugriff**: Zugewiesene Spiele
- **Berechtigungen**:
  - Vollständige Spielkontrolle (Start, Pause, Ende)
  - Teilnehmerverwaltung und Rollenzuweisung
  - Kartenverwaltung (Zonen erstellen, ändern, löschen)
  - Event-Management (Szenarien, Challenges, Speedhunts)
  - Joker-Verwaltung (definieren, zuweisen, entziehen)
  - Kommunikation (alle Chats lesen, ändern, löschen)
  - Regelverstöße bearbeiten, Strafen verhängen

### 4. Spielleitung-Assistent
- **Zugriff**: Zugewiesene Spiele (Read-Only+)
- **Berechtigungen**: Alle Daten einsehen, Events dokumentieren, Chats senden
- **Einschränkungen**: Keine Änderungen an Spieleinstellungen, keine Entscheidungsgewalt

### 5. Runner (Spieler)
- **Zugriff**: Eigenes Spielerprofil
- **Berechtigungen**: Eigene Position sehen, Joker nutzen, Chatten, Video hochladen
- **Sichtbarkeit**: Nur für Runner sichtbare Zonen, keine Hunter-Daten

### 6. Hunter
- **Zugriff**: Hunter-spezifische Daten
- **Berechtigungen**: Runner-Pings empfangen, Hunter-Team-Chat, Strategies erstellen
- **Sichtbarkeit**: Nur Ping-Positionen (nicht live), optional Predictive Analytics

### 7. Hunter-Koordinator
- **Zugriff**: Alle Hunter-Daten
- **Berechtigungen**: Hunter-Teams erstellen, Strategien verwalten, Ressourcen koordinieren

### 8. Security / Begleitschutz
- **Zugriff**: Zugewiesene Spieler
- **Berechtigungen**: Echtzeit-Position der zugewiesenen Spieler, Notfall-Button, Chat mit Spielleitung
- **Einschränkungen**: Keine Spielinformationen (Joker, Budget, Hunter-Daten)

### 9. Zuschauer / Produktion
- **Zugriff**: Spiel (verzögert)
- **Berechtigungen**: Karte mit Delay, Event-Timeline, Statistiken
- **Einschränkungen**: Keine Echtzeit-Daten, keine Interaktion

### 10. Director (Produktion)
- **Zugriff**: Alle Produktionsdaten (Echtzeit)
- **Berechtigungen**: Multi-Kamera-View, Highlights markieren, Timestamps setzen, Export-Funktionen
- **Einschränkungen**: Keine Spieleingriffe, keine Chat-Nachrichten senden

---

## Kern-Features

### 1. Spiel-Management
- Spiele erstellen, konfigurieren, archivieren
- Spielphasen-Verwaltung (Setup, Pre-Game, Active, Final Sprint, Endgame, Post-Game, Archive)
- Mehrere Spiele parallel pro Organisation
- Spiel-Templates und Presets (Wien, Bangkok, New York)
- Klonen von Spielen mit Einstellungen
- Spieler können nur in einem aktiven Spiel gleichzeitig sein

### 2. Regelwerk-Konfiguration
- Vollständig anpassbare Spielregeln
- Zeitbasierte Regel-Änderungen
- Bedingte Regeln (z.B. wetterabhängig)
- Import/Export von Regelwerken
- Versionierung von Regeländerungen
- Preset-Templates (Wien, Bangkok, New York)

### 3. Echtzeit-Tracking
- Multi-Tracker-Support pro Spieler (Redundanz)
- GPS-Datenfusion mit Konfidenz-Scoring
- Genauigkeits-Visualisierung
- Offline-Detection und Last-Known-Position
- Tracking-History mit Playback
- WebApp-Tracking über Browser Geolocation API
- Anomalie-Erkennung (GPS-Spoofing, unmögliche Geschwindigkeiten)

### 4. Kartenoberfläche
- Interaktive 2D/3D-Karten (Mapbox GL JS)
- Zonen-Management (Spielfeld, Restricted Areas, Safe Zones, Exit Points)
- Dynamische Zone-Änderungen während des Spiels (Spielfeld-Verkleinerung)
- Layer-System (Zonen, Tracks, Heatmaps, POIs, Weather, 3D Buildings)
- Indoor-Mapping Support
- Custom Map Overlays (Notizen, Fotos, Strategien)
- Wegpunkt-System für Runner

### 5. Event-System
- Automatische Event-Erkennung (Proximity, Zone-Violations, Tracking-Anomalien)
- Manuelle Event-Erstellung
- Event-Timeline mit Filterung und Suche
- Event-basierte Aktionen (Webhooks, Notifications, Regel-Trigger)
- Szenario-Builder (Proximity-Alerts, Transaction-Triggers, etc.)
- Regelwerk-Enforcement mit GM-Review-Queue

### 6. Kommunikation
- In-App Echtzeit-Chat (WebSocket)
- Gruppen-Chats mit Rollen-basierter Zuordnung
- Push-Notifications (Web, Mobile)
- Spielleitung-Moderation (Lesen, Schreiben, Ändern, Löschen)
- Chat-Export und Archivierung
- Rich-Media-Support (Text, Bilder, Locations, Files)
- @Mentions und Reactions

### 7. Joker-System
- Umfangreich konfigurierbare Joker (Zeitraum, Nutzungsanzahl, Effekte)
- Standard-Joker (Regeneration, Hunter Location Query, Immunity, Fake Ping, Hotel-Bonus)
- Custom Jokers per Spiel
- Shared Jokers für Teams
- Joker-Marketplace (optional, handelbar)
- Zeitbasierte Freischaltung
- Joker-Transfer mit GM-Approval

### 8. Transaktions-Tracking
- Banking-API-Integration (Revolut, Bunq)
- Automatisches Transaction-Tracking via Webhooks
- Budget-Management mit Daily Reset
- Hunter-Benachrichtigung bei Käufen (konfigurierbar)
- Budget-Violations mit GM-Review
- Transaction-Analytics und Heatmaps

### 9. Video & Streaming
- Video-Upload zu S3 (Chunked Upload, Pre-signed URLs)
- Live-Stream-Integration (YouTube, Twitch, RTMP)
- Automatische Timestamp-Generierung bei Events
- Multi-Kamera-View für Produktion
- Footage-Requirement-Tracking (z.B. 35h/Tag)
- Video-Metadata mit Event-Tagging
- Export für Editing-Software (Final Cut Pro, Premiere)

### 10. KI & Automation
- Automatische Event-Erkennung (Proximity, Anomalien, Violations)
- GPS-Datenfusion-Algorithmus
- Anomalie-Erkennung (GPS-Spoofing, Dead-Man-Switch)
- Optional: Predictive Analytics für Hunter (konfig urierbar)
- Automatische Regelwerk-Enforcement (mit GM-Approval)
- Smart Notifications basierend auf Kontext

### 11. Sicherheit & Notfälle
- Panic-Button für alle Teilnehmer
- Dead-Man-Switch (Reaktions-Monitoring)
- Geofencing-Alarme (Restricted/Danger Areas)
- Security-Role mit Live-Tracking zugewiesener Spieler
- Emergency-Dashboard für Spielleitung
- Kein automatischer Notruf (manuell durch GM)

### 12. Hunter-Features
- Hunter-Teams mit Koordinator-Role
- Strategie-Planung auf Karte (Routen zeichnen)
- Equipment-Tracking und Transfer
- Live-Koordination zwischen Huntern
- Silenthunt-Pings (zeitgesteuert)
- Speedhunt-Mode (intensive Verfolgung)
- Optional: Predictive Analytics

### 13. Challenge-System
- Challenges von Spielleitung vordefiniert
- Challenge-Zonen auf Karte
- Teilnehmer-Auswahl (welche Runner)
- Zeitfenster und Regeln
- Challenge-Status-Tracking
- Modifizierte Regeln während Challenge

### 14. Team-Management
- Team-Bildung durch Spielleitung (an/aus schaltbar)
- Runner-Teams und Hunter-Teams
- Temporäre Allianzen
- Team-Chat (von GM steuerbar)
- Shared Jokers für Teams
- Team-Statistiken

### 15. Production-Features
- Timeline-Replay (zu jedem Zeitpunkt zurückspringen)
- Multi-Kamera-View mit Switching
- Event-Timeline mit Highlight-Marker
- Delayed Live-View für öffentlichen Stream (konfigurierbar Delay)
- Censor-Funktion (sensible Daten ausblenden)
- Export für Post-Production
- Director-Mode für Live-Produktion

### 16. Weather & Environment (Optional)
- Wetterdaten-Integration (OpenWeatherMap API)
- Wetter-Layer auf Karte
- Dämmerung/Sonnenauf-/untergang anzeigen
- Wetterbasierte Regel-Anpassungen

### 17. Gamification
- Achievement-System (Badges für Leistungen)
- Leaderboards (Global, pro Organisation)
- Persönliche Statistiken (Bestzeiten, Distanzen, etc.)
- Post-Game-Reports mit umfassenden Analytics

### 18. Practice & Testing
- Test-Spiele ohne Auswirkung auf Statistiken
- Sandbox-Mode für Spielleitung (Regelwerk testen)
- Pre-Flight-Checklists vor Spielstart

---

## Detaillierte Feature-Beschreibungen

### Spielflow & Phasen

Ein Manhunt-Spiel durchläuft mehrere definierte Phasen:

#### Phase 1: Setup (Vorbereitung)
**Dauer**: Variabel (Tage/Wochen vor Start)

**Aktivitäten**:
- Spiel erstellen und Grunddaten eingeben
- Regelwerk konfigurieren oder Template wählen
- Teilnehmer einladen und Rollen zuweisen
- Karte auswählen und Spielfeld definieren
- Zonen erstellen (Start-Zonen, Exit-Points, Restricted Areas)
- Joker definieren und Spielern zuweisen
- Challenges vorbereiten
- Tracking-Geräte zuweisen und testen
- Banking-Accounts verknüpfen
- DSGVO-Dokumente generieren

**Pre-Flight-Checklist**:
- ✓ Alle Teilnehmer haben Tracking-Geräte
- ✓ DSGVO-Dokumente unterschrieben
- ✓ Mindestens 1 Runner und 1 Hunter
- ✓ Spielfeld definiert
- ✓ Exit-Point(s) gesetzt
- ✓ Banking-Integration getestet

#### Phase 2: Pre-Game (Unmittelbar vor Start)
**Dauer**: 1-24 Stunden vor Start

**Aktivitäten**:
- System-Checks (GPS, WebSocket, Banking-APIs)
- Tracking-Geräte aktivieren und Position verifizieren
- Test-Notifications an alle Teilnehmer
- Video-Equipment-Checks
- Finale Regelwerk-Briefing

#### Phase 3: Active (Spiel läuft)
**Dauer**: Spieldauer (z.B. 96 Stunden)

**Hauptaktivitäten**:
- Echtzeit-Tracking aller Teilnehmer
- Automatische Silenthunt-Pings
- Speedhunts auslösen (automatisch/manuell)
- Events monitoren und verarbeiten
- Regelverstöße bearbeiten
- Challenges starten
- Täglicher Reset (06:00 Uhr):
  - Budget auffrischen
  - Spielfeld verkleinern
  - Tages-Joker freischalten
  - Fortifications zurücksetzen

**Automatische Prozesse**:
- GPS-Daten-Fusion alle 10-30 Sekunden
- Proximity-Checks alle 30 Sekunden
- Geofencing-Checks bei Positionsänderung
- Transaction-Webhooks in Echtzeit
- Dead-Man-Switch alle 5 Minuten

#### Phase 4: Final Sprint
**Dauer**: Letzte 6-12 Stunden

**Besonderheiten**:
- Erhöhte Speedhunt-Frequenz
- Spielfeld auf Minimum verkleinert
- Exit-Point wird sichtbar
- Countdown-Notifications (6h, 3h, 1h, 30min, 15min)

#### Phase 5: Endgame
**Zeitpunkt**: Letzter Tag um 06:00-06:15 Uhr

**Ablauf**:
- Runner müssen Exit-Point erreichen
- System stoppt exakt bei 06:15 Uhr
- Gewinner-Ermittlung automatisch

#### Phase 6: Post-Game
**Dauer**: Unbegrenzt

**Aktivitäten**:
- Tracking deaktivieren
- Statistiken berechnen
- Gewinner-Announcements
- Disputes bearbeiten
- Video-Footage sammeln
- Post-Game-Report erstellen
- Replay jederzeit verfügbar

---

### GPS & Tracking System

#### Multi-Tracker-Architektur

**Jeder Spieler kann mehrere Tracking-Geräte nutzen**:
```
Runner #3
├── Tracker 1: Garmin InReach (Primary)
├── Tracker 2: Smartphone WebApp (Secondary)
├── Tracker 3: Trackimo Device (Tertiary)
└── Fused Position: 40.7580° N, 73.9855° W (±5m, 95% Confidence)
```

**Vorteile**:
- Redundanz bei Geräteausfall
- Höhere Genauigkeit durch Datenfusion
- Verschiedene Technologien (GPS, GLONASS, Galileo, WiFi, Cell)

#### Unterstützte Tracking-Provider

**Hardware GPS-Tracker**:
- Garmin InReach (Satellite)
- Spot Gen4 (Satellite)
- Trackimo (GPS + Cell + WiFi)
- Custom GPS-Geräte via API

**Software-Tracking**:
- WebApp (Browser Geolocation API)
- Progressive Web App (PWA) mit Background-Tracking
- Zukünftig: Native Mobile Apps

#### GPS-Datenfusion-Algorithmus

**Ziel**: Eine präzise, zuverlässige Position aus mehreren Quellen

**Schritte**:

1. **Daten-Sammlung** von allen Trackern
2. **Validierung**:
   - Zeitstempel-Check (> 5 Min = ignorieren)
   - Plausibilitäts-Check (> 200 km/h = Outlier)
   - Genauigkeits-Check (±500m = unsicher)
3. **Gewichtung**:
   ```
   Weight = (1 / accuracy) × freshness_factor × device_trust_factor
   ```
4. **Fusion** (Weighted Average):
   ```
   Latitude = Σ(lat_i × weight_i) / Σ(weight_i)
   Longitude = Σ(lng_i × weight_i) / Σ(weight_i)
   ```
5. **Confidence-Berechnung**:
   ```
   Confidence = f(tracker_count, agreement, accuracy)
   ```

**Ausgabe**:
```json
{
  "runner_id": 3,
  "position": {
    "lat": 40.7580,
    "lng": -73.9855,
    "accuracy": 5,
    "confidence": 95
  },
  "timestamp": "2024-07-15T14:23:45Z",
  "sources": [
    {"tracker_id": 1, "weight": 0.49},
    {"tracker_id": 2, "weight": 0.30},
    {"tracker_id": 3, "weight": 0.21}
  ]
}
```

#### Anomalie-Erkennung

**Erkennung von**:
- GPS-Spoofing (Position springt > 10km in < 1min)
- Tracking-Unterbrechung (keine Daten > 10min)
- Unplausible Geschwindigkeit (> 150 km/h ohne Flugzeug)
- Geofencing-Violations

**Aktionen**:
1. Alert an Spielleitung
2. Markierung auf Karte
3. Last-Known-Good-Position verwenden
4. Alternative Tracker stärker gewichten
5. Notification an Spieler

#### Tracking-Modi

1. **Silenthunt**: Pings zu definierten Zeiten (z.B. 1x/Stunde)
2. **Speedhunt**: Häufige Updates (z.B. alle 5min, 4 Queries max)
3. **Live-Tracking**: Nur für GM/Production (alle 10-30s)
4. **Vehicle-Tracking**: Bei ÖPNV-Nutzung (1-Min-Live)

#### Genauigkeits-Visualisierung

Positionen mit Konfidenz-Indikatoren:
- 🟢 Excellent (< 10m, > 90%)
- 🟡 Good (10-50m, 70-90%)
- 🟠 Fair (50-100m, 50-70%)
- 🔴 Poor (> 100m, < 50%)

---

### Kartenoberfläche & Zonen-Management

#### Karten-Provider

**Standard**: Mapbox GL JS
- Vektorkarten (schnell, skalierbar)
- 3D-Gebäude-Support
- Custom Styling
- Offline-Tiles

**Alternative**: OpenStreetMap + Leaflet.js

**Indoor**: MapsIndoors oder Custom (Shopping Malls, Flughäfen)

#### Zonen-Typen

**1. Spielfeld (Play Area)**:
- Bereich für erlaubte Bewegung
- Multi-Polygon-Support
- Violation-Actions konfigurierbar

**2. Sub-Zonen**:
- Verschiedene Tracking-Frequenzen
- Z.B. "Manhattan" (1h Pings) vs "Outer Boroughs" (2h Pings)

**3. Restricted Areas**:
- Verbotene Bereiche
- Automatische Benachrichtigung bei Verstoß
- Optional: Sofortige Disqualifikation

**4. Safe Zones**:
- Temporär sichere Bereiche für Runner
- Hunter dürfen nicht hinein
- Zeitlich begrenzt (z.B. max 2h)

**5. Fortification Zones**:
- Verstecke (max 2h am Stück)
- Nach 8h nicht mehr zugänglich

**6. Challenge Zones**:
- Nur während Challenge aktiv
- Modifizierte Regeln

**7. Exit Points**:
- Finale Zielpunkte
- Meist erst am letzten Tag sichtbar
- 50m Acceptance-Radius

#### Dynamische Zonen-Änderungen

**Spielfeld-Verkleinerung** (Beispiel):
```
Tag 1: Manhattan + Brooklyn + Queens (~800 km²)
Tag 2: Manhattan + Brooklyn (~150 km²)
Tag 3: Manhattan Only (~60 km²)
Tag 4: Midtown (~10 km²)
```

**Automatischer Ablauf**:
1. 24h vorher: Notification
2. Um 06:00: Zonen-Wechsel
3. Neue Boundaries hervorheben
4. Spieler außerhalb: Warnung + Grace Period

#### Sichtbarkeits-Regeln

| Zone-Typ | Spielleitung | Runner | Hunter | Zuschauer |
|----------|--------------|---------|---------|-----------|
| Spielfeld | ✅ | ✅ | ✅ | ✅ (Delay) |
| Restricted | ✅ | ✅ | ❌ | ❌ |
| Safe Zones | ✅ | ✅ | ❌ | ❌ |
| Exit Points | ✅ | ⏰ Zeit | ❌ | ⏰ Zeit |

#### Karten-Layer-System

**Base Layers** (einer aktiv):
- Streets, Satellite, Dark Mode, Custom

**Data Layers** (kombinierbar):
- Zonen, Tracking, Heatmaps, Events, POIs, Weather, 3D Buildings, Hunter-Strategien

#### Interaktive Zonen-Erstellung

**Tools für Spielleitung**:
- Rectangle, Circle, Polygon, Import (GeoJSON/KML)
- Eigenschaften: Name, Sichtbarkeit, Aktions-Regeln, Zeitplan
- Echtzeit-Synchronisation via WebSocket

#### 3D-Features

- 3D-Gebäude-Extrusion (Mapbox)
- Terrain (Höhenlinien)
- Kamera-Steuerung (Pitch, Bearing, Zoom)

#### Indoor-Mapping

- Shopping Malls, Flughäfen, U-Bahn
- Floor-Selector
- WiFi-Trilateration, Bluetooth-Beacons

---

### Szenario & Event-System

#### Event-Typen

**1. Automatische Events**:
- Proximity-Events (2 Spieler < X Meter)
- Zone-Events (betreten/verlassen/zu lange)
- Tracking-Events (GPS-Anomalie, Tracker offline)
- Transaction-Events (Kauf via Banking-API)
- Time-Events (Daily Reset, Speedhunt-Schedule)

**2. Manuelle Events** (Spielleitung):
- Speedhunt starten
- Challenge-Announcement
- Custom Events (freier Text)

**3. Regelwerk-Events**:
- Catch-Event (Hunter meldet Capture)
- Joker-Activation

#### Scenario Builder

**Beispiel-Konfiguration**:
```yaml
Scenario: "Hunter Near Runner"
Trigger:
  Type: Proximity
  Participants: [Hunter, Runner]
  Distance: 200 meters
Conditions:
  - Game Phase: Active
  - NOT during Challenge
  - Cooldown: 10 min per pair
Actions:
  - Notify Hunter: Exact Location
  - Optional: Notify Runner (Warning)
  - Log Event
```

#### Event-Timeline

Zentrale Übersicht für Spielleitung:
- Echtzeit-Updates (WebSocket)
- Filterung (Typ, Spieler, Zeitraum)
- Export (CSV, JSON, PDF)
- Direktlinks zu Karte
- Expandable Cards mit Details

#### Regelwerk-Enforcement

**Automatische Checks**:
- Fortification > 2h
- Budget überschritten
- Zone-Violations
- Tracking-Anomalien

**GM-Review-Queue**:
Violations landen bei Spielleitung zur Entscheidung:
- Warning
- Penalty (z.B. extra Speedhunt)
- Disqualify
- Dismiss (false alarm)

Alle Entscheidungen werden geloggt (Audit-Trail).

---

### Joker-System

#### Joker-Struktur

**Core-Eigenschaften**:
```yaml
Joker:
  Name: "Regeneration Joker"
  Icon: 🛡️
  Ownership: Personal | Shared
  Usage: Single-Use | Multi-Use (3x)
  Cooldown: 0 | 60 minutes
  Available From: "2024-07-15T06:00:00Z"
  Available Until: "2024-07-16T05:59:59Z"
  Effect:
    Type: "no_tracking_for_hunters"
    Duration: 240 minutes
  Transfer: Transferable | Requires GM Approval
  Marketplace: Tradable | Not Tradable
```

#### Standard-Joker

1. **Regeneration Joker**: 4h ohne Pings/Speedhunts
2. **Hunter Location Query**: Zeigt Hunter-Positionen
3. **Immunity**: 3h Fangimmunität
4. **Fake Ping**: Nächster Ping zeigt falsche Position
5. **Hotel-Bonus**: 6h sicherer Schlaf, dann Hunter-Benachrichtigung

#### Custom Jokers

Spielleitung kann eigene Joker erstellen:
- Freie Effekt-Definition
- Zeitplan und Verfügbarkeit
- Multi-Use mit Cooldown
- Team-Joker (Shared)

#### Joker-Verwaltung

**Spielleitung kann**:
- Zuweisen, Entziehen
- Als verwendet markieren
- Übertragen (mit Approval)
- Marketplace an/aus schalten

#### Shared Jokers

Team-Joker: Einer aktiviert → Alle profitieren

#### Joker-Marketplace (Optional)

Spieler können Joker untereinander handeln:
- Angebote erstellen
- Tauschgeschäfte
- GM-Approval erforderlich (optional)
- Trade-Limits konfigurierbar

#### Zeitbasierte Freischaltung

Automatisches Unlock nach Tagen:
```
Tag 1: Keine Joker
Tag 2: Regeneration, Hunter Location
Tag 3: Fake Ping, Immunity
Tag 4: Hotel-Bonus
```

---

### Chat & Kommunikation

#### Chat-Architektur

- Echtzeit via WebSocket (Laravel Reverb/Soketi)
- < 100ms Latenz
- Typing-Indicators, Read-Receipts
- Message-Delivery-Status

#### Chat-Raum-Typen

1. **Global Game Chat**: Alle Teilnehmer, Ankündigungen
2. **Role-Based**: Runner-Chat, Hunter-Chat, GM-Chat
3. **Team-Chats**: Runner-Teams, Hunter-Teams
4. **Direct Messages**: 1:1 (falls erlaubt)
5. **Custom Chats**: Von GM erstellt

#### Spielleitung-Kontrolle

**Vollständige Moderations-Macht**:
- Alle Chats lesen (auch DMs)
- In jedem Chat schreiben
- Nachrichten editieren (mit Edit-Marker)
- Nachrichten löschen (Soft-Delete für Audit)
- Chats aktivieren/deaktivieren (Mute/Enable)
- Benutzer zu Chats hinzufügen/entfernen

#### Message-Features

**Rich-Media**:
- Text (Markdown), Emojis
- Images (max 4MB)
- Locations (GPS-Position teilen)
- Files (PDF, Dokumente, max 4MB)

**Interaktionen**:
- @Mentions (@GameMaster, @Runner2, @all)
- Reactions (👍 😱 🏃)

#### Push-Notifications

**Trigger**:
- Neue Nachricht
- @Mention
- Direct Message
- Wichtige Ankündigungen

**Platforms**:
- Web: Browser-Notifications
- Mobile: Firebase Cloud Messaging

**User-Einstellungen**:
- All Messages | Mentions Only | DMs Only | None
- Quiet Hours

#### Chat-History & Export

- Unbegrenzte History
- Volltext-Search
- Filterbar
- Export (JSON)

---

### Transaktions-Tracking

#### Banking-Integration

**Unterstützte Anbieter**:
- Revolut Business API
- Bunq API
- N26 API (zukünftig)
- Wise API (zukünftig)

#### Transaction-Flow

1. **Runner nutzt virtuelle Karte** (Revolut Virtual Card)
2. **Revolut sendet Webhook** bei Transaction
3. **Platform empfängt Webhook**:
   - Transaction in DB speichern
   - Budget aktualisieren
   - Hunter benachrichtigen (gemäß Regeln)
   - Event in Timeline
   - GM benachrichtigen
   - Budget-Violation Check

4. **Hunter erhalten Notification**:
   ```
   🎯 TRANSACTION ALERT
   Runner #3 - Joe's Pizza
   📍 40.7580° N, 73.9855° W
   💵 $8.50
   ```

5. **Location auf Karte markiert**

#### Budget-Management

**Daily Reset** (06:00 Uhr):
- Budget zurücksetzen auf $30
- Notification an Runner

**Runner-Budget-Dashboard**:
```
💰 MEIN BUDGET
Heute verfügbar: $21.50 / $30.00
[████████████░░░░░░] 72%

Nächster Reset: in 15h 24min

Heutige Ausgaben:
├─ 14:35 - Joe's Pizza       -$8.50
├─ 12:20 - U-Bahn Ticket     -$2.75
└─ 08:15 - Kaffee            -$4.25
```

#### Transaction-Rules

Konfigurierbar:
- Notify Hunters: Immediate | Delayed | Exact Location | Radius | Zone Only
- Spending Limits: Daily Budget, Per-Transaction Max
- Merchant Categories: Allowed/Forbidden
- Cash Withdrawal: Forbidden → Auto-Violation

#### Transaction-Analytics

Post-Game:
- Total Spent per Runner
- Category Breakdown (Food, Transport, Other)
- Transaction Heatmap
- Savings-Rate

---

### Video & Streaming

#### Video-Upload (S3)

**Challenge**: AWS Lambda 4MB Payload-Limit

**Lösung**: Chunked Upload + Pre-signed URLs

**Flow**:
1. Client initiiert Multipart-Upload
2. Backend erstellt Pre-signed URLs für Chunks
3. Client uploaded Chunks direkt zu S3 (je 3MB)
4. Client completed Multipart-Upload
5. Backend triggert Processing (Transcoding, Thumbnail)

#### Automatic Timestamps

Bei Events automatisch Video-Timestamps setzen:
- Speedhunt Start/End
- Challenge Start/End
- Joker Activation
- Capture
- Proximity Alert
- etc.

**Export für Editing**:
- Final Cut Pro XML
- Premiere Pro XML
- Marker mit Farben und Labels

#### Live-Stream Integration

**Platforms**: YouTube Live, Twitch, Custom RTMP

**Features**:
- Stream-Status-Monitoring (Bitrate, FPS, Latency)
- Multi-Stream-Director-View
- Zwischen Cams wechseln
- Picture-in-Picture

#### Footage-Requirement-Tracking

**Regel**: z.B. 35h Footage pro Tag

**Tracking**:
```
Runner #3:
Day 1: ✅ 37h 23min / 35h (106%)
Day 2: ✅ 35h 12min / 35h (100%)
Day 3: ⚠️  28h 45min / 35h (82%) - Missing: 6h 15min
```

**Warnings** bei Unter-Erfüllung

#### Video-Metadata

Automatisches Tagging:
- Runner, Game, Recorded Date
- Location Start/End
- Events Captured (mit Video-Offset)
- Quality Score
- Transcoding Status
- Thumbnail URL

---

### KI & Automation

#### Automatische Event-Erkennung

- **Proximity Detection**: Spieler < X Meter
- **GPS-Spoofing**: Unmögliche Geschwindigkeit (> 150 km/h)
- **Fortification-Violation**: > 2h in Gebäude
- **Budget-Violation**: Überzogen
- **Dead-Man-Switch**: Keine Aktivität > 60min

#### Predictive Analytics (Optional)

**Features** (von GM konfigurierbar):
- Movement Prediction (nächste Position)
- Pattern Recognition (Favorite Areas)
- Route Recommendation für Hunter

**Algorithmus**:
- Machine Learning (Random Forest, LSTM)
- Historische Bewegungen analysieren
- Features: Geschwindigkeit, Richtung, Tageszeit, Landmarks

**Ethical Considerations**:
- GM entscheidet über Nutzung
- Kann unfairen Vorteil geben
- Optional an/aus

#### Automatische Regelwerk-Enforcement

- Violations werden automatisch erkannt
- Landen in GM-Review-Queue
- GM trifft finale Entscheidung
- Nur sehr klare Fälle auto-penalized (z.B. > 2h außerhalb Spielfeld)

---

### Sicherheit & Notfälle

#### Panic-Button

Verfügbar für alle (besonders Runner & Security)

**Flow**:
1. Runner drückt 🆘
2. Sofortige Notifications:
   - GM (Critical Priority)
   - Security-Personal
   - Assigned Security
3. Live-Position-Streaming (alle 5s)
4. GM-Emergency-Dashboard
5. Optionen: Spiel pausieren, Hunter abziehen, Security senden
6. **Kein** automatischer Notruf

#### Dead-Man-Switch

Automatische Erkennung bei fehlender Aktivität:
- Stufe 1 (60min): "Bist du okay?" → Bestätigung anfordern
- Stufe 2 (75min): GM benachrichtigen
- Stufe 3 (90min): Emergency Alert + Security-Check

**Aktivität** = GPS-Update | Chat-Message | App-Interaction | Button-Press

#### Geofencing-Alarme

Bei Betreten von Restricted/Danger Areas:
- Automatische Violation
- Sofort-Alarm bei "Danger"-Severity
- Notification an Runner: "⛔ GEFAHRENBEREICH - Sofort verlassen!"

**Danger-Zonen-Beispiele**:
- Militärgelände
- Gefährliche Nachbarschaften
- Baustellen

#### Security-Role

**Dashboard**:
- Live-Positionen zugewiesener Runner
- Status-Anzeige (🟢 Normal | 🔴 Alert)
- Direkt-Chat mit GM und Runnern
- Report-Danger-Button

**Einschränkungen**:
- Keine Spielinformationen
- Keine Hunter-Daten

---

### Hunter-Features

#### Hunter-Teams

- Koordinator-Rolle
- Team-Bildung durch Koordinator
- Team-Chat
- Strategie-Sharing

#### Strategieplanung

Auf Karte zeichnen:
- Routen, Checkpoints, Koordinationspunkte
- Sharing: Alle Teams | Einzelne Teams | Privat

#### Equipment-Tracking

- Equipment definieren (Fahrzeuge, Ausrüstung)
- Equipment zuweisen
- Transfer zwischen Huntern
- GM kann Equipment-Regeln definieren

#### Live-Koordination

- Alle Hunter-Positionen live sehen
- "Wer verfolgt wen" Status
- Ressourcen-Allocation

#### Tracking-Modi für Hunter

**Silenthunt**:
- Pings zu definierten Zeiten (z.B. 1x/h)
- Statische Position (kein Live)

**Speedhunt**:
- Häufige Updates (z.B. alle 5min)
- Begrenzte Queries (z.B. 4)
- Von GM manuell/automatisch getriggert

**Optional: Predictive Analytics**:
- Wahrscheinliche nächste Position
- Confidence-Score
- Von GM an/aus schaltbar

---

### Challenge-System

#### Challenge-Definition

Von Spielleitung vordefiniert:

```yaml
Challenge: "Brooklyn Bridge Run"
Zone: Brooklyn Bridge Area
Participants: [Runner #2, #3, #7]
Start Time: "2024-07-16T14:00:00Z"
Duration: 60 minutes
Rules:
  - Modified: public_transport_forbidden
  - Bonus: Immunity for 1h if completed
Status-Tracking: In Progress | Completed | Failed
```

#### Challenge-Flow

1. GM erstellt Challenge
2. Notification an Teilnehmer
3. Challenge startet zur definierten Zeit
4. Challenge-Zone wird auf Karte sichtbar
5. Modifizierte Regeln aktiv
6. Completion-Check
7. Belohnungen vergeben

#### Challenge-Typen

- Time-Trial (erreiche X in Y Minuten)
- Hide-and-Seek (vermeide Hunter für X Zeit)
- Scavenger Hunt (besuche X Locations)
- Team-Challenge (gemeinsam lösen)
- Custom (freie Definition)

---

### Team-Management

#### Team-Bildung

Von GM steuerbar:
- Team-Feature an/aus
- Temporäre Allianzen erlauben/verbieten

#### Runner-Teams

- Team-Name und Mitglieder
- Team-Chat (von GM moderierbar)
- Shared Jokers
- Team-Statistiken

#### Hunter-Teams

- Koordinator-Rolle
- Team-Strategien
- Ressourcen-Sharing
- Live-Koordination

#### GM-Kontrolle

- Teams erstellen/auflöschen
- Mitglieder hinzufügen/entfernen
- Team-Chats einsehen/moderieren
- Team-Regeln definieren

---

### Production-Features

#### Timeline-Replay

**Zeitreise-Funktion**:
- Zu jedem Zeitpunkt zurückspringen
- Karte zeigt historische Positionen
- Events nachlesen
- Geschwindigkeit einstellbar (1x, 2x, 4x, 8x)

**Use Cases**:
- Post-Production (Video-Editing)
- Dispute-Resolution
- Analytics
- Highlights erstellen

#### Multi-Kamera-View

**Director-Mode**:
```
┌────────────────────────────────────────┐
│ 🎬 DIRECTOR VIEW                       │
├────────────────────────────────────────┤
│ ┌────┐ ┌────┐ ┌────┐ ┌────┐           │
│ │Run1│ │Run2│ │Run3│ │Hunt│           │
│ │🔴  │ │🔴  │ │⚫  │ │🔴  │           │
│ └────┘ └────┘ └────┘ └────┘           │
│                                        │
│ 🎥 Main Output: [Runner 2 ▼]          │
│ [Switch] [PiP] [Audio Mix]            │
└────────────────────────────────────────┘
```

**Features**:
- Live-Switching zwischen Cams
- Picture-in-Picture
- Audio-Mixing
- Output zu Streaming-Platform

#### Event-Timeline mit Highlights

- Alle Events auf Zeitstrahl
- Highlight-Marker setzen (🌟)
- Wichtigkeit (High, Medium, Low)
- Filter und Export

#### Delayed Live-View

Für öffentlichen Stream:
- Konfigurierbarer Delay (z.B. 30min)
- Censor-Funktion (sensitive Daten ausblenden)
- Zuschauer-Mode (Read-Only)

#### Export für Post-Production

**Formate**:
- Final Cut Pro XML
- Premiere Pro XML
- JSON (alle Events und Positionen)
- CSV (Statistiken)
- GPX/KML (GPS-Tracks)

**Daten**:
- Video-Timestamps mit Events
- GPS-Tracks als Overlays
- Event-Marker
- Statistiken

---

### Weather & Environment (Optional)

#### Wetterdaten-Integration

**API**: OpenWeatherMap

**Daten**:
- Aktuelle Temperatur, Regen, Wind
- Sichtweite
- Sonnenauf-/untergang
- Dämmerungszeiten

#### Wetter-Layer auf Karte

Overlay mit:
- Temperatur-Heatmap
- Niederschlags-Radar
- Wind-Richtung und -Stärke

#### Dämmerung/Sonnenlicht

Visualisierung:
- Nacht-/Tag-Zyklus auf Karte
- Dämmerungs-Phasen
- Beleuchtungs-Simulation

#### Wetterbasierte Regeln

Optional konfigurierbar:
```yaml
Weather-Rule:
  Condition: Rain > 10mm/h
  Action: Pause Speedhunts for 2h
  Notification: "Regenpause - Speedhunts gestoppt"
```

**Use Cases**:
- Sicherheit (bei Gewitter)
- Fairness (schlechte Sicht)
- Dramatik (Produktion)

---

## API & Integrationen

### REST API

**Authentifizierung**: Laravel Sanctum (Token-based)

**Rollen-basierter Zugriff**:
- Hunter: Nur Hunter-spezifische Daten
- Runner: Nur eigene Daten
- GM: Voller Zugriff auf Spiel
- Super-Admin: Plattform-weit

**Endpoints** (Beispiele):
```
GET /api/v1/games/{game_id}
GET /api/v1/games/{game_id}/runners
GET /api/v1/games/{game_id}/events
GET /api/v1/runners/{runner_id}/position
POST /api/v1/jokers/{joker_id}/activate
POST /api/v1/speedhunts/trigger
```

**Rate-Limiting**: 60 requests/minute (anpassbar)

### Webhooks

**Konfigurierbar per Event**:
```yaml
Webhook:
  URL: https://discord.com/api/webhooks/...
  Events:
    - speedhunt_started
    - runner_captured
    - joker_activated
  Method: POST
  Headers:
    Authorization: Bearer {token}
  Payload: JSON
```

**Event-Payload**:
```json
{
  "event": "speedhunt_started",
  "game_id": 42,
  "timestamp": "2024-07-15T14:00:00Z",
  "data": {
    "speedhunt_number": 3,
    "duration_minutes": 30
  }
}
```

**Use Cases**:
- Discord-Bot-Notifications
- Externe Dashboards
- Custom Analytics
- Third-Party-Integrations

### Export-Formate

**Unterstützt**:
- JSON (alle Daten)
- CSV (Statistiken, Transactions, Events)
- GPX (GPS-Tracks)
- KML (Google Earth)
- GeoJSON (Zonen, Positionen)
- XML (Final Cut Pro, Premiere Pro)
- PDF (Reports)

**Export-API**:
```
POST /api/v1/games/{game_id}/export
{
  "format": "json",
  "include": ["events", "positions", "transactions"],
  "time_range": {
    "from": "2024-07-15T00:00:00Z",
    "to": "2024-07-18T23:59:59Z"
  }
}
```

### Drittanbieter-Integrationen

**GPS-Tracker**:
- Provider-APIs (Garmin, Spot, etc.)
- Webhook-Empfang oder Polling
- Normalisierung zu einheitlichem Format

**Banking**:
- Revolut Business API
- Bunq API
- Webhook-Empfang für Transaktionen

**Maps**:
- Mapbox GL JS
- OpenStreetMap Overpass API

**Weather**:
- OpenWeatherMap API
- Polling alle 30 Minuten

**Video**:
- AWS S3 (Storage)
- AWS MediaConvert (Transcoding)
- YouTube/Twitch APIs (Streaming)

---

## DSGVO & Compliance

### Datensammlung

**Sensitive Daten**:
- GPS-Positionen (Bewegungsprofile)
- Transaktionsdaten (Kaufverhalten)
- Kommunikation (Chats)
- Video-Material (Bild-/Tonaufnahmen)

### DSGVO-Consent

**Prozess**:
1. **PDF-Generierung**:
   - Automatisch pro Spieler
   - Alle gesammelten Daten aufgelistet
   - Nutzungszweck erklärt
   - Speicherdauer angegeben
   - Rechte (Auskunft, Löschung, Widerruf)

2. **Unterschrift**:
   - Digital (Upload PDF mit Unterschrift)
   - Oder physisch (Scan hochladen)

3. **Speicherung**:
   - In DB mit Timestamp
   - Zuordnung zu Spieler und Spiel

4. **Validierung**:
   - Vor Spielstart: Alle Consents vorhanden?
   - Ohne Consent: Keine Teilnahme

**PDF-Inhalt**:
```
EINWILLIGUNGSERKLÄRUNG - BOUNTYOPS EVENT

Ich, [Name], willige ein, dass folgende Daten erhoben werden:
- GPS-Positionsdaten (Echtzeit-Tracking während des Events)
- Transaktionsdaten (Käufe mit zugewiesener Karte)
- Kommunikationsdaten (Chat-Nachrichten)
- Video-/Audio-Material (Body-Cam-Aufnahmen)

Zweck: Durchführung des BountyOps-Events
Speicherdauer: Unbegrenzt (bis auf Widerruf)
Weitergabe: An Organisatoren, Produktionsteam

Rechte:
- Auskunft über gespeicherte Daten
- Löschung der Daten
- Widerruf dieser Einwilligung

Unterschrift: ________________  Datum: __________
```

### Datenauskunft

**Spieler-Anfragen**:
- Anfrage via Interface: "Meine Daten exportieren"
- System generiert ZIP mit:
  - Alle GPS-Positionen (JSON, GPX)
  - Alle Chat-Messages
  - Alle Transaktionen
  - Alle Events (wo Spieler beteiligt)
  - Alle Videos (Links)
- Download-Link per Email

### Datenlöschung

**Spieler-Anfragen**:
- Anfrage via Interface: "Meine Daten löschen"
- GM erhält Notification (Bestätigung erforderlich)
- Nach Bestätigung: Soft-Delete (Anonymisierung)
  - GPS-Positionen: Gelöscht
  - Chats: "Gelöschter Nutzer"
  - Transaktionen: Runner-ID anonymisiert
  - Videos: Gelöscht (von S3)
- Hard-Delete nach 30 Tagen (Backup-Retention)

### Audit-Logs

**Alle Zugriffe geloggt**:
- Wer hat wann auf welche Daten zugegriffen?
- Super-Admin-Zugriffe besonders
- 2-Jahres-Retention (DSGVO-konform)

### Datensicherheit

**Verschlüsselung**:
- In-Transit: TLS 1.3
- At-Rest: S3 Server-Side-Encryption, DB-Encryption

**Zugriffskontrolle**:
- Role-Based Access Control (RBAC)
- Principle of Least Privilege

**Backups**:
- Tägliche DB-Backups (AWS RDS)
- 30-Tage-Retention
- Verschlüsselt

---

## Datenmodell

### Kern-Tabellen

#### organisations
```sql
id, name, slug, settings, created_at, updated_at
```

#### users
```sql
id, organisation_id, name, email, password, role, created_at, updated_at
```

#### games
```sql
id, organisation_id, name, slug, status, ruleset, settings,
start_time, end_time, created_at, updated_at
```

#### game_participants
```sql
id, game_id, user_id, role, participant_number, settings,
created_at, updated_at
```

#### tracking_positions
```sql
id, game_id, trackable_type, trackable_id, tracker_id,
latitude, longitude, altitude, accuracy, confidence,
timestamp, speed, heading, is_fused, is_anomaly,
created_at
```
**PostGIS**: `geom GEOGRAPHY(POINT, 4326)` für räumliche Queries

#### zones
```sql
id, game_id, type, name, geometry, visibility_rules,
action_rules, active_from, active_until, created_at, updated_at
```
**PostGIS**: `geometry GEOGRAPHY(POLYGON, 4326)`

#### events
```sql
id, game_id, type, triggered_by_id, related_participants,
data, timestamp, importance, created_at
```

#### jokers
```sql
id, game_id, name, icon, description, ownership_type,
assigned_to, usage_type, uses_total, uses_remaining,
effect_type, effect_data, available_from, available_until,
transferable, tradable, created_at, updated_at
```

#### joker_activations
```sql
id, joker_id, activated_by_id, activated_at, effect_expires_at,
data, created_at
```

#### chat_rooms
```sql
id, game_id, type, name, participants, settings, created_at, updated_at
```

#### chat_messages
```sql
id, chat_room_id, sender_id, message, media_url, location,
edited, deleted, created_at, updated_at
```

#### transactions
```sql
id, game_id, runner_id, amount, currency, merchant,
location_lat, location_lng, timestamp, external_id, created_at
```

#### video_uploads
```sql
id, game_id, runner_id, filename, s3_key, filesize,
duration_seconds, recorded_at, upload_status, metadata,
created_at, updated_at
```

#### video_timestamps
```sql
id, video_upload_id, timestamp, event_id, type, label,
importance, created_at
```

#### rule_violations
```sql
id, game_id, participant_id, type, severity, details,
auto_detected, status, reviewed_by_id, reviewed_at,
decision, penalty, created_at, updated_at
```

#### emergency_alerts
```sql
id, game_id, user_id, type, emergency_type, position_lat,
position_lng, status, resolved_at, notes, created_at, updated_at
```

#### audit_logs
```sql
id, user_id, action, resource_type, resource_id, ip_address,
user_agent, data, created_at
```

### Beziehungen

```
Organisation
 ├─ Users
 └─ Games
     ├─ Participants (Users)
     ├─ Zones
     ├─ Events
     ├─ Jokers
     ├─ Chat Rooms
     ├─ Transactions
     ├─ Video Uploads
     ├─ Rule Violations
     └─ Emergency Alerts

Participants
 ├─ Tracking Positions
 └─ Joker Activations

Video Uploads
 └─ Video Timestamps
```

---

## WebSocket-Architektur

### Technologie

**Laravel Reverb** (oder Soketi als Alternative)
- WebSocket-Server für Laravel
- Kompatibel mit Laravel Broadcasting
- Redis als Backend
- Horizontal skalierbar

### Channels

**Private Channels** (Authentifizierung erforderlich):
```
game.{game_id}                    // Alle Teilnehmer
game.{game_id}.gamemaster         // Nur Spielleitung
game.{game_id}.runner.{runner_id} // Nur dieser Runner
game.{game_id}.hunters            // Alle Hunter
chat.{chat_room_id}               // Chat-Teilnehmer
```

**Presence Channels**:
```
presence-game.{game_id}           // Wer ist online?
```

### Event-Broadcasting

**Position-Updates**:
```php
broadcast(new PositionUpdated($runner, $position))
  ->toOthers(); // Nicht an Sender
```

**Chat-Messages**:
```php
broadcast(new MessageSent($chatRoom, $message));
```

**Game-Events**:
```php
broadcast(new SpeedhuntStarted($game, $speedhunt));
broadcast(new JokerActivated($runner, $joker));
broadcast(new ZoneChanged($game, $zone));
```

### Client-Side (JavaScript)

```javascript
// Laravel Echo
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
});

// Subscribe to Game Channel
Echo.private(`game.${gameId}`)
    .listen('PositionUpdated', (e) => {
        updateMarkerOnMap(e.runner, e.position);
    })
    .listen('SpeedhuntStarted', (e) => {
        showNotification('Speedhunt gestartet!');
    });

// Subscribe to Chat
Echo.private(`chat.${chatRoomId}`)
    .listen('MessageSent', (e) => {
        appendMessageToChat(e.message);
    })
    .listenForWhisper('typing', (e) => {
        showTypingIndicator(e.user);
    });
```

### Update-Frequenzen

**Konfigurierbar pro Spiel**:
- Live-Map: 5s, 10s, 30s, 60s
- Chat: Echtzeit (< 1s)
- Events: Echtzeit
- Statistics: 30s, 60s, 5min

**Throttling**:
- Max 10 Position-Updates pro Sekunde (Server-Side)
- Client-Side: Batch-Updates alle X Sekunden

### Scalability

**Redis-Cluster**:
- Horizontal scaling
- Multi-Node-Setup

**Connection-Handling**:
- Reconnect-Logic (Auto-Reconnect bei Disconnect)
- Message-Queue bei Offline (kurz)

---

## Implementierungs-Roadmap

### Phase 1: MVP (Minimal Viable Product)

**Dauer**: 3-4 Monate

**Features**:
- ✅ Multi-Tenancy (Organisationen, Spiele)
- ✅ User-Management mit Rollen (GM, Runner, Hunter)
- ✅ Basis-Tracking (GPS via WebApp)
- ✅ Einfache Karte (Mapbox, Zonen-Anzeige)
- ✅ Event-System (Basis)
- ✅ Chat (Echtzeit via WebSocket)
- ✅ Regelwerk (Basis-Konfiguration)
- ✅ Spielphasen (Setup bis Post-Game)

**Tech-Stack**:
- Laravel 12, Livewire, Filament
- PostgreSQL, Redis
- Mapbox GL JS
- AWS Lambda, S3

### Phase 2: Core Features

**Dauer**: 2-3 Monate

**Features**:
- ✅ Multi-Tracker-Support (Garmin, Spot, etc.)
- ✅ GPS-Datenfusion
- ✅ Joker-System (vollständig)
- ✅ Transaktions-Tracking (Revolut-Integration)
- ✅ Zone-Management (erweitert)
- ✅ Szenario-Builder
- ✅ Regelwerk-Enforcement (automatisch)
- ✅ DSGVO-Compliance (Consent-PDFs)

### Phase 3: Production & Analytics

**Dauer**: 2 Monate

**Features**:
- ✅ Video-Upload (S3, Chunked)
- ✅ Automatic Timestamps
- ✅ Timeline-Replay
- ✅ Multi-Kamera-View
- ✅ Export-Funktionen (GPX, JSON, XML)
- ✅ Post-Game-Reports
- ✅ Analytics (Heatmaps, Statistiken)

### Phase 4: Advanced Features

**Dauer**: 2 Monate

**Features**:
- ✅ Challenge-System
- ✅ Team-Management
- ✅ Hunter-Strategie-Planung
- ✅ 3D-Karten
- ✅ Indoor-Mapping
- ✅ Weather-Integration
- ✅ Gamification (Achievements, Leaderboards)

### Phase 5: AI & Automation

**Dauer**: 1-2 Monate

**Features**:
- ✅ Anomalie-Erkennung (GPS-Spoofing)
- ✅ Predictive Analytics (optional)
- ✅ Auto-Event-Detection (erweitert)
- ✅ Smart Notifications

### Phase 6: Polish & Scale

**Dauer**: Ongoing

**Features**:
- Performance-Optimierung
- UI/UX-Verbesserungen
- Mobile Apps (iOS, Android)
- Additional Integrations (N26, Wise, etc.)
- Community-Features
- Documentation & Tutorials

---

## Technische Anforderungen

### Entwicklungsumgebung

**Lokal**:
- PHP 8.4+ mit Extensions (pdo_pgsql, redis, gd, imagick)
- Composer 2.x
- Node.js 20+ mit npm
- PostgreSQL 15+ mit PostGIS
- Redis 7+

**Laravel Herd** (empfohlen für macOS):
- PHP, Nginx, Redis vorinstalliert
- Einfaches Switching zwischen PHP-Versionen

### Deployment

**AWS Lambda** (Serverless):
- Bref Framework für Laravel
- CloudFormation / Terraform für Infrastructure-as-Code

**Datenbank**:
- AWS RDS PostgreSQL mit PostGIS
- Multi-AZ für Hochverfügbarkeit

**Storage**:
- S3 für Videos, Exports, Dokumente
- CloudFront als CDN

**WebSockets**:
- Reverb auf separatem Server (EC2 oder Container)
- Oder Soketi via Docker

### Performance-Ziele

- **API-Response-Time**: < 200ms (p95)
- **WebSocket-Latency**: < 100ms
- **Map-Load-Time**: < 2s
- **Video-Upload**: Chunked, kein Timeout
- **Concurrent-Games**: Unbegrenzt (durch Scaling)
- **Concurrent-Users**: 1000+ pro Spiel

### Sicherheit

- **HTTPS**: Überall (TLS 1.3)
- **CSRF-Protection**: Laravel-Standard
- **XSS-Protection**: Input-Sanitization
- **SQL-Injection**: Eloquent ORM (Prepared Statements)
- **Authentication**: Laravel Sanctum (Token-based)
- **Authorization**: Laravel Policies
- **Rate-Limiting**: 60 req/min (anpassbar)
- **Secrets-Management**: AWS Secrets Manager

---

## Zusammenfassung

Die **BountyOps SaaS Platform** ist eine umfassende Lösung für die Planung, Durchführung und Nachbereitung von Live-Action-Verfolgungsjagd-Events. Sie bietet:

1. **Vollständige Spielverwaltung** von Setup bis Post-Game
2. **Echtzeit-Tracking** mit Multi-Tracker-Support und GPS-Fusion
3. **Flexible Regelwerke** mit vollständiger Konfigurierbarkeit
4. **Production-Tools** für professionelle Video-Produktion
5. **Automatisierung** durch KI-gestützte Event-Erkennung
6. **Sicherheit** mit Notfall-Features und DSGVO-Compliance
7. **Skalierbarkeit** für unbegrenzte Spiele und Teilnehmer

Die Plattform ist **kostenlos** und als Open-Source-Projekt konzipiert, um die Community zu fördern und Innovation zu ermöglichen.

**Technologie**: Laravel 12, Filament, Livewire, PostgreSQL, Redis, Mapbox, AWS Lambda

**Roadmap**: 6 Phasen über ~12 Monate von MVP bis Production-Ready

**Vision**: Die universelle Plattform für BountyOps-Events weltweit
