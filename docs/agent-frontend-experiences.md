# Agent IA — intégrer les Experiences côté frontend

Checklist pour mettre à jour le projet frontend (ex. `xavcha-base-site/frontend`) après activation du mode Experience dans le package `page-content-manager`.

## Objectif

Brancher le rendu Next.js sur `content_mode` sans casser les pages en mode blocs.

## Contrat API

`GET /api/pages/{slug}` expose désormais :

```json
{
  "content_mode": "blocks" | "experience",
  "sections": [ ... ],
  "experience": null | {
    "key": "home-organic",
    "content": { "...": "..." }
  }
}
```

Règles :

1. Si `content_mode === "blocks"` (ou absent / legacy) → pipeline **BlockRenderer** actuel **inchangé**.
2. Si `content_mode === "experience"` → **ne pas** rendre `sections` ; utiliser `experience.key` + `experience.content`.
3. Les URLs médias arrivent déjà résolues dans `experience.content` via `transform()` backend (même idée que les blocs).

## Étapes d’implémentation

1. **Types TS**  
   Étendre le type page avec `content_mode` et `experience?: { key: string; content: Record<string, unknown> } | null`.

2. **Registry Experiences**  
   Créer un mapping `key → composant`, par ex. :
   - `frontend/components/experiences/index.tsx`
   - `frontend/types/experiences.ts`

3. **ExperienceRenderer**  
   Composant qui :
   - lit `page.experience.key`
   - résout le composant dans le registry
   - passe `page.experience.content` en props
   - retourne `null` (ou fallback) si clé inconnue

4. **Point d’entrée page**  
   Là où les pages sont rendues aujourd’hui :
   ```ts
   if (page.content_mode === 'experience') {
     return <ExperienceRenderer experience={page.experience} />;
   }
   return <BlockRenderer sections={page.sections} />;
   ```

5. **Animations / Canvas / GSAP / Framer**  
   Les enfermer **dans** le composant Experience du projet, pas dans le package PHP.

6. **Preview**  
   Si une route `/preview/{slug}` existe, appliquer la même branche `content_mode`.

## Vérifications

- [ ] Page `content_mode=blocks` : rendu identique à avant
- [ ] Page `content_mode=experience` : composant dédié, pas de blocs
- [ ] Clé Experience inconnue : pas de crash (null / message)
- [ ] Médias : URLs absolues via helpers existants si besoin

## Hors scope

- Ne pas créer les Experiences PHP ici (voir `docs/agent-create-experience.md`)
- Ne pas modifier la structure Filament via le frontend
