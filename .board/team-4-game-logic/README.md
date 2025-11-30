# Team 4: Game Logic & Events

> Verantwortlich für Spielphasen, Regelwerk, Event-System, Joker, Challenges und Teams

## Team-Mitglieder

| Name | Role | Spezialisierung |
|------|------|-----------------|
| TBD | Senior Backend Developer (Lead) | Game-Logic, State-Machines |
| TBD | Mid-Level Developer | Event-Systems |
| TBD | Mid-Level Developer | Business-Logic |
| TBD | Mid-Level Developer | Regelwerk-Engine |
| TBD | Junior Developer | Testing |

**Team-Größe**: 5 Entwickler

## Verantwortungsbereiche

### Primär
1. **Spielphasen** - Setup, Active, Endgame, Post-Game
2. **Regelwerk** - Konfiguration, Templates, Enforcement
3. **Event-System** - Automatisch + Manuell, Timeline
4. **Szenario-Builder** - Proximity-Alerts, Triggers
5. **Joker-System** - 5 Standard + Custom-Joker, Marketplace
6. **Challenge-System** - Challenges erstellen, verwalten
7. **Team-Management** - Runner/Hunter-Teams
8. **Regelwerk-Enforcement** - Violations, GM-Review

### Sekundär
- Events für Timeline (Team 5)
- Game-State für API (Team 6)

## Dependencies

### Benötigt von
- **Team 1**: Game, Event-Models
- **Team 2**: Position-Data
- **Team 3**: Zone-Data

### Liefert an
- **Team 5**: Events für Broadcasting
- **Team 6**: Automatic-Timestamps

## Tech-Stack

- Laravel 12, State-Machines
- PostgreSQL, Redis
- Event-Sourcing (optional)

## Priorität

🟠 **HOCH**

## Sprint-Übersicht

| Sprint | Fokus |
|--------|-------|
| 1-2 | Game-Model, Spielphasen, Event-Model |
| 3-4 | Regelwerk, Participant-Zuweisung |
| 5-6 | Event-Timeline, Proximity-Events |
| 7-8 | Joker-System, Standard-Joker |
| 9-10 | Challenges, Teams |
| 13-14 | Custom-Joker, Marketplace |

**Details**: Siehe [sprint-plan.md](./sprint-plan.md)
