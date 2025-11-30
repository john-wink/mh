# Team 7: DevOps & Infrastructure

> Verantwortlich für AWS-Infrastructure, Deployment, Monitoring und Performance

## Team-Mitglieder

| Name | Role | Spezialisierung |
|------|------|-----------------|
| TBD | Senior DevOps Engineer (Lead) | AWS, Lambda, Infrastructure |
| TBD | Mid-Level DevOps Engineer | CI/CD, Docker |
| TBD | Mid-Level DevOps Engineer | Monitoring, Performance |

**Team-Größe**: 3 Entwickler

## Verantwortungsbereiche

### Primär
1. **AWS-Infrastructure** - Lambda, RDS, S3, CloudFront
2. **Database** - PostgreSQL + PostGIS (RDS)
3. **Redis-Cluster** - Cache, Queue, WebSocket-State
4. **CI/CD** - GitHub Actions, Deployment-Pipeline
5. **Monitoring** - CloudWatch, Sentry
6. **Performance** - Load-Testing, Scaling
7. **Security** - IAM, Secrets-Manager
8. **Backup & Disaster-Recovery**

### Sekundär
- Infrastructure für alle Teams

## Dependencies

### Benötigt von
- Keine (unabhängig)

### Liefert an
- **Alle Teams** (Infrastructure)

## Tech-Stack

- AWS (Lambda, RDS, S3, CloudFront, CloudWatch)
- Terraform / CloudFormation
- GitHub Actions
- Docker
- Redis, PostgreSQL

## Priorität

🔴 **KRITISCH** - Blocker für alle Teams

## Sprint-Übersicht

| Sprint | Fokus |
|--------|-------|
| 1-2 | AWS-Setup, RDS, Redis, S3, CI/CD |
| 3-4 | Production-Environment |
| 5-6 | Performance-Monitoring |
| 7-10 | WebSocket-Scaling, S3-Multipart |
| 11-12 | Load-Testing MVP |
| 23-24 | Load-Testing Phase 2 |
| 37-40 | Infrastructure-Optimization |

**Details**: Siehe [sprint-plan.md](./sprint-plan.md)
