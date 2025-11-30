# Team 6: Integrations & External APIs

> Verantwortlich für Banking, Video-Upload, Streaming, Weather und alle externen Integrationen

## Team-Mitglieder

| Name | Role | Spezialisierung |
|------|------|-----------------|
| TBD | Senior Backend Developer (Lead) | API-Integration |
| TBD | Mid-Level Developer | Banking-APIs |
| TBD | Mid-Level Developer | AWS S3, Video |
| TBD | Junior Developer | Testing |

**Team-Größe**: 4 Entwickler

## Verantwortungsbereiche

### Primär
1. **Banking-Integration** - Revolut, Bunq APIs
2. **Transaction-Tracking** - Webhooks, Budget
3. **Video-Upload** - S3 Chunked Upload, Pre-signed URLs
4. **Video-Streaming** - YouTube, Twitch, RTMP
5. **Transcoding** - AWS MediaConvert
6. **Weather-API** - OpenWeatherMap
7. **Export-Funktionen** - GPX, KML, XML (FCPro, Premiere)
8. **REST-API** - Externe Consumers
9. **Webhooks** - Event-Notifications

### Sekundär
- Transaction-Events für Game-Logic (Team 4)
- Video-Metadata für Timeline (Team 4)

## Dependencies

### Benötigt von
- **Team 1**: API-Basis, Webhook-System
- **Team 7**: S3, MediaConvert

### Liefert an
- **Team 4**: Transaction-Events, Timestamps

## Tech-Stack

- Laravel 12, Guzzle
- AWS S3, MediaConvert
- Revolut API, Bunq API
- YouTube/Twitch APIs
- OpenWeatherMap API

## Priorität

🟡 **MITTEL** (Startet Sprint 13)

## Sprint-Übersicht

| Sprint | Fokus |
|--------|-------|
| 13-14 | Revolut-Integration, Transaction-Model |
| 15-16 | Bunq, Budget-Management |
| 17-18 | S3-Chunked-Upload, Video |
| 19-20 | Streaming (YouTube, Twitch) |
| 21-22 | Export-Formate (GPX, XML) |
| 25-26 | Weather-API |

**Details**: Siehe [sprint-plan.md](./sprint-plan.md)
