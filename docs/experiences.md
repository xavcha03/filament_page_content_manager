# Experiences (pages à schéma figé)

## Pourquoi

Deux façons de composer une page :

| Mode | `content_mode` | Éditeur Filament | Frontend |
|------|----------------|------------------|----------|
| **Blocs** | `blocks` | Builder de sections (existant) | `BlockRenderer` |
| **Experience** | `experience` | Formulaire fixe défini en PHP | Composant dédié (Canvas, GSAP, Framer, etc.) |

Le **type** de page (`home` / `standard`) reste le rôle métier. Il ne faut pas le confondre avec le mode de contenu.

## Ce qui est stocké

Sur `pages` :

- `content_mode` : `blocks` | `experience` (défaut `blocks`)
- `experience_key` : clé active (ex. `home-organic`), nullable
- `content` : blocs inchangés (`sections` + `metadata`)
- `experience_content` : JSON **indexé par clé** d’Experience

Exemple :

```json
{
  "home-organic": { "hero_title": "..." },
  "about-timeline": { "title": "...", "events": [] }
}
```

Changer de mode ou de modèle **conserve** les deux payloads. Seul le frontend utilise le mode actif.

## Filament

Toujours la ressource **Pages** (pas de ressource « Experiences ») :

1. Onglet **Général** : Mode de contenu + Modèle d’Experience
2. Onglet **SEO** : inchangé
3. Onglet **Contenu** :
   - mode blocs → Builder actuel
   - mode experience → champs figés de l’Experience (pas d’ajout/suppression/réordonnancement de structure)

## Côté projet

Créer les Experiences métier dans :

```text
app/Experiences/HomeOrganicExperience.php
```

Commande :

```bash
php artisan page-content-manager:make-experience home-organic
```

Le package fournit le moteur + une Experience Core de démo :

- clé : `demo`
- label : **Demo Experience**
- champs : surtitre, titre, intro, image, CTA, points forts

Pour la désactiver en prod : `disabled_experiences => ['demo']` dans la config.

Le package ne shippe pas de designs métier au-delà de cette démo.

## API

Voir `docs/api.md`. En résumé : `content_mode` + éventuellement `experience: { key, content }`.  
Quand `content_mode === experience`, le frontend doit **ignorer** `sections` pour le rendu (elles restent exposées pour rétrocompat).

## MCP

Les agents peuvent **éditer les valeurs** uniquement :

- `list_experiences`
- `get_experience_schema`
- `update_experience_fields`
- `set_page_content_mode`

Ils ne peuvent **pas** inventer ni modifier la structure (champs) d’une Experience.

## Guides agents

- Frontend : `docs/agent-frontend-experiences.md`
- Concevoir une Experience : `docs/agent-create-experience.md`
