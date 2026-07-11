# Prévisualisation frontend (brouillons / planifiées)

Le package génère des **tokens signés** pour consulter une page non publiée.  
Le frontend Next.js doit implémenter une route dédiée qui transmet ce token à l'API.

> **Sites Xavcha (starter)** : réexporter `@xavcha/frontend-core/app/preview` dans `app/preview/[...slug]/page.tsx` et déclarer `export const dynamic = 'force-dynamic'` dans ce fichier (Next.js 16). Voir [installation frontend-core](https://github.com/xavcha03/xavcha-frontend-core/blob/master/docs/installation.md).

## Flux

```
Filament « Prévisualiser »
    → https://monsite.com/preview/contact?preview_token=xxx
        → Next.js app/preview/[slug]/page.tsx
            → GET /api/pages/contact?preview_token=xxx
                → JSON page + preview: true
```

## API (fournie par le package)

### Requête

```
GET /api/pages/{slug}?preview_token={token}
```

- `{slug}` : slug de la page (`home` pour l'accueil)
- `{token}` : généré par le backend (Filament), valide **60 min** par défaut

### Réponses

| Cas | HTTP | Corps |
|-----|------|--------|
| Token valide | 200 | JSON page habituel + `preview: true`, `page_status` |
| Token invalide / expiré | 403 | `{ "message": "Token de prévisualisation invalide ou expiré." }` |
| Preview désactivée | 403 | `{ "message": "La prévisualisation est désactivée." }` |

Header optionnel : `X-Page-Preview: 1`

Sans `preview_token`, une page brouillon renvoie toujours **404** (comportement public inchangé).

## Configuration backend (.env)

```env
APP_FRONTEND_URL=https://monsite.com

PAGE_CONTENT_MANAGER_PREVIEW_ENABLED=true
PAGE_CONTENT_MANAGER_PREVIEW_SECRET=change-me-long-random-string
PAGE_CONTENT_MANAGER_PREVIEW_TTL=60
PAGE_CONTENT_MANAGER_PREVIEW_PATH=/preview
```

Publier la config si besoin :

```bash
php artisan vendor:publish --tag=page-content-manager-config
```

## À réaliser côté Next.js

### 1. Route preview

Créer par exemple `frontend/app/preview/[...slug]/page.tsx` (ou `preview/[slug]`).

```tsx
// Exemple minimal — adapter à votre fetchAPI / types
import { notFound } from 'next/navigation';
import { PageRenderer } from '@/components/PageRenderer';

export const dynamic = 'force-dynamic'; // pas de cache ISR sur la preview

interface PreviewPageProps {
  params: Promise<{ slug: string[] }>;
  searchParams: Promise<{ preview_token?: string }>;
}

export default async function PreviewPage({ params, searchParams }: PreviewPageProps) {
  const { slug } = await params;
  const { preview_token: previewToken } = await searchParams;

  if (!previewToken) {
    notFound();
  }

  const pageSlug = (slug?.length ? slug.join('/') : 'home');
  const apiUrl = `${process.env.NEXT_PUBLIC_API_URL}/pages/${pageSlug}?preview_token=${encodeURIComponent(previewToken)}`;

  const response = await fetch(apiUrl, {
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });

  if (response.status === 403 || response.status === 404) {
    notFound(); // ou page « Lien de prévisualisation expiré »
  }

  if (!response.ok) {
    throw new Error('Preview fetch failed');
  }

  const pageData = await response.json();

  if (!pageData.preview) {
    notFound();
  }

  return (
    <>
      {/* Bandeau optionnel */}
      <div className="bg-amber-500 text-black text-center py-2 text-sm" role="status">
        Mode prévisualisation — statut : {pageData.page_status}
      </div>
      <PageRenderer
        sections={pageData.sections}
        pageSlug={pageData.slug}
        pageType={pageData.type}
      />
    </>
  );
}
```

### 2. Métadonnées SEO

Sur les routes `/preview/*` :

```tsx
export async function generateMetadata() {
  return {
    title: 'Prévisualisation',
    robots: { index: false, follow: false },
  };
}
```

### 3. Ne pas mélanger avec le rendu public

- Ne pas utiliser la même logique ISR que `app/[...slug]/page.tsx` sans `preview_token`.
- Ne pas référencer `/preview/` dans le sitemap.
- Optionnel : middleware qui ajoute `X-Robots-Tag: noindex` sur `/preview`.

### 4. Variables d'environnement

Le frontend n'a besoin que de `NEXT_PUBLIC_API_URL` (déjà utilisé).  
Le secret reste **uniquement côté Laravel**.

### 5. Planifiées (`scheduled`)

Le token permet aussi de prévisualiser une page **planifiée** (non encore publiée selon `published_at`).

## Filament

- **Publiée** → bouton **Ouvrir** (URL publique)
- **Brouillon / planifiée** → bouton **Prévisualiser** (URL `/preview/...?preview_token=...`)

## Sécurité

- Partager le lien = donner accès temporaire à la page.
- Durée configurable (`PAGE_CONTENT_MANAGER_PREVIEW_TTL`).
- Pages en corbeille : pas de token généré.
