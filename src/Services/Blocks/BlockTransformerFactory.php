<?php

namespace Xavcha\PageContentManager\Services\Blocks;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;
use Xavcha\PageContentManager\Services\Blocks\Transformers\DefaultBlockTransformer;
use Illuminate\Support\Facades\File;

class BlockTransformerFactory
{
    /**
     * @var array<string, BlockTransformerInterface>
     */
    protected array $transformers = [];

    /**
     * @var bool
     */
    protected bool $autoDiscovered = false;

    public function __construct()
    {
        $this->registerDefaultTransformer();
    }

    /**
     * Enregistre un transformer pour un type de bloc.
     *
     * @param string $type Le type de bloc
     * @param BlockTransformerInterface $transformer Le transformer
     * @return void
     */
    public function register(string $type, BlockTransformerInterface $transformer): void
    {
        $this->transformers[$type] = $transformer;
    }

    /**
     * Récupère le transformer pour un type de bloc.
     *
     * @param string $type Le type de bloc
     * @return BlockTransformerInterface Le transformer (ou DefaultBlockTransformer si non trouvé)
     */
    public function getTransformer(string $type): BlockTransformerInterface
    {
        $this->autoDiscoverTransformers();

        return $this->transformers[$type] ?? new DefaultBlockTransformer($type);
    }

    /**
     * Auto-découvre les transformers dans les dossiers Core et Custom.
     *
     * @return void
     */
    protected function autoDiscoverTransformers(): void
    {
        if ($this->autoDiscovered) {
            return;
        }

        // Chercher dans Core (package)
        $packageTransformersPath = __DIR__ . '/Transformers/Core';
        if (File::exists($packageTransformersPath)) {
            $files = File::files($packageTransformersPath);
            foreach ($files as $file) {
                $className = 'Xavcha\\PageContentManager\\Services\\Blocks\\Transformers\\Core\\' . $file->getFilenameWithoutExtension();
                $this->registerTransformerIfValid($className);
            }
        }

        // Chercher dans Core et Custom (application)
        $transformersPaths = [
            app_path('Services/Blocks/Transformers/Core'),
            app_path('Services/Blocks/Transformers/Custom'),
        ];

        foreach ($transformersPaths as $transformersPath) {
            if (!File::exists($transformersPath)) {
                continue;
            }

            $files = File::files($transformersPath);

            foreach ($files as $file) {
                $namespace = str_contains($file->getPath(), 'Custom') 
                    ? 'App\\Services\\Blocks\\Transformers\\Custom\\' 
                    : 'App\\Services\\Blocks\\Transformers\\Core\\';
                
                $className = $namespace . $file->getFilenameWithoutExtension();
                $this->registerTransformerIfValid($className);
            }
        }

        $this->autoDiscovered = true;
    }

    /**
     * Enregistre un transformer si la classe est valide.
     *
     * @param string $className
     * @return void
     */
    protected function registerTransformerIfValid(string $className): void
    {
        if (!class_exists($className)) {
            return;
        }

        $reflection = new \ReflectionClass($className);
        
        // Ignorer les classes abstraites, interfaces et DefaultBlockTransformer
        if ($reflection->isAbstract() || 
            $reflection->isInterface() || 
            $className === DefaultBlockTransformer::class ||
            !$reflection->implementsInterface(BlockTransformerInterface::class)) {
            return;
        }

        try {
            $transformer = new $className();
            
            if ($transformer instanceof BlockTransformerInterface) {
                $this->register($transformer->getType(), $transformer);
            }
        } catch (\Throwable $e) {
            // Ignorer les erreurs d'instanciation
            return;
        }
    }

    /**
     * Enregistre le transformer par défaut.
     *
     * @return void
     */
    protected function registerDefaultTransformer(): void
    {
        // DefaultBlockTransformer sera retourné via getTransformer si non trouvé
        // Pas besoin de l'enregistrer ici
    }
}

