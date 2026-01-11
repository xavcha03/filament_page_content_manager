<?php

namespace Xavcha\PageContentManager\Blocks;

use Illuminate\Support\Facades\Log;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class BlockValidator
{
    /**
     * Valide un bloc.
     *
     * @param string $type Le type du bloc
     * @param string $blockClass La classe du bloc
     * @return array Tableau avec 'errors', 'warnings' et 'valid'
     */
    public static function validate(string $type, string $blockClass): array
    {
        $errors = [];
        $warnings = [];

        if (!class_exists($blockClass)) {
            $errors[] = "La classe {$blockClass} n'existe pas";
            return ['errors' => $errors, 'warnings' => $warnings, 'valid' => false];
        }

        $reflection = new \ReflectionClass($blockClass);

        // Vérifier que c'est une classe concrète
        if ($reflection->isAbstract() || $reflection->isInterface()) {
            $errors[] = "La classe {$blockClass} est abstraite ou une interface";
            return ['errors' => $errors, 'warnings' => $warnings, 'valid' => false];
        }

        // Vérifier l'implémentation de BlockInterface
        if (!$reflection->implementsInterface(BlockInterface::class)) {
            $errors[] = "La classe {$blockClass} n'implémente pas BlockInterface";
            return ['errors' => $errors, 'warnings' => $warnings, 'valid' => false];
        }

        // Vérifier les méthodes requises
        $requiredMethods = ['getType', 'make', 'transform'];

        foreach ($requiredMethods as $method) {
            if (!method_exists($blockClass, $method)) {
                $errors[] = "La méthode {$method}() est manquante";
            } elseif (!$reflection->getMethod($method)->isStatic()) {
                $warnings[] = "La méthode {$method}() devrait être statique";
            }
        }

        // Vérifier que getType() retourne le bon type
        try {
            $actualType = $blockClass::getType();
            if ($actualType !== $type) {
                $warnings[] = "Type mismatch: attendu '{$type}', obtenu '{$actualType}'";
            }
        } catch (\Throwable $e) {
            $errors[] = "Erreur lors de l'appel à getType(): " . $e->getMessage();
        }

        // Vérifier que make() retourne un Block valide
        try {
            $block = $blockClass::make();
            if (!$block instanceof \Filament\Forms\Components\Builder\Block) {
                $warnings[] = "La méthode make() ne retourne pas une instance de Block";
            }
        } catch (\Throwable $e) {
            $warnings[] = "Erreur lors de l'appel à make(): " . $e->getMessage();
        }

        // Vérifier que transform() retourne un array
        try {
            $transformed = $blockClass::transform([]);
            if (!is_array($transformed)) {
                $warnings[] = "La méthode transform() ne retourne pas un array";
            } elseif (!isset($transformed['type'])) {
                $warnings[] = "La méthode transform() devrait retourner un array avec la clé 'type'";
            }
        } catch (\Throwable $e) {
            $warnings[] = "Erreur lors de l'appel à transform(): " . $e->getMessage();
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'valid' => empty($errors),
        ];
    }

    /**
     * Valide tous les blocs d'un registry.
     *
     * @param BlockRegistry $registry
     * @param bool $throwOnError Si true, lance une exception en cas d'erreur
     * @return array Tableau avec 'valid', 'warnings', 'errors' et 'results'
     */
    public static function validateAll(BlockRegistry $registry, bool $throwOnError = false): array
    {
        $allBlocks = $registry->all();
        $results = [];
        $validCount = 0;
        $warningCount = 0;
        $errorCount = 0;

        foreach ($allBlocks as $type => $blockClass) {
            $validation = self::validate($type, $blockClass);

            $status = 'valid';
            if (!empty($validation['errors'])) {
                $status = 'error';
                $errorCount++;
            } elseif (!empty($validation['warnings'])) {
                $status = 'warning';
                $warningCount++;
            } else {
                $validCount++;
            }

            $results[] = [
                'type' => $type,
                'status' => $status,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
            ];

            // Logger les warnings
            if (!empty($validation['warnings'])) {
                Log::warning("Bloc {$type} a des avertissements", [
                    'type' => $type,
                    'class' => $blockClass,
                    'warnings' => $validation['warnings'],
                ]);
            }

            // Logger les erreurs
            if (!empty($validation['errors'])) {
                Log::error("Bloc {$type} a des erreurs", [
                    'type' => $type,
                    'class' => $blockClass,
                    'errors' => $validation['errors'],
                ]);

                // Lancer une exception si demandé
                if ($throwOnError) {
                    throw new \RuntimeException(
                        "Bloc {$type} ({$blockClass}) a des erreurs: " . implode(', ', $validation['errors'])
                    );
                }
            }
        }

        return [
            'valid' => $validCount,
            'warnings' => $warningCount,
            'errors' => $errorCount,
            'results' => $results,
        ];
    }
}




