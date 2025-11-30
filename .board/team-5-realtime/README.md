# Team 5: Real-time Communication & WebSockets

> Verantwortlich für Chat, Push-Notifications, WebSocket-Infrastructure und Real-time Updates

## Team-Mitglieder

| Name | Role | Spezialisierung |
|------|------|-----------------|
| TBD | Senior Full-Stack Developer (Lead) | WebSockets, Real-time |
| TBD | Mid-Level Backend Developer | Laravel Broadcasting |
| TBD | Mid-Level Frontend Developer | JavaScript, WebSocket-Client |
| TBD | Junior Developer | Testing |

**Team-Größe**: 4 Entwickler

## Verantwortungsbereiche

### Primär
1. **Chat-System** - Gruppen-Chat, DMs, Rich-Media
2. **WebSocket-Infrastructure** - Laravel Reverb/Soketi
3. **Push-Notifications** - Web, Mobile (Firebase)
4. **Real-time Updates** - Position, Events, Chat
5. **Broadcasting** - Channels, Events, Presence
6. **Moderation** - GM kann Chats lesen/ändern/löschen
7. **Chat-History** - Export, Search

### Sekundär
- WebSocket-Updates für Map (Team 3)
- WebSocket-Events von Game-Logic (Team 4)

## Dependencies

### Benötigt von
- **Team 1**: Auth, Permissions
- **Team 7**: Redis, Reverb-Setup

### Liefert an
- Alle Teams (WebSocket-Infrastructure)

## Tech-Stack

- Laravel Reverb / Soketi
- Redis (Pub/Sub)
- Livewire, Alpine.js
- Firebase Cloud Messaging

## Priorität

🟠 **HOCH**

## Sprint-Übersicht

| Sprint | Fokus |
|--------|-------|
| 1-2 | Reverb-Setup, Basis-Broadcasting |
| 3-4 | Chat-Model, Gruppen-Chat |
| 5-6 | Chat-Moderation, Push-Notifications |
| 7-8 | Typing-Indicators, Rich-Media |
| 9-10 | Custom-Chat-Räume, @Mentions |
| 15-16 | Mobile-Push (Firebase) |
| 19-20 | Delayed-Live-View (Zuschauer) |

**Details**: Siehe [sprint-plan.md](./sprint-plan.md)
