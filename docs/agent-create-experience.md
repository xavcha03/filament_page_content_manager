# Agent IA — concevoir une nouvelle Experience

Une **Experience** est un template de page à **schéma figé** (défini en code). L’admin et le MCP ne peuvent que remplir les champs ; ils ne peuvent pas inventer la structure.

## Prérequis

- Design / maquette connus (sections, textes, images, CTAs)
- Décider de la clé kebab-case (ex. `home-organic`, `service-story`)
- Prévoir le composant Next.js du même nom conceptuel

## Backend (Laravel / projet hôte)

1. Générer le scaffold :
   ```bash
   php artisan page-content-manager:make-experience home-organic
   # optionnel : --with-media
   ```
2. Éditer `app/Experiences/{Name}Experience.php` :
   - `getKey()` / `getLabel()`
   - `make()` : liste **fixe** de champs Filament (TextInput, Textarea, MediaPicker, Repeaters **bornés** par le design — pas de builder de blocs page)
   - `transform(array $data)` : forme API consommée par le frontend (résoudre les médias comme les blocs)
   - `getMcpFields()` + `getMcpExample()` via `HasMcpMetadata`
3. Ne **jamais** exposer un moyen d’ajouter des champs dynamiquement en admin.
4. Vérifier la découverte : la classe dans `app/Experiences/` implémente `ExperienceInterface`.

## Frontend (Next.js / projet hôte)

1. Créer `frontend/components/experiences/HomeOrganicExperience.tsx` (nom aligné sur la clé)
2. Typer le `content` transformé
3. Enregistrer dans le registry Experiences (`key` → composant)
4. Animations (GSAP, Framer, Canvas) **dans** ce composant uniquement

Voir aussi `docs/agent-frontend-experiences.md` pour le branchement `content_mode`.

## Filament (utilisateur final)

1. Éditer / créer une page
2. Général → Mode de contenu = **Experience**
3. Choisir le **Modèle** (clé)
4. Onglet Contenu = formulaire fixe uniquement

## MCP (contenu seulement)

Workflow obligatoire :

1. `list_experiences`
2. `get_experience_schema` (champs autorisés)
3. `set_page_content_mode` si besoin (`experience` + `experience_key`)
4. `update_experience_fields` avec un merge partiel des **valeurs**

Interdit :

- Inventer des noms de champs absents du schéma
- « Ajouter une section » à une Experience
- Modifier la structure PHP via MCP

## Checklist alignement

- [ ] Clés PHP `make()` = clés `transform()` = props TS = `getMcpFields()`
- [ ] Médias : IDs MediaFile en admin → objets URL en API
- [ ] Page test en Filament + `GET /api/pages/{slug}` + rendu Next
- [ ] Switch vers blocs puis retour Experience : anciennes valeurs toujours présentes dans `experience_content[key]`
