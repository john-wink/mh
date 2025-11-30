# Teams-Übersicht

## Team-Matrix

| # | Team | Size | Priorität | Start-Sprint | Hauptfokus |
|---|------|------|-----------|--------------|------------|
| 1 | Core Platform | 4 | 🔴 Kritisch | 1 | Multi-Tenancy, Auth, Models |
| 2 | GPS & Tracking | 5 | 🔴 Kritisch | 1 | GPS-Fusion, Anomalie-Detection |
| 3 | Maps & Zones | 4 | 🟠 Hoch | 1 | Karte, Zonen, Layer |
| 4 | Game Logic | 5 | 🟠 Hoch | 1 | Spielphasen, Events, Joker |
| 5 | Real-time | 4 | 🟠 Hoch | 1 | Chat, WebSockets, Push |
| 6 | Integrations | 4 | 🟡 Mittel | 13 | Banking, Video, Streaming |
| 7 | DevOps | 3 | 🔴 Kritisch | 1 | AWS, Infrastructure |
| 8 | QA | 3 | 🟠 Hoch | 1 | Testing, QA |
| 9 | Design | 2-3 | 🟡 Mittel | 1 | UI/UX, Design-System |

**Gesamt**: 28-29 Personen

---

## Cross-Team-Dependencies

```
Team 1 (Core)
  ├─> Team 2 (GPS)         - benötigt Models
  ├─> Team 3 (Maps)        - benötigt Models
  ├─> Team 4 (Game Logic)  - benötigt Models
  ├─> Team 5 (Realtime)    - benötigt Auth
  └─> Team 6 (Integrations) - benötigt API-Basis

Team 7 (DevOps)
  ├─> Team 1-6             - liefert Infrastructure
  └─> Team 8 (QA)          - liefert CI/CD

Team 2 (GPS)
  ├─> Team 3 (Maps)        - liefert Position-Data
  └─> Team 4 (Game Logic)  - liefert Position-Data

Team 3 (Maps)
  └─> Team 4 (Game Logic)  - liefert Zone-Data

Team 4 (Game Logic)
  ├─> Team 5 (Realtime)    - liefert Events
  └─> Team 6 (Integrations) - liefert Event-Timestamps

Team 5 (Realtime)
  └─> All Teams            - liefert WebSocket-Infra

Team 8 (QA)
  └─> All Teams            - testet alle Features

Team 9 (Design)
  └─> All Teams            - liefert Design-Assets
```

---

## Skill-Matrix

### Backend-Development
- **Team 1**: Laravel-Experten (Multi-Tenancy)
- **Team 2**: Algorithmen + Geo-Data
- **Team 4**: Game-Logic + State-Machines
- **Team 5**: WebSocket-Experten
- **Team 6**: API-Integration

### Frontend-Development
- **Team 3**: Mapbox + JavaScript
- **Team 5**: Livewire + WebSockets
- **Team 9**: UI/UX-Designer

### Infrastructure
- **Team 7**: DevOps + AWS

### Quality
- **Team 8**: QA-Engineers

---

## Kommunikations-Struktur

### Daily-Standups
- **Zeitpunkt**: Jeden Tag 9:00 Uhr
- **Teilnehmer**: Alle Teams (kurz, 15min)
- **Format**: Was gestern? Was heute? Blocker?

### Weekly-Syncs
- **Montag 10:00**: Sprint-Planning (alle Teams)
- **Mittwoch 16:00**: Tech-Sync (Tech-Leads)
- **Freitag 14:00**: Sprint-Review (alle Teams)
- **Freitag 15:00**: Retro (pro Team)

### Monthly
- **Erster Montag**: Planning-Meeting (nächste 4 Sprints)
- **Letzter Freitag**: Demo-Day (Stakeholder)

---

## Slack-Channels

### Team-Channels
- `#team-1-core`
- `#team-2-gps`
- `#team-3-maps`
- `#team-4-game-logic`
- `#team-5-realtime`
- `#team-6-integrations`
- `#team-7-devops`
- `#team-8-qa`
- `#team-9-design`

### Cross-Team
- `#manhunt-dev` (General)
- `#architecture` (Architektur-Fragen)
- `#bugs` (Bug-Reports)
- `#releases` (Release-Notes)
- `#random` (Off-Topic)

---

## Onboarding

### Neue Team-Mitglieder

**Tag 1**:
- Welcome-Call (Team-Lead)
- Repository-Access
- Slack-Channels
- Local-Setup (README.md)

**Tag 2-3**:
- Architecture-Overview (Tech-Lead)
- Team-Sprint (beobachten)
- Erste kleine Task

**Woche 1**:
- Pair-Programming
- Code-Review-Training
- Domain-Knowledge

---

## Offboarding

Falls ein Team-Mitglied das Team verlässt:
- Knowledge-Transfer (1 Woche)
- Documentation-Update
- Handover-Meeting
- Access-Revocation

---

**Letzte Aktualisierung**: 2025-01-13
