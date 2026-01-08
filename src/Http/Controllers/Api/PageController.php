<?php

namespace Xavcha\PageContentManager\Http\Controllers\Api;

use Xavcha\PageContentManager\Http\Controllers\Controller;
use Xavcha\PageContentManager\Http\Resources\PageResource;
use Xavcha\PageContentManager\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Liste toutes les pages publiées (pour le menu de navigation).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $pages = Page::published()
            ->select('id', 'title', 'slug', 'type')
            ->orderByRaw("CASE WHEN type = 'home' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->get();

        return response()->json([
            'pages' => $pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug ?: 'home',
                    'type' => $page->type,
                ];
            }),
        ]);
    }

    /**
     * Affiche une page par son slug.
     *
     * @param string $slug Le slug de la page
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        // Gérer le cas spécial de la page Home (slug vide ou "home")
        if ($slug === 'home' || $slug === '') {
            $page = Page::where('type', 'home')
                ->published()
                ->first();
        } else {
            $page = Page::where('slug', $slug)
                ->published()
                ->first();
        }

        if (!$page) {
            return response()->json([
                'message' => 'Page non trouvée',
            ], 404);
        }

        return response()->json(new PageResource($page));
    }
}

