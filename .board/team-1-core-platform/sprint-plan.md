# Team 1: Core Platform - Detaillierter Sprint-Plan

> Sprint-by-Sprint Breakdown mit konkreten Tasks, Story-Points und Dependencies

---

## Phase 1: MVP Foundation (Sprints 1-12)

### Sprint 1: Project Setup & Foundation

**Zeitraum**: Woche 1  
**Sprint-Ziel**: Projekt aufsetzen, Basis-Architektur designen  
**Story-Points**: 20 SP

#### Tasks

**T1.1.1: Laravel-Projekt Setup** (2 SP) 🔴 KRITISCH
- [ ] Laravel 12 installieren
- [ ] Composer-Dependencies konfigurieren
- [ ] .env-Files (dev, staging, production)
- [ ] Git-Repository initialisieren
- [ ] README.md erstellen

**T1.1.2: Filament Installation** (1 SP)
- [ ] Filament v4 installieren
- [ ] Admin-Panel-Basis konfigurieren
- [ ] Theme anpassen (Tailwind)

**T1.1.3: Multi-Tenancy Architektur-Design** (5 SP) 🔴 KRITISCH
- [ ] Architecture-Diagramm erstellen
- [ ] Tenant-Isolation-Strategy (Database-Partitioning vs Schemas)
- [ ] Tenant-Recognition-Strategy (Domain vs Subdomain vs Path)
- [ ] Migration-Strategy
- [ ] **Deliverable**: Architecture-Doc in `.board/team-1-core-platform/architecture.md`

**T1.1.4: Organisation-Model & Migration** (3 SP) 🔴 KRITISCH
- [ ] Migration: `create_organisations_table`
  - `id`, `name`, `slug`, `settings (json)`, `created_at`, `updated_at`
- [ ] Model: `Organisation`
- [ ] Factory: `OrganisationFactory`
- [ ] Tests: Unit-Tests für Model

**T1.1.5: User-Model & Authentication** (5 SP) 🔴 KRITISCH
- [ ] Migration: `create_users_table`
  - `id`, `organisation_id`, `name`, `email`, `password`, `role`, timestamps
- [ ] Model: `User` (mit Organisation-Relation)
- [ ] Laravel Sanctum installieren
- [ ] API-Token-Generierung
- [ ] Tests: Auth-Tests (Login, Logout, Token)

**T1.1.6: Basis-RBAC Setup** (4 SP)
- [ ] Enum: `UserRole` (SuperAdmin, OrgAdmin)
- [ ] Middleware: `role:super-admin`
- [ ] Policy-Basis
- [ ] Tests: Permission-Tests

**Blockers**: Wartet auf Team 7 (RDS-Setup)

**Deliverables**:
- ✅ Laravel-Projekt läuft lokal
- ✅ Organisation & User-Models
- ✅ Basis-Auth funktioniert

---

### Sprint 2: Core Models Expansion

**Zeitraum**: Woche 2  
**Sprint-Ziel**: Game & Participant-Models, erweiterte RBAC  
**Story-Points**: 22 SP

#### Tasks

**T1.2.1: Game-Model & Migration** (4 SP) 🔴 KRITISCH
- [ ] Migration: `create_games_table`
  - `id`, `organisation_id`, `name`, `slug`, `status`, `ruleset (json)`, `settings (json)`, `start_time`, `end_time`, timestamps
- [ ] Model: `Game`
- [ ] Enum: `GameStatus` (Setup, PreGame, Active, FinalSprint, Endgame, PostGame, Archived)
- [ ] Relations: `organisation()`, `participants()`
- [ ] Factory: `GameFactory`
- [ ] Tests: Game-Model-Tests

**T1.2.2: GameParticipant-Model** (5 SP) 🔴 KRITISCH
- [ ] Migration: `create_game_participants_table`
  - `id`, `game_id`, `user_id`, `role`, `participant_number`, `settings (json)`, timestamps
- [ ] Model: `GameParticipant`
- [ ] Enum: `ParticipantRole` (GameMaster, Assistant, Runner, Hunter, HunterCoordinator, Security, Spectator, Director)
- [ ] Pivot-Relations
- [ ] Factory: `GameParticipantFactory`
- [ ] Tests: Participant-Tests

**T1.2.3: Permission-System (erweitert)** (6 SP)
- [ ] Permission-Model & Migration (optional, oder Laravel-native Policies)
- [ ] Policies: `OrganisationPolicy`, `GamePolicy`, `UserPolicy`
- [ ] Gates definieren
- [ ] Middleware erweitern: `can:manage-game`
- [ ] Tests: Policy-Tests (alle Rollen)

**T1.2.4: Audit-Log-Model** (3 SP)
- [ ] Migration: `create_audit_logs_table`
  - `id`, `user_id`, `action`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `data (json)`, `created_at`
- [ ] Model: `AuditLog`
- [ ] Trait: `Auditable` (für Models)
- [ ] Observer: Log automatisch bei CRUD
- [ ] Tests: Audit-Tests

**T1.2.5: Filament-Ressourcen (Basis)** (4 SP)
- [ ] Filament-Resource: `OrganisationResource` (CRUD)
- [ ] Filament-Resource: `UserResource` (CRUD)
- [ ] Filament-Resource: `GameResource` (CRUD - Basis)
- [ ] Navigation-Setup
- [ ] Tests: Livewire-Tests (Filament)

**Blockers**: Keine (Team 1 unabhängig)

**Deliverables**:
- ✅ Game & Participant-Models
- ✅ Permission-System (RBAC)
- ✅ Filament-Admin-Panel (Basis)

---

### Sprint 3: RBAC Completion & Dashboards

**Zeitraum**: Woche 3  
**Sprint-Ziel**: Alle 10 Rollen implementiert, Basis-Dashboards  
**Story-Points**: 24 SP

#### Tasks

**T1.3.1: Vollständiges Rollen-System** (5 SP)
- [ ] Alle 10 Rollen im `ParticipantRole`-Enum
- [ ] Policies für jede Rolle (was darf wer?)
- [ ] Middleware-Guards pro Rolle
- [ ] Documentation: Permissions-Matrix
- [ ] Tests: Permission-Tests (alle Rollen)

**T1.3.2: Organisation-Dashboard** (4 SP)
- [ ] Filament-Page: `OrganisationDashboard`
- [ ] Widgets: Spiele-Übersicht, User-Übersicht
- [ ] Stats: Anzahl aktive Spiele, Gesamt-User
- [ ] Navigation
- [ ] Tests: Dashboard-Tests

**T1.3.3: User-Management (erweitert)** (4 SP)
- [ ] User-Invite-System (Email-Einladungen)
- [ ] User-Rollen-Zuweisung
- [ ] User-Status (Active, Inactive, Suspended)
- [ ] User-Profile-Edit
- [ ] Tests: User-Management-Tests

**T1.3.4: Game-Creation-Wizard** (7 SP)
- [ ] Filament-Wizard: Spiel erstellen (Multi-Step)
  - Step 1: Basic Info (Name, Start-Time, Duration)
  - Step 2: Regelwerk (Template auswählen)
  - Step 3: Teilnehmer einladen
  - Step 4: Zusammenfassung
- [ ] Wizard-Validation
- [ ] Wizard-State-Management
- [ ] Tests: Wizard-Tests

**T1.3.5: Super-Admin-Features** (4 SP)
- [ ] Super-Admin kann auf alle Organisationen zugreifen
- [ ] Organisations-Wechsel (Super-Admin-Interface)
- [ ] Support-Mode (impersonate User)
- [ ] Audit-Log-Einsicht (alle Orgs)
- [ ] Tests: Super-Admin-Tests

**Deliverables**:
- ✅ 10 Rollen komplett
- ✅ Dashboards (Org-Admin)
- ✅ Game-Creation-Wizard

---

### Sprint 4: API-Basis & Documentation

**Zeitraum**: Woche 4  
**Sprint-Ziel**: REST-API-Struktur, Versioning, Rate-Limiting  
**Story-Points**: 20 SP

#### Tasks

**T1.4.1: API-Routing-Struktur** (3 SP)
- [ ] API-Routes: `/api/v1/`
- [ ] API-Versioning-Strategy
- [ ] API-Controller-Struktur (z.B. `Api/V1/GameController`)
- [ ] API-Response-Format (standardisiert)
- [ ] Tests: API-Route-Tests

**T1.4.2: API-Authentication** (3 SP)
- [ ] Sanctum-Token-Authentication
- [ ] Token-Scopes (Permissions)
- [ ] Token-Expiration
- [ ] Token-Revocation
- [ ] Tests: API-Auth-Tests

**T1.4.3: Rate-Limiting** (2 SP)
- [ ] Rate-Limiter konfigurieren (60 req/min default)
- [ ] Custom-Limits pro Route
- [ ] Rate-Limit-Headers
- [ ] Tests: Rate-Limit-Tests

**T1.4.4: API-Endpoints (Basis)** (6 SP)
- [ ] `GET /api/v1/games` (Liste)
- [ ] `GET /api/v1/games/{id}` (Detail)
- [ ] `POST /api/v1/games` (Create)
- [ ] `PUT /api/v1/games/{id}` (Update)
- [ ] `GET /api/v1/users/me` (Current User)
- [ ] Tests: API-Endpoint-Tests

**T1.4.5: API-Documentation (Scribe)** (4 SP)
- [ ] Scribe installieren
- [ ] API-Docs generieren
- [ ] Postman-Collection exportieren
- [ ] API-Docs hosten (`/docs/api`)
- [ ] Tests: Docs-Generation-Tests

**T1.4.6: Error-Handling (API)** (2 SP)
- [ ] Custom-Exception-Handler (API)
- [ ] Error-Response-Format (JSON)
- [ ] Validation-Errors formatieren
- [ ] Tests: Error-Handling-Tests

**Deliverables**:
- ✅ API-Struktur v1
- ✅ API-Docs verfügbar
- ✅ Rate-Limiting aktiv

---

### Sprint 5: Super-Admin & Audit-Logs

**Zeitraum**: Woche 5  
**Sprint-Ziel**: Super-Admin-Features, Audit-System erweitern  
**Story-Points**: 18 SP

#### Tasks

**T1.5.1: Super-Admin-Dashboard** (4 SP)
- [ ] Filament-Page: `SuperAdminDashboard`
- [ ] Widgets: Alle Orgs, Alle Games, System-Stats
- [ ] Organisation-Switcher
- [ ] System-Settings
- [ ] Tests: Super-Admin-Dashboard-Tests

**T1.5.2: Audit-Log-System (erweitert)** (5 SP)
- [ ] Automatisches Logging (alle CRUD-Operationen)
- [ ] Audit-Log-Viewer (Filament-Resource)
- [ ] Filter (User, Action, Resource, Date-Range)
- [ ] Export (CSV)
- [ ] Retention-Policy (2 Jahre)
- [ ] Tests: Audit-Log-Tests

**T1.5.3: Impersonation (Support-Mode)** (4 SP)
- [ ] Super-Admin kann User impersonieren
- [ ] Impersonation-Banner ("Du bist als X eingeloggt")
- [ ] Impersonation-Stop
- [ ] Audit-Log für Impersonation
- [ ] Tests: Impersonation-Tests

**T1.5.4: Organisation-Settings** (3 SP)
- [ ] Settings-Model (JSON-based)
- [ ] Settings-UI (Filament-Page)
- [ ] Settings-Validation
- [ ] Tests: Settings-Tests

**T1.5.5: User-Activity-Tracking** (2 SP)
- [ ] Last-Login-Tracking
- [ ] Last-Activity-Tracking
- [ ] Online-Status (Redis-based)
- [ ] Tests: Activity-Tests

**Deliverables**:
- ✅ Super-Admin-Features komplett
- ✅ Audit-Logs erweitert

---

### Sprint 6: API-Erweiterung

**Zeitraum**: Woche 6  
**Sprint-Ziel**: API erweitern für andere Teams  
**Story-Points**: 16 SP

#### Tasks

**T1.6.1: API-Endpoints (GameParticipants)** (4 SP)
- [ ] `GET /api/v1/games/{id}/participants`
- [ ] `POST /api/v1/games/{id}/participants` (Teilnehmer hinzufügen)
- [ ] `DELETE /api/v1/games/{id}/participants/{pid}` (Entfernen)
- [ ] `PUT /api/v1/games/{id}/participants/{pid}` (Rolle ändern)
- [ ] Tests: Participant-API-Tests

**T1.6.2: API-Endpoints (Organisations)** (3 SP)
- [ ] `GET /api/v1/organisations`
- [ ] `GET /api/v1/organisations/{id}`
- [ ] `POST /api/v1/organisations` (nur Super-Admin)
- [ ] Tests: Org-API-Tests

**T1.6.3: API-Permissions (Fine-Grained)** (4 SP)
- [ ] Permission-Check in allen API-Controllern
- [ ] Role-based-Access (Hunter sieht nur Hunter-Daten)
- [ ] Scope-basierte Permissions (Sanctum)
- [ ] Tests: API-Permission-Tests

**T1.6.4: API-Error-Messages (Localization)** (2 SP)
- [ ] Deutsche Fehlermeldungen
- [ ] Englische Fehlermeldungen
- [ ] Language-Detection (Header)
- [ ] Tests: Localization-Tests

**T1.6.5: Performance-Optimierung (API)** (3 SP)
- [ ] Eager-Loading (N+1-Probleme vermeiden)
- [ ] Response-Caching (Redis)
- [ ] Database-Query-Optimierung
- [ ] Tests: Performance-Tests

**Deliverables**:
- ✅ API erweitert für Teams 2-6
- ✅ API-Performance optimiert

---

### Sprint 7: DSGVO-Compliance (Teil 1)

**Zeitraum**: Woche 7  
**Sprint-Ziel**: DSGVO-Consent-System  
**Story-Points**: 20 SP

#### Tasks

**T1.7.1: Consent-Model & Migration** (3 SP)
- [ ] Migration: `create_consents_table`
  - `id`, `user_id`, `game_id`, `consent_type`, `version`, `signed_at`, `pdf_path`, timestamps
- [ ] Model: `Consent`
- [ ] Factory: `ConsentFactory`
- [ ] Tests: Consent-Model-Tests

**T1.7.2: PDF-Generierung (DomPDF)** (5 SP)
- [ ] DomPDF installieren
- [ ] Consent-PDF-Template (Blade)
- [ ] PDF-Generator-Service
- [ ] PDF-Speicherung (S3)
- [ ] Tests: PDF-Generation-Tests

**T1.7.3: Consent-Upload** (4 SP)
- [ ] Consent-Upload-UI (Filament)
- [ ] Unterschriebene PDF hochladen
- [ ] Validation (PDF-Format)
- [ ] Speicherung (S3)
- [ ] Tests: Upload-Tests

**T1.7.4: Consent-Validation (Pre-Game)** (3 SP)
- [ ] Pre-Flight-Checklist: Alle Consents vorhanden?
- [ ] Warning bei fehlenden Consents
- [ ] Spielstart-Blocker bei fehlenden Consents
- [ ] Tests: Validation-Tests

**T1.7.5: DSGVO-Dashboard** (3 SP)
- [ ] Filament-Page: `DsgvoDashboard`
- [ ] Consent-Übersicht pro Spiel
- [ ] Missing-Consents-Liste
- [ ] Tests: Dashboard-Tests

**T1.7.6: Consent-Reminder-Emails** (2 SP)
- [ ] Email-Template (Reminder)
- [ ] Cronjob: Reminder 7 Tage vor Spiel
- [ ] Tests: Email-Tests

**Deliverables**:
- ✅ DSGVO-Consent-System

---

### Sprint 8: DSGVO-Compliance (Teil 2)

**Zeitraum**: Woche 8  
**Sprint-Ziel**: Datenauskunft & Datenlöschung  
**Story-Points**: 18 SP

#### Tasks

**T1.8.1: Datenauskunft-Feature** (5 SP)
- [ ] API-Endpoint: `POST /api/v1/users/me/data-export`
- [ ] Export-Job (Queue)
- [ ] ZIP-Generierung (alle User-Daten)
  - GPS-Positionen (JSON, GPX)
  - Chat-Messages
  - Transaktionen
  - Events
  - Videos (Links)
- [ ] Email mit Download-Link
- [ ] Tests: Export-Tests

**T1.8.2: Datenlöschung-Feature** (5 SP)
- [ ] API-Endpoint: `DELETE /api/v1/users/me/data`
- [ ] Lösch-Anfrage (Soft-Delete)
- [ ] GM-Approval-Workflow
- [ ] Anonymisierung (statt Hard-Delete)
- [ ] Tests: Deletion-Tests

**T1.8.3: DSGVO-Compliance-Report** (3 SP)
- [ ] Report-Generator (PDF)
- [ ] Alle gesammelten Daten auflisten
- [ ] Speicherdauer
- [ ] Rechte
- [ ] Tests: Report-Tests

**T1.8.4: Cookie-Consent (Frontend)** (2 SP)
- [ ] Cookie-Banner
- [ ] Cookie-Settings
- [ ] Tests: Cookie-Tests

**T1.8.5: Privacy-Policy & Terms** (3 SP)
- [ ] Privacy-Policy-Seite
- [ ] Terms-of-Service-Seite
- [ ] Verlinkung im Footer
- [ ] Tests: Page-Tests

**Deliverables**:
- ✅ DSGVO vollständig implementiert

---

### Sprint 9: Spielleitung-Dashboard

**Zeitraum**: Woche 9  
**Sprint-Ziel**: Umfassendes GM-Dashboard  
**Story-Points**: 22 SP

#### Tasks

**T1.9.1: GM-Dashboard (Layout)** (4 SP)
- [ ] Filament-Page: `GameMasterDashboard`
- [ ] Layout: Karte (groß), Stats (rechts), Events (unten)
- [ ] Real-time-Updates (Livewire)
- [ ] Tests: Dashboard-Tests

**T1.9.2: Stats-Widgets** (5 SP)
- [ ] Widget: Runner-Status (Alive, Captured)
- [ ] Widget: Hunter-Status (Active, Locations)
- [ ] Widget: Game-Timer (verbleibende Zeit)
- [ ] Widget: Budget-Übersicht
- [ ] Widget: Violation-Count
- [ ] Tests: Widget-Tests

**T1.9.3: Quick-Actions** (4 SP)
- [ ] Button: Speedhunt starten
- [ ] Button: Spiel pausieren
- [ ] Button: Spiel beenden
- [ ] Button: Challenge erstellen
- [ ] Tests: Action-Tests

**T1.9.4: Participant-Management (Inline)** (4 SP)
- [ ] Teilnehmer-Liste (sortierbar, filterbar)
- [ ] Inline-Edit (Rolle ändern)
- [ ] Disqualifikation
- [ ] Tests: Participant-Management-Tests

**T1.9.5: Notification-Center** (3 SP)
- [ ] Notifications anzeigen (Violations, Emergencies)
- [ ] Notification-Archiv
- [ ] Tests: Notification-Tests

**T1.9.6: GM-Settings** (2 SP)
- [ ] Dashboard-Konfiguration (welche Widgets?)
- [ ] Notification-Settings
- [ ] Tests: Settings-Tests

**Deliverables**:
- ✅ GM-Dashboard (MVP)

---

### Sprint 10: Runner & Hunter Dashboards

**Zeitraum**: Woche 10  
**Sprint-Ziel**: Dashboards für Runner & Hunter  
**Story-Points**: 20 SP

#### Tasks

**T1.10.1: Runner-Dashboard** (8 SP)
- [ ] Filament-Page: `RunnerDashboard`
- [ ] Layout: Karte (mit eigener Position), Joker, Budget, Stats
- [ ] Widgets:
  - [ ] Meine Position
  - [ ] Meine Joker
  - [ ] Mein Budget
  - [ ] Meine Wegpunkte
  - [ ] Nächste Challenge
- [ ] Tests: Runner-Dashboard-Tests

**T1.10.2: Hunter-Dashboard** (8 SP)
- [ ] Filament-Page: `HunterDashboard`
- [ ] Layout: Karte (mit Runner-Pings), Hunter-Team, Stats
- [ ] Widgets:
  - [ ] Runner-Pings (Silenthunt)
  - [ ] Hunter-Team (Positionen)
  - [ ] Speedhunt-Queries
  - [ ] Captures-Log
  - [ ] Optional: Predictive-Analytics
- [ ] Tests: Hunter-Dashboard-Tests

**T1.10.3: Role-based-Routing** (2 SP)
- [ ] Auto-Redirect zu korrektem Dashboard (basierend auf Rolle)
- [ ] Tests: Routing-Tests

**T1.10.4: Mobile-Responsiveness** (2 SP)
- [ ] Alle Dashboards Mobile-optimiert
- [ ] Tests: Mobile-Tests

**Deliverables**:
- ✅ Runner & Hunter Dashboards

---

### Sprint 11-12: MVP Refinement

**Zeitraum**: Woche 11-12  
**Sprint-Ziel**: Bug-Fixes, Performance, MVP-Launch-ready  
**Story-Points**: 18 SP (pro Sprint = 36 SP gesamt)

#### Tasks (Sprint 11)

**T1.11.1: Bug-Fixes (Critical)** (8 SP)
- [ ] Bug-Backlog abarbeiten (Priorität: Critical)
- [ ] Tests: Bug-Fix-Verification

**T1.11.2: Performance-Optimierung** (5 SP)
- [ ] Database-Query-Optimierung (Eager-Loading)
- [ ] Caching-Strategy (Redis)
- [ ] API-Response-Time-Optimierung
- [ ] Tests: Performance-Tests

**T1.11.3: Code-Cleanup** (3 SP)
- [ ] Dead-Code entfernen
- [ ] Comments aufräumen
- [ ] Larastan (Level 5+)
- [ ] Pint (Code-Formatting)

**T1.11.4: Documentation-Update** (2 SP)
- [ ] README aktualisieren
- [ ] API-Docs aktualisieren
- [ ] Architecture-Docs aktualisieren

#### Tasks (Sprint 12)

**T1.12.1: Bug-Fixes (High + Medium)** (6 SP)
- [ ] Bug-Backlog (Priorität: High)
- [ ] Bug-Backlog (Priorität: Medium)

**T1.12.2: UAT-Support** (4 SP)
- [ ] User-Acceptance-Testing unterstützen
- [ ] Feedback einarbeiten

**T1.12.3: MVP-Launch-Checklist** (4 SP)
- [ ] Pre-Flight-Checklist durchgehen
- [ ] Deployment-Test (Staging)
- [ ] Rollback-Plan testen

**T1.12.4: Final-Review** (4 SP)
- [ ] Code-Review (alle PRs)
- [ ] Security-Audit (intern)
- [ ] Performance-Benchmarks

**Deliverables**:
- 🎉 **MVP LAUNCH-READY**

---

## Phase 2: Core Features (Sprints 13-24)

### Sprint 13-14: API-Dokumentation & Webhooks

**Zeitraum**: Woche 13-14  
**Story-Points**: 18 SP (pro Sprint)

#### Tasks (Sprint 13)

**T1.13.1: Swagger-Documentation (vollständig)** (5 SP)
- [ ] Alle Endpoints dokumentieren (Swagger/OpenAPI)
- [ ] Request/Response-Examples
- [ ] Authentication-Docs
- [ ] Try-it-out-Funktion
- [ ] Tests: API-Docs-Completeness

**T1.13.2: Webhook-System (Basis)** (6 SP)
- [ ] Webhook-Model & Migration
  - `id`, `url`, `events`, `secret`, `active`, timestamps
- [ ] Webhook-Delivery-Service
- [ ] Webhook-Signature (HMAC)
- [ ] Retry-Logic (failed webhooks)
- [ ] Tests: Webhook-Tests

**T1.13.3: Webhook-UI** (3 SP)
- [ ] Filament-Resource: `WebhookResource`
- [ ] Webhook-Test-Button
- [ ] Webhook-Logs
- [ ] Tests: UI-Tests

**T1.13.4: Webhook-Events** (4 SP)
- [ ] Event: `GameStarted`
- [ ] Event: `SpeedhuntStarted`
- [ ] Event: `RunnerCaptured`
- [ ] Tests: Event-Tests

#### Tasks (Sprint 14)

**T1.14.1: API-Rate-Limiting (erweitert)** (3 SP)
- [ ] Custom-Rate-Limits pro API-Key
- [ ] Rate-Limit-Dashboard
- [ ] Tests: Rate-Limit-Tests

**T1.14.2: API-Versioning (v2 Vorbereitung)** (3 SP)
- [ ] API-v2-Struktur anlegen
- [ ] Deprecation-Strategy
- [ ] Tests: Versioning-Tests

**T1.14.3: Postman-Collection** (2 SP)
- [ ] Collection erstellen
- [ ] Environments (dev, staging, prod)
- [ ] Tests exportieren

**T1.14.4: Third-Party-API-Keys** (5 SP)
- [ ] API-Key-Management (für externe Integrationen)
- [ ] Scopes (Read, Write)
- [ ] Key-Rotation
- [ ] Tests: API-Key-Tests

**T1.14.5: Documentation-Website** (5 SP)
- [ ] Docs-Website (separate Laravel-App oder Static Site)
- [ ] API-Reference
- [ ] Guides (Getting Started)
- [ ] Tests: Docs-Tests

**Deliverables**:
- ✅ API-Dokumentation vollständig
- ✅ Webhook-System

---

### Sprint 15-22: Support für andere Teams

**Zeitraum**: Woche 15-22 (8 Sprints)  
**Story-Points**: ~12-16 SP pro Sprint (Support-Tasks)

In diesen Sprints fokussiert sich Team 1 primär auf:
- Bug-Fixes
- Performance-Optimierung
- Support für Teams 2-6 (Code-Reviews, Architektur-Beratung)
- Kleinere Features nach Bedarf

#### Wiederkehrende Tasks (jeder Sprint)

**T1.X.1: Code-Reviews** (4 SP)
- [ ] PRs von Team 2 (GPS)
- [ ] PRs von Team 3 (Maps)
- [ ] PRs von Team 4 (Game Logic)
- [ ] PRs von Team 5 (Realtime)
- [ ] PRs von Team 6 (Integrations)

**T1.X.2: Bug-Fixes** (4 SP)
- [ ] Critical-Bugs
- [ ] High-Bugs

**T1.X.3: Performance-Monitoring** (2 SP)
- [ ] New-Relic/Sentry-Alerts bearbeiten
- [ ] Slow-Queries optimieren

**T1.X.4: Architecture-Support** (4 SP)
- [ ] Architektur-Fragen beantworten
- [ ] Design-Reviews

**T1.X.5: Feature-Requests** (2 SP)
- [ ] Kleine Features nach Bedarf
- [ ] Model-Changes für andere Teams

---

### Sprint 23-24: Phase 2 Cleanup

**Zeitraum**: Woche 23-24  
**Story-Points**: 20 SP (pro Sprint)

#### Tasks

**T1.23.1: Code-Refactoring** (8 SP)
- [ ] Code-Smells beseitigen
- [ ] DRY-Prinzip durchsetzen
- [ ] Tests: Refactoring-Tests

**T1.23.2: Performance-Profiling** (5 SP)
- [ ] Full-App-Profiling
- [ ] Bottlenecks identifizieren
- [ ] Optimierungen

**T1.23.3: Security-Review** (5 SP)
- [ ] OWASP-Top-10-Check
- [ ] Penetration-Testing (intern)
- [ ] Security-Fixes

**T1.23.4: Documentation-Update** (2 SP)
- [ ] Alle Docs aktualisieren
- [ ] Architecture-Diagramme

**Deliverables**:
- 🎉 **Phase 2 Complete**

---

## Phase 3: Advanced Features (Sprints 25-36)

### Sprint 25-26: ML-Infrastructure

**Zeitraum**: Woche 25-26  
**Story-Points**: 16 SP (pro Sprint)

#### Tasks

**T1.25.1: ML-Model-Infrastructure** (6 SP)
- [ ] Model-Storage (S3)
- [ ] Model-Versioning
- [ ] Model-Deployment-Pipeline
- [ ] Tests: ML-Infrastructure-Tests

**T1.25.2: Prediction-API** (5 SP)
- [ ] API-Endpoint: `POST /api/v1/predictions/movement`
- [ ] Request-Format
- [ ] Response-Format
- [ ] Tests: Prediction-API-Tests

**T1.25.3: ML-Settings** (3 SP)
- [ ] GM kann Predictive-Analytics an/aus schalten
- [ ] Settings-UI
- [ ] Tests: Settings-Tests

**T1.25.4: ML-Monitoring** (2 SP)
- [ ] Model-Performance-Tracking
- [ ] Prediction-Accuracy-Logs

---

### Sprint 27-36: Continued Support

**Zeitraum**: Woche 27-36  
**Story-Points**: ~12-16 SP pro Sprint

Fortsetzung von Support-Tasks, Bug-Fixes, Performance-Optimierung.

---

## Phase 4: Polish & Launch (Sprints 37-48)

### Sprint 37-48: Optimization & Launch

**Zeitraum**: Woche 37-48  
**Story-Points**: ~15-20 SP pro Sprint

#### Focus Areas

**Sprints 37-40: Performance**
- Database-Optimierung
- Caching-Strategy
- Load-Testing-Support

**Sprints 41-44: Stability**
- Bug-Fixes (alle Prioritäten)
- Edge-Cases
- Error-Handling

**Sprints 45-46: Documentation**
- Vollständige Docs
- Runbook
- Onboarding-Guides

**Sprints 47-48: Launch**
- Final-Checks
- Deployment-Dry-Runs
- Launch-Support

---

## Velocity-Tracking

| Sprint | Planned SP | Actual SP | Velocity | Notes |
|--------|------------|-----------|----------|-------|
| 1 | 20 | TBD | TBD | |
| 2 | 22 | TBD | TBD | |
| 3 | 24 | TBD | TBD | |
| ... | ... | ... | ... | |

**Target-Velocity**: 20-25 SP pro Sprint

---

**Letzte Aktualisierung**: 2025-01-13
**Team-Lead**: TBD
