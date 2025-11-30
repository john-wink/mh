# Manhunt SaaS Platform - Development Board

> Zentrale Übersicht über alle Entwicklungs-Teams, Sprint-Pläne und Task-Verteilung

## Team-Struktur

### Kern-Entwicklungsteams (6 Teams, 26 Entwickler)

1. **[Team 1: Core Platform & Authentication](./team-1-core-platform/)** - 4 Entwickler
2. **[Team 2: GPS & Tracking System](./team-2-gps-tracking/)** - 5 Entwickler
3. **[Team 3: Maps & Zones](./team-3-maps-zones/)** - 4 Entwickler
4. **[Team 4: Game Logic & Events](./team-4-game-logic/)** - 5 Entwickler
5. **[Team 5: Real-time Communication](./team-5-realtime/)** - 4 Entwickler
6. **[Team 6: Integrations & APIs](./team-6-integrations/)** - 4 Entwickler

### Support-Teams (3 Teams, 8-9 Personen)

7. **[Team 7: DevOps & Infrastructure](./team-7-devops/)** - 3 Entwickler
8. **[Team 8: QA & Testing](./team-8-qa/)** - 3 QA Engineers
9. **[Team 9: UI/UX & Design](./team-9-design/)** - 2-3 Designer

**Gesamt: 28-29 Personen**

---

## Projekt-Timeline

**Gesamt-Dauer: 8-12 Monate** (36-48 Sprints à 1 Woche)

### Phase 1: MVP Foundation (Sprints 1-12, ~3 Monate)
- Teams 1, 2, 7 starten sofort
- Teams 3, 4, 5 ab Sprint 3
- **Ziel**: Funktionierendes MVP mit Core-Features

### Phase 2: Core Features (Sprints 13-24, ~3 Monate)
- Alle Kern-Teams aktiv
- Team 6 startet ab Sprint 13
- **Ziel**: Vollständiges Feature-Set

### Phase 3: Advanced Features (Sprints 25-36, ~3 Monate)
- Fokus auf Advanced Features
- AI/Automation
- **Ziel**: Production-ready Platform

### Phase 4: Polish & Launch (Sprints 37-48, ~2-3 Monate)
- Performance-Optimierung
- Bug-Fixes
- Launch-Vorbereitung

---

## Sprint-Rhythmus

**Sprint-Dauer**: 1 Woche (Montag-Freitag)

**Sprint-Struktur**:
- **Montag**: Sprint Planning (2h)
- **Dienstag-Donnerstag**: Development + Daily Standups (15min)
- **Freitag**: Sprint Review (1h) + Retro (1h) + Sprint Planning Next (1h)

**Ceremonies**:
- Daily Standup: 9:00 Uhr (15min, alle Teams zusammen)
- Sprint Planning: Montag 10:00 (2h, pro Team)
- Sprint Review: Freitag 14:00 (1h, alle Teams)
- Retrospective: Freitag 15:00 (1h, pro Team)

---

## Team-Dependencies

```
Team 1 (Core Platform)
  └─> BLOCKER für alle anderen Teams
      └─> Team 2 (GPS) - benötigt User/Game Models
      └─> Team 3 (Maps) - benötigt Game/Zone Models
      └─> Team 4 (Game Logic) - benötigt alle Models
      └─> Team 5 (Realtime) - benötigt Auth/Permissions
      └─> Team 6 (Integrations) - benötigt API-Basis

Team 7 (DevOps)
  └─> BLOCKER für alle Teams (Infrastructure)

Team 2 (GPS)
  └─> Team 3 (Maps) - Position-Data für Karte
  └─> Team 4 (Game Logic) - Position-Data für Events

Team 3 (Maps)
  └─> Team 4 (Game Logic) - Zones für Regelwerk

Team 5 (Realtime)
  └─> Team 2, 3, 4 - WebSocket-Events von allen
```

---

## Kommunikations-Kanäle

### Daily
- **Slack**: `#manhunt-dev` (General)
- **Team-Channels**: `#team-1-core`, `#team-2-gps`, etc.

### Wöchentlich
- **All-Hands**: Montag 9:00 (30min, Status-Update)
- **Tech-Sync**: Mittwoch 16:00 (1h, Architektur-Entscheidungen)

### Monatlich
- **Demo-Day**: Letzter Freitag im Monat (2h, alle Stakeholder)
- **Planning-Meeting**: Erster Montag im Monat (3h, nächste 4 Sprints)

---

## Tools & Workflows

### Code
- **Repository**: GitHub (Mono-Repo)
- **Branching**: GitFlow (main, develop, feature/*, hotfix/*)
- **Code-Review**: Minimum 1 Approval (von anderem Team wenn Cross-Team)
- **CI/CD**: GitHub Actions

### Project Management
- **Board**: `.board/` in Repository (Markdown-based)
- **Issues**: GitHub Issues (mit Labels pro Team)
- **Sprints**: GitHub Projects

### Testing
- **Unit Tests**: Pest (Laravel)
- **E2E Tests**: Pest + Browser Testing
- **Code Coverage**: Minimum 80%

### Communication
- **Slack**: Daily Communication
- **Zoom**: Video Calls
- **Miro**: Architecture-Diagramme
- **Figma**: Design-Specs

---

## Definition of Done (DoD)

Ein Task ist "Done" wenn:
1. ✅ Code geschrieben und committed
2. ✅ Unit-Tests geschrieben (min. 80% Coverage)
3. ✅ Code-Review approved (min. 1 Person)
4. ✅ Integration-Tests bestanden
5. ✅ Dokumentation aktualisiert
6. ✅ Deployed auf Staging-Environment
7. ✅ QA-Approved (von Team 8)
8. ✅ Merged in `develop` Branch

---

## Risiko-Management

### Top-Risiken

**1. Team 1 Delay** (Wahrscheinlichkeit: Mittel, Impact: Kritisch)
- **Mitigation**: Priorisierung, zusätzliche Ressourcen bei Bedarf

**2. GPS-Datenfusion-Komplexität** (Wahrscheinlichkeit: Hoch, Impact: Hoch)
- **Mitigation**: Proof-of-Concept in Sprint 1-2, externe Expertise

**3. WebSocket-Skalierung** (Wahrscheinlichkeit: Mittel, Impact: Hoch)
- **Mitigation**: Load-Testing früh (ab Sprint 6), Architektur-Review

**4. Banking-API-Integration** (Wahrscheinlichkeit: Mittel, Impact: Mittel)
- **Mitigation**: Sandbox-Testing, Mock-APIs für Development

**5. AWS Lambda 4MB-Limit** (Wahrscheinlichkeit: Niedrig, Impact: Hoch)
- **Mitigation**: Pre-signed URLs, Chunked Uploads, Architecture bereits designed

---

## Schnellzugriff

- **[Gesamt-Sprint-Plan](./sprint-plan-gesamt.md)** - Alle Sprints, alle Teams
- **[Team-Übersicht](./teams-overview.md)** - Detaillierte Team-Infos
- **[Milestone-Tracking](./milestones.md)** - Wichtige Meilensteine

---

## Team-Verzeichnisse

Jedes Team hat seinen eigenen Ordner mit:
- `README.md` - Team-Übersicht, Mitglieder, Verantwortung
- `sprint-plan.md` - Detaillierter Sprint-Plan (alle Sprints)
- `tasks.md` - Task-Liste mit Status-Tracking
- `architecture.md` - Architektur-Entscheidungen
- `notes.md` - Meeting-Notes, Entscheidungen

---

**Letzte Aktualisierung**: 2025-01-13
**Status**: Planning Phase
**Nächster Meilenstein**: Sprint 1 Start
