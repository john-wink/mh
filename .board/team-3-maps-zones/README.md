# Team 3: Maps & Zones

> Verantwortlich für Kartenoberfläche, Zonen-Management, Layer-System und Map-Features

## Team-Mitglieder

| Name | Role | Spezialisierung |
|------|------|-----------------|
| TBD | Senior Frontend Developer (Lead) | Mapbox, JavaScript |
| TBD | Mid-Level Full-Stack Developer | Laravel + JS |
| TBD | Mid-Level Frontend Developer | Livewire, Alpine.js |
| TBD | Junior Developer | UI/UX, Testing |

**Team-Größe**: 4 Entwickler

## Verantwortungsbereiche

### Primär
1. **Kartenoberfläche** - Mapbox GL JS Integration
2. **Zonen-Management** - 7 Zonen-Typen (Spielfeld, Restricted, etc.)
3. **Layer-System** - Zonen, Tracks, Heatmaps, POIs
4. **Interaktive Tools** - Zonen-Erstellung, Zeichnen
5. **3D-Features** - 3D-Buildings, Terrain
6. **Indoor-Mapping** - Shopping Malls, Airports
7. **Custom-Overlays** - Notizen, Fotos, Strategien

### Sekundär
- Zones für Regelwerk (Team 4)
- Map für Production (Team 6)

## Dependencies

### Benötigt von
- **Team 1**: Game, Zone-Models
- **Team 2**: Position-Data
- **Team 7**: CDN (Mapbox)

### Liefert an
- **Team 4**: Zone-Data für Events

## Tech-Stack

- Mapbox GL JS, Leaflet.js (Fallback)
- Livewire, Alpine.js
- PostgreSQL/PostGIS
- OpenStreetMap

## Priorität

🟠 **HOCH**

## Sprint-Übersicht

| Sprint | Fokus |
|--------|-------|
| 1-2 | Mapbox-Integration, Zone-Model |
| 3-4 | Zonen-Typen, CRUD |
| 5-6 | Interaktive Zonen-Erstellung, 3D |
| 7-8 | Indoor-Mapping (Vorbereitung) |
| 9-10 | Heatmaps, Weather-Layer |
| 17-18 | Indoor-Mapping (Implementation) |

**Details**: Siehe [sprint-plan.md](./sprint-plan.md)
