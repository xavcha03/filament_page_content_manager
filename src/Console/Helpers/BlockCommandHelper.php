<?php

namespace Xavcha\PageContentManager\Console\Helpers;

use Illuminate\Console\Command;
use Xavcha\PageContentManager\Blocks\BlockRegistry;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class BlockCommandHelper
{
    /**
     * Détermine si la commande doit fonctionner en mode non-interactif.
     *
     * @param Command $command
     * @return bool
     */
    public static function isNonInteractive(Command $command): bool
    {
        // Vérifier --no-interaction (option standard Laravel)
        if ($command->option('no-interaction')) {
            return true;
        }

        // Vérifier --force si l'option existe
        try {
            if ($command->option('force')) {
                return true;
            }
        } catch (\InvalidArgumentException $e) {
            // L'option n'existe pas, continuer
        }

        // Vérifier --json si l'option existe
        try {
            if ($command->option('json')) {
                return true;
            }
        } catch (\InvalidArgumentException $e) {
            // L'option n'existe pas, continuer
        }

        return false;
    }

    /**
     * Retourne une réponse JSON standardisée.
     *
     * @param bool $success
     * @param mixed $data
     * @param array $errors
     * @param array $warnings
     * @param string|null $message
     * @return array
     */
    public static function jsonResponse(
        bool $success = true,
        $data = null,
        array $errors = [],
        array $warnings = [],
        ?string $message = null
    ): array {
        $response = [
            'success' => $success,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Récupère les informations détaillées d'un bloc.
     *
     * @param BlockRegistry $registry
     * @param string $type
     * @return array|null
     */
    public static function getBlockInfo(BlockRegistry $registry, string $type): ?array
    {
        $blockClass = $registry->get($type);

        if (!$blockClass) {
            return null;
        }

        $reflection = new \ReflectionClass($blockClass);
        $disabledBlocks = config('page-content-manager.disabled_blocks', []);
        $isDisabled = in_array($type, $disabledBlocks, true);

        $info = [
            'type' => $type,
            'class' => class_basename($blockClass),
            'namespace' => $reflection->getNamespaceName(),
            'status' => $isDisabled ? 'disabled' : 'active',
        ];

        // Ordre (si méthode existe)
        if (method_exists($blockClass, 'getOrder')) {
            try {
                $info['order'] = $blockClass::getOrder();
            } catch (\Throwable $e) {
                $info['order'] = 100;
            }
        } else {
            $info['order'] = 100;
        }

        // Groupe (si méthode existe)
        if (method_exists($blockClass, 'getGroup')) {
            try {
                $info['group'] = $blockClass::getGroup();
            } catch (\Throwable $e) {
                $info['group'] = null;
            }
        } else {
            $info['group'] = null;
        }

        // Source (Core ou Custom)
        if (str_starts_with($blockClass, 'Xavcha\\PageContentManager\\Blocks\\Core\\')) {
            $info['source'] = 'core';
        } else {
            $info['source'] = 'custom';
        }

        // Champs du formulaire (si possible)
        try {
            $block = $blockClass::make();
            $schema = $block->getSchema();
            $fields = [];

            foreach ($schema as $field) {
                $fieldInfo = [
                    'name' => $field->getName(),
                    'type' => class_basename(get_class($field)),
                ];

                if (method_exists($field, 'isRequired')) {
                    $fieldInfo['required'] = $field->isRequired();
                }

                $fields[] = $fieldInfo;
            }

            $info['fields'] = $fields;
        } catch (\Throwable $e) {
            $info['fields'] = [];
        }

        // Validation de l'implémentation
        $info['has_transform'] = method_exists($blockClass, 'transform');
        $info['has_validation'] = method_exists($blockClass, 'validate');

        return $info;
    }

    /**
     * Récupère les statistiques des blocs.
     *
     * @param BlockRegistry $registry
     * @return array
     */
    public static function getStats(BlockRegistry $registry): array
    {
        $allBlocks = $registry->all();
        $disabledBlocks = config('page-content-manager.disabled_blocks', []);

        $core = 0;
        $custom = 0;
        $active = 0;
        $disabled = 0;

        $byGroup = [];
        $usage = [];

        foreach ($allBlocks as $type => $blockClass) {
            // Compter Core vs Custom
            if (str_starts_with($blockClass, 'Xavcha\\PageContentManager\\Blocks\\Core\\')) {
                $core++;
            } else {
                $custom++;
            }

            // Compter actifs vs désactivés
            if (in_array($type, $disabledBlocks, true)) {
                $disabled++;
            } else {
                $active++;
            }

            // Grouper par groupe
            if (method_exists($blockClass, 'getGroup')) {
                try {
                    $group = $blockClass::getGroup() ?? 'other';
                    if (!isset($byGroup[$group])) {
                        $byGroup[$group] = 0;
                    }
                    $byGroup[$group]++;
                } catch (\Throwable $e) {
                    // Ignorer
                }
            }

            // Compter l'utilisation dans les pages (si possible)
            try {
                $pageModel = config('page-content-manager.models.page');
                if ($pageModel && class_exists($pageModel)) {
                    // Rechercher dans les sections JSON
                    $pages = $pageModel::whereNotNull('content')->get();
                    $count = 0;
                    foreach ($pages as $page) {
                        $content = $page->content;
                        if (is_array($content) && isset($content['sections']) && is_array($content['sections'])) {
                            foreach ($content['sections'] as $section) {
                                if (isset($section['type']) && $section['type'] === $type) {
                                    $count++;
                                    break; // Compter la page une seule fois
                                }
                            }
                        }
                    }
                    if ($count > 0) {
                        $usage[$type] = $count;
                    }
                }
            } catch (\Throwable $e) {
                // Ignorer si le modèle n'existe pas ou erreur
            }
        }

        return [
            'total' => count($allBlocks),
            'core' => $core,
            'custom' => $custom,
            'active' => $active,
            'disabled' => $disabled,
            'by_group' => $byGroup,
            'usage' => $usage,
        ];
    }

    /**
     * Valide un bloc.
     *
     * @param string $type
     * @param string $blockClass
     * @return array
     */
    public static function validateBlock(string $type, string $blockClass): array
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
            $returnedType = $blockClass::getType();
            if ($returnedType !== $type) {
                $warnings[] = "Type mismatch : attendu '{$type}', obtenu '{$returnedType}'";
            }
        } catch (\Throwable $e) {
            $errors[] = "Erreur lors de l'appel à getType() : " . $e->getMessage();
        }

        // Vérifier que transform() retourne un array avec 'type'
        try {
            $testData = ['test' => 'data'];
            $transformed = $blockClass::transform($testData);
            if (!is_array($transformed)) {
                $errors[] = "La méthode transform() doit retourner un array";
            } elseif (!isset($transformed['type'])) {
                $errors[] = "La méthode transform() doit retourner un array avec la clé 'type'";
            } elseif ($transformed['type'] !== $type) {
                $warnings[] = "Le type retourné par transform() ('{$transformed['type']}') ne correspond pas au type du bloc ('{$type}')";
            }
        } catch (\Throwable $e) {
            $warnings[] = "Impossible de tester transform() : " . $e->getMessage();
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'valid' => empty($errors),
        ];
    }

    /**
     * Convertit un nom en kebab-case.
     *
     * @param string $name
     * @return string
     */
    public static function toKebabCase(string $name): string
    {
        // Enlever les espaces et caractères spéciaux
        $name = preg_replace('/[^a-zA-Z0-9]+/', '-', $name);
        // Convertir en minuscules
        $name = strtolower($name);
        // Enlever les tirets en début/fin
        return trim($name, '-');
    }

    /**
     * Convertit un nom en PascalCase.
     *
     * @param string $name
     * @return string
     */
    public static function toPascalCase(string $name): string
    {
        // Enlever les espaces et caractères spéciaux, remplacer par espaces
        $name = preg_replace('/[^a-zA-Z0-9]+/', ' ', $name);
        // Convertir en mots capitalisés
        $name = ucwords(strtolower($name));
        // Enlever les espaces
        return str_replace(' ', '', $name);
    }

    /**
     * Vérifie si un bloc existe déjà.
     *
     * @param BlockRegistry $registry
     * @param string $type
     * @return bool
     */
    public static function blockExists(BlockRegistry $registry, string $type): bool
    {
        return $registry->get($type) !== null;
    }

    /**
     * Vérifie si un fichier de bloc existe déjà.
     *
     * @param string $blockName
     * @return bool
     */
    public static function blockFileExists(string $blockName): bool
    {
        $path = app_path("Blocks/Custom/{$blockName}Block.php");
        return file_exists($path);
    }

    /**
     * Trouve des blocs similaires à un type donné (pour suggestions).
     *
     * @param BlockRegistry $registry
     * @param string $type
     * @param int $limit
     * @return array
     */
    public static function findSimilarBlocks(BlockRegistry $registry, string $type, int $limit = 3): array
    {
        $allBlocks = $registry->all();
        $similar = [];

        foreach ($allBlocks as $blockType => $blockClass) {
            $similarity = 0;

            // Calcul de similarité simple (Levenshtein)
            $distance = levenshtein(strtolower($type), strtolower($blockType));
            $maxLength = max(strlen($type), strlen($blockType));
            
            if ($maxLength > 0) {
                $similarity = 1 - ($distance / $maxLength);
            }

            // Vérifier si le type contient ou est contenu dans le type recherché
            if (stripos($blockType, $type) !== false || stripos($type, $blockType) !== false) {
                $similarity += 0.3;
            }

            if ($similarity > 0.3) {
                $similar[] = [
                    'type' => $blockType,
                    'similarity' => $similarity,
                ];
            }
        }

        // Trier par similarité décroissante
        usort($similar, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Retourner les N premiers
        return array_slice($similar, 0, $limit);
    }
}

