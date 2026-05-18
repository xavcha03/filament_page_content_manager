<?php

namespace Xavcha\PageContentManager\Http\Controllers\Api;

use Xavcha\PageContentManager\Http\Controllers\Controller;
use Xavcha\PageContentManager\Http\Resources\PageResource;
use Xavcha\PageContentManager\Models\Page;
use Xavcha\PageContentManager\Services\PagePreviewService;
use Xavcha\PageContentManager\Services\PageUrlResolver;
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
    public function show(Request $request, string $slug): JsonResponse
    {
        $previewToken = $request->query('preview_token');

        if (is_string($previewToken) && $previewToken !== '') {
            return $this->showPreview($previewToken, $slug);
        }

        $resolver = app(PageUrlResolver::class);
        $resolution = $resolver->resolve($slug);

        if ($resolution['resolution'] === PageUrlResolver::RESOLUTION_PAGE) {
            return response()->json(new PageResource($resolution['page']));
        }

        $response = response()->json(
            $resolver->toJsonResponsePayload($resolution),
            $resolution['http_status'],
        );

        $location = $resolver->redirectLocationHeader($resolution);

        if ($location !== null) {
            $response->header('Location', $location);
        }

        return $response;
    }

    protected function showPreview(string $previewToken, string $slug): JsonResponse
    {
        $preview = app(PagePreviewService::class);

        if (! $preview->isEnabled()) {
            return response()->json([
                'message' => 'La prévisualisation est désactivée.',
            ], 403);
        }

        $page = $preview->resolvePageFromToken($previewToken, $slug);

        if (! $page) {
            return response()->json([
                'message' => 'Token de prévisualisation invalide ou expiré.',
            ], 403);
        }

        $data = (new PageResource($page))->toArray(request());
        $data['preview'] = true;
        $data['page_status'] = $page->status;

        return response()
            ->json($data)
            ->header('X-Page-Preview', '1');
    }
}






