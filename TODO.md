# TODO

## A faire absolument (plus tard)

- Activer une vraie auth MCP (middleware `auth:*` avec scopes, gestion des tokens, revocation).
- Securiser le serveur MCP (auth, scopes, rate-limit, logs, audit trail).
- Controler l'acces a l'API pages (auth ou allowlist selon besoin).
- Ajouter des tests d'integration API (pages index/show, blocs, metadata).
- Ajouter des tests de securite basiques (MCP + API).
- Documenter clairement le modele de permissions (Filament, API, MCP).
- Stabiliser le schema de contenu (versioning, migration, compat).

## Idees de features / nouvelles logiques (ordre utilite + importance)

1. Permissions fines par bloc et par groupe (roles/abilities).
2. Draft / preview / publish pour les pages (workflow editorial).
3. Versioning de contenu + historique + rollback.
4. Validation par schema JSON et erreurs UI claires.
5. Internationalisation (multi-langue par page et par bloc).
6. SEO avance (meta auto, schema.org par type, OG dynamique).
7. Bloc “repeater” configurable (sections dynamiques par template).
8. Import / export de pages (JSON) avec validation et mapping medias.
9. Indexation / recherche full-text des contenus blocs.
10. Templates de page (starter layouts reutilisables).
11. “Content Diff” avant publication (comparaison visuelle).
12. Webhooks (page published, updated, deleted).
13. Cache HTTP / ETags / invalidation intelligente.
14. “Block analytics” (usage par page, frequence, performance).
15. Generator de docs automatique des blocs (schema + exemples).
