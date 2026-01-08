<?php

namespace Xavcha\PageContentManager\Blocks;

use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;
use Illuminate\Support\Facades\File;

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

        // Chercher dans Core (package)
        $packageBlocksPath = __DIR__ . '/Core';
        if (File::exists($packageBlocksPath)) {
            $files = File::files($packageBlocksPath);
            foreach ($files as $file) {
                $className = 'Xavcha\\PageContentManager\\Blocks\\Core\\' . $file->getFilenameWithoutExtension();
                $this->registerBlockIfValid($className);
            }
        }

        // Chercher dans Custom (application)
        $customBlocksPath = app_path('Blocks/Custom');
        if (File::exists($customBlocksPath)) {
            $files = File::files($customBlocksPath);
            foreach ($files as $file) {
                $className = 'App\\Blocks\\Custom\\' . $file->getFilenameWithoutExtension();
                $this->registerBlockIfValid($className);
            }
        }

        $this->autoDiscovered = true;
    }

    /**
     * Enregistre un bloc si la classe est valide.
     *
     * @param string $className
     * @return void
     */
    protected function registerBlockIfValid(string $className): void
    {
        if (!class_exists($className)) {
            return;
        }

        $reflection = new \ReflectionClass($className);
        
        // Ignorer les classes abstraites et interfaces
        if ($reflection->isAbstract() || 
            $reflection->isInterface() ||
            !$reflection->implementsInterface(\Xavcha\PageContentManager\Blocks\Contracts\BlockInterface::class)) {
            return;
        }

        try {
            $type = $className::getType();
            $this->register($type, $className);
        } catch (\Throwable $e) {
            // Ignorer les erreurs
            return;
        }
    }
}

