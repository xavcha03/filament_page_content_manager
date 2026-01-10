<?php

namespace Xavcha\PageContentManager\Blocks;

use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class BlockRegistry
{
    /**
     * @var array<string, class-string<BlockInterface>>
     */
    protected array $blocks = [];

    /**
     * @var bool
     */
    protected bool $autoDiscovered = false;

    /**
     * Enregistre un bloc.
     *
     * @param string $type Le type du bloc
     * @param class-string<BlockInterface> $blockClass La classe du bloc
     * @return void
     */
    public function register(string $type, string $blockClass): void
    {
        if (!is_subclass_of($blockClass, BlockInterface::class)) {
            throw new \InvalidArgumentException("La classe {$blockClass} doit implémenter BlockInterface");
        }

        $this->blocks[$type] = $blockClass;
    }

    /**
     * Récupère un bloc par son type.
     *
     * @param string $type Le type du bloc
     * @return class-string<BlockInterface>|null La classe du bloc ou null si non trouvé
     */
    public function get(string $type): ?string
    {
        $this->autoDiscoverBlocks();

        return $this->blocks[$type] ?? null;
    }

    /**
     * Récupère tous les blocs enregistrés.
     *
     * @return array<string, class-string<BlockInterface>>
     */
    public function all(): array
    {
        $this->autoDiscoverBlocks();

        return $this->blocks;
    }

    /**
     * Auto-découvre les blocs dans les dossiers Core et Custom.
     *
     * @return void
     */
    protected function autoDiscoverBlocks(): void
    {
        if ($this->autoDiscovered) {
            return;
        }

        // Vérifier si le cache est activé et si on n'est pas en environnement local
        $cacheEnabled = config('page-content-manager.cache.enabled', true);
        $cacheKey = config('page-content-manager.cache.key', 'page-content-manager.blocks.registry');
        $cacheTtl = config('page-content-manager.cache.ttl', 3600);
        $isLocal = app()->environment('local');

        // En développement local, on peut désactiver le cache pour détecter les nouveaux blocs
        if ($cacheEnabled && !$isLocal) {
            $cached = Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->discoverBlocks();
            });

            // Charger les blocs depuis le cache
            foreach ($cached as $type => $class) {
                $this->blocks[$type] = $class;
            }
        } else {
            // Mode sans cache (développement local ou cache désactivé)
            $this->discoverBlocks();
        }

        $this->autoDiscovered = true;
    }

    /**
     * Découvre les blocs dans les dossiers Core et Custom.
     *
     * @return array<string, class-string<BlockInterface>>
     */
    protected function discoverBlocks(): array
    {
        $blocks = [];

        // Chercher dans Core (package)
        $packageBlocksPath = __DIR__ . '/Core';
        if (File::exists($packageBlocksPath)) {
            $files = File::files($packageBlocksPath);
            foreach ($files as $file) {
                $className = 'Xavcha\\PageContentManager\\Blocks\\Core\\' . $file->getFilenameWithoutExtension();
                $type = $this->getBlockTypeIfValid($className);
                if ($type !== null) {
                    $blocks[$type] = $className;
                }
            }
        }

        // Chercher dans Custom (application)
        $customBlocksPath = app_path('Blocks/Custom');
        if (File::exists($customBlocksPath)) {
            $files = File::files($customBlocksPath);
            foreach ($files as $file) {
                $className = 'App\\Blocks\\Custom\\' . $file->getFilenameWithoutExtension();
                $type = $this->getBlockTypeIfValid($className);
                if ($type !== null) {
                    $blocks[$type] = $className;
                }
            }
        }

        // Filtrer les blocs désactivés si la configuration existe
        $disabledBlocks = config('page-content-manager.disabled_blocks', []);
        if (!empty($disabledBlocks) && is_array($disabledBlocks)) {
            $blocks = array_filter($blocks, function ($type) use ($disabledBlocks) {
                return !in_array($type, $disabledBlocks, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        // Enregistrer les blocs découverts
        foreach ($blocks as $type => $className) {
            $this->blocks[$type] = $className;
        }

        return $blocks;
    }

    /**
     * Récupère le type d'un bloc si la classe est valide.
     *
     * @param string $className
     * @return string|null Le type du bloc ou null si invalide
     */
    protected function getBlockTypeIfValid(string $className): ?string
    {
        if (!class_exists($className)) {
            return null;
        }

        $reflection = new \ReflectionClass($className);
        
        // Ignorer les classes abstraites et interfaces
        if ($reflection->isAbstract() || 
            $reflection->isInterface() ||
            !$reflection->implementsInterface(\Xavcha\PageContentManager\Blocks\Contracts\BlockInterface::class)) {
            return null;
        }

        try {
            return $className::getType();
        } catch (\Throwable $e) {
            // Ignorer les erreurs
            return null;
        }
    }

    /**
     * Enregistre un bloc si la classe est valide.
     *
     * @param string $className
     * @return void
     */
    protected function registerBlockIfValid(string $className): void
    {
        $type = $this->getBlockTypeIfValid($className);
        if ($type !== null) {
            $this->register($type, $className);
        }
    }

    /**
     * Invalide le cache des blocs.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $cacheKey = config('page-content-manager.cache.key', 'page-content-manager.blocks.registry');
        Cache::forget($cacheKey);
        
        // Réinitialiser l'état de découverte pour forcer une nouvelle découverte
        $this->autoDiscovered = false;
        $this->blocks = [];
    }
}



