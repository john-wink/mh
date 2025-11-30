# Team 1: Core Platform & Authentication

> Foundation-Team - Verantwortlich für die Basis-Architektur, Multi-Tenancy, Authentication und Core-Models

## Team-Mitglieder

| Name | Role | Spezialisierung | Erfahrung |
|------|------|-----------------|-----------|
| TBD | Senior Laravel Developer (Lead) | Architecture, Multi-Tenancy | 8+ Jahre |
| TBD | Mid-Level Backend Developer | Laravel, APIs | 4-6 Jahre |
| TBD | Mid-Level Backend Developer | Database, Models | 4-6 Jahre |
| TBD | Junior Developer | Laravel, Testing | 1-2 Jahre |

**Team-Größe**: 4 Entwickler

---

## Verantwortungsbereiche

### Primär-Verantwortung

1. **Multi-Tenancy-Architektur**
   - Organisation-Isolierung
   - Datenbank-Partitionierung
   - Tenant-Erkennung
   - Super-Admin-Zugriff

2. **Authentication & Authorization**
   - Laravel Sanctum (Token-based Auth)
   - 10 Rollen-System (RBAC)
   - Permissions & Policies
   - Session-Management

3. **Core-Models**
   - Organisation
   - User
   - Game
   - GameParticipant
   - Permission-Models

4. **API-Basis**
   - REST-API-Struktur
   - API-Documentation (Swagger)
   - Rate-Limiting
   - Versioning

5. **DSGVO & Compliance**
   - Consent-Management
   - Datenauskunft
   - Datenlöschung
   - Audit-Logs

6. **Dashboards** (Filament)
   - Super-Admin-Dashboard
   - Organisations-Admin-Dashboard
   - Spielleitung-Dashboard
   - Runner-Dashboard
   - Hunter-Dashboard

### Sekundär-Verantwortung (Support)

- Code-Reviews für alle anderen Teams
- Architektur-Beratung
- Performance-Optimierung

---

## Dependencies

### Blocker für andere Teams

⚠️ **KRITISCH**: Alle anderen Teams sind von Team 1 abhängig!

- **Team 2 (GPS)**: Benötigt User, Game, GameParticipant-Models
- **Team 3 (Maps)**: Benötigt Game, Zone-Basis
- **Team 4 (Game Logic)**: Benötigt alle Core-Models
- **Team 5 (Realtime)**: Benötigt Auth & Permissions
- **Team 6 (Integrations)**: Benötigt API-Basis

### Abhängig von

- **Team 7 (DevOps)**: Infrastructure (RDS, Redis)

---

## Technologie-Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.4)
- **Database**: PostgreSQL 15+ (via Team 7)
- **Cache**: Redis (via Team 7)
- **Queue**: Redis Queue

### Frontend
- **Admin-Panel**: Filament v4
- **UI**: Livewire v3
- **Styling**: Tailwind CSS v4

### Testing
- **Framework**: Pest v4
- **Coverage-Target**: 90%+

### Tools
- **API-Docs**: Scribe / L5-Swagger
- **Code-Quality**: Larastan, Pint

---

## Kommunikation

### Daily Standup
- **Zeit**: 9:00 Uhr (15min)
- **Ort**: Slack `#team-1-core` (Video-Call)
- **Format**: Was gestern? Was heute? Blocker?

### Team-Sync
- **Zeit**: Mittwoch 14:00 (30min)
- **Zweck**: Architektur-Entscheidungen, Code-Reviews

### Office Hours
- **Team-Lead verfügbar**: Täglich 16:00-17:00
- **Zweck**: Andere Teams können Fragen stellen

---

## Definition of Done

Für Team 1 gelten zusätzlich:

1. ✅ **Architecture-Review** (von Tech Lead approved)
2. ✅ **Breaking-Changes kommuniziert** (an alle Teams)
3. ✅ **Migration-Script getestet** (Up & Down)
4. ✅ **Filament-Admin-Panel aktualisiert**
5. ✅ **API-Documentation aktualisiert**

---

## Key Performance Indicators (KPIs)

| Metric | Target | Aktuell |
|--------|--------|---------|
| Code-Coverage | 90%+ | TBD |
| API-Response-Time | < 100ms (p95) | TBD |
| Sprint-Velocity | 20-25 SP | TBD |
| Bug-Rate | < 5% | TBD |
| Code-Review-Time | < 4h | TBD |

---

## Risiken & Mitigation

### Top-Risiken

**1. Multi-Tenancy-Komplexität** (Impact: KRITISCH)
- **Mitigation**: Frühe Architektur-Review, externe Beratung
- **Status**: 🟡 Monitoring

**2. Team 1 Delay = Gesamt-Projekt Delay** (Impact: KRITISCH)
- **Mitigation**: Priorisierung, zusätzliche Ressourcen bei Bedarf
- **Status**: 🟡 Monitoring

**3. Scope-Creep (zu viele Features)** (Impact: HOCH)
- **Mitigation**: Strikte Priorisierung, Product-Owner-Alignment
- **Status**: 🟢 OK

---

## Sprint-Übersicht (Quick-Reference)

| Sprint | Phase | Hauptfokus | Deliverables |
|--------|-------|------------|--------------|
| 1-2 | MVP | Setup & Models | Org, User, Game-Models |
| 3-4 | MVP | RBAC & Dashboards | 10 Rollen, Permissions |
| 5-6 | MVP | API & Admin-Features | REST-API, Super-Admin |
| 7-8 | MVP | DSGVO | Consent-PDFs, Audit-Logs |
| 9-10 | MVP | Dashboards | GM, Runner, Hunter-Dashboards |
| 11-12 | MVP | Refinement | Bug-Fixes, Performance |
| 13-14 | Core | API-Erweiterung | Webhooks, Docs |
| 15-16 | Core | Export | JSON/CSV-Export |
| 17-18 | Core | Video-Support | Video-Metadata |
| 19-20 | Core | Production | Director-Role |
| 21-22 | Core | Analytics | Post-Game-Reports |
| 23-24 | Core | Refinement | Bug-Fixes |
| 25-26 | Advanced | AI-Infra | ML-Infrastructure |
| 27-28 | Advanced | Hunter-Features | Hunter-Coordinator |
| 29-30 | Advanced | Security | Security-Role |
| 31-32 | Advanced | Gamification | Achievements, Leaderboards |
| 33-34 | Advanced | Testing | Sandbox-Mode |
| 35-36 | Advanced | Refinement | Bug-Fixes |
| 37-48 | Polish | Optimization | Performance, Docs, Launch |

---

## Detaillierte Pläne

- **[Sprint-Plan](./sprint-plan.md)** - Detaillierter Sprint-by-Sprint-Plan
- **[Tasks](./tasks.md)** - Task-Liste mit Status
- **[Architecture](./architecture.md)** - Architektur-Entscheidungen
- **[Notes](./notes.md)** - Meeting-Notes

---

**Team-Lead**: TBD
**Scrum Master**: Shared (Projekt-PM)
**Letzte Aktualisierung**: 2025-01-13
