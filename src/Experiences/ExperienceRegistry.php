<?php

declare(strict_types=1);

namespace Xavcha\PageContentManager\Experiences;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Xavcha\PageContentManager\Experiences\Contracts\ExperienceInterface;

class ExperienceRegistry
{
    /**
     * @var array<string, class-string<ExperienceInterface>>
     */
    protected array $experiences = [];

    protected bool $autoDiscovered = false;

    /**
     * @param  class-string<ExperienceInterface>  $experienceClass
     */
    public function register(string $key, string $experienceClass): void
    {
        if (! is_subclass_of($experienceClass, ExperienceInterface::class)) {
            throw new \InvalidArgumentException("La classe {$experienceClass} doit implémenter ExperienceInterface");
        }

        $this->experiences[$key] = $experienceClass;
    }

    /**
     * @return class-string<ExperienceInterface>|null
     */
    public function get(string $key): ?string
    {
        $this->autoDiscoverExperiences();

        $class = $this->experiences[$key] ?? null;

        if ($class !== null && ! class_exists($class)) {
            unset($this->experiences[$key]);

            return null;
        }

        return $class;
    }

    /**
     * @return array<string, class-string<ExperienceInterface>>
     */
    public function all(): array
    {
        $this->autoDiscoverExperiences();

        return $this->experiences;
    }

    public function has(string $key): bool
    {
        $this->autoDiscoverExperiences();

        return isset($this->experiences[$key]) && class_exists($this->experiences[$key]);
    }

    /**
     * @return array<string, string> key => label
     */
    public function options(): array
    {
        $options = [];
        foreach ($this->all() as $key => $class) {
            try {
                $options[$key] = $class::getLabel();
            } catch (\Throwable) {
                $options[$key] = $key;
            }
        }

        return $options;
    }

    protected function autoDiscoverExperiences(): void
    {
        if ($this->autoDiscovered) {
            return;
        }

        $cacheEnabled = config('page-content-manager.experiences.cache.enabled', true);
        $cacheKey = config('page-content-manager.experiences.cache.key', 'page-content-manager.experiences.registry');
        $cacheTtl = config('page-content-manager.experiences.cache.ttl', 3600);
        $isLocal = app()->environment('local');

        if ($cacheEnabled && ! $isLocal) {
            $cached = Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->discoverExperiences();
            });

            foreach ($cached as $key => $class) {
                $this->experiences[$key] = $class;
            }
        } else {
            $this->discoverExperiences();
        }

        $this->autoDiscovered = true;
    }

    /**
     * @return array<string, class-string<ExperienceInterface>>
     */
    protected function discoverExperiences(): array
    {
        $experiences = [];

        // Core (package) — ex. DemoExperience
        $packagePath = __DIR__ . '/Core';
        if (File::isDirectory($packagePath)) {
            foreach (File::files($packagePath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = 'Xavcha\\PageContentManager\\Experiences\\Core\\' . $file->getFilenameWithoutExtension();
                $key = $this->getKeyIfValid($className);
                if ($key !== null) {
                    $experiences[$key] = $className;
                }
            }
        }

        // Custom (application) — écrase le core si même clé
        $path = config('page-content-manager.experiences.path');
        if (! is_string($path) || $path === '') {
            $path = app_path('Experiences');
        }

        $namespace = config('page-content-manager.experiences.namespace', 'App\\Experiences');

        if (File::isDirectory($path)) {
            foreach (File::files($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = rtrim((string) $namespace, '\\') . '\\' . $file->getFilenameWithoutExtension();
                $key = $this->getKeyIfValid($className);
                if ($key !== null) {
                    $experiences[$key] = $className;
                }
            }
        }

        $disabled = config('page-content-manager.disabled_experiences', []);
        if (! empty($disabled) && is_array($disabled)) {
            $experiences = array_filter(
                $experiences,
                fn ($key) => ! in_array($key, $disabled, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        foreach ($experiences as $key => $className) {
            $this->experiences[$key] = $className;
        }

        return $experiences;
    }

    protected function getKeyIfValid(string $className): ?string
    {
        if (! class_exists($className)) {
            return null;
        }

        $reflection = new \ReflectionClass($className);

        if (
            $reflection->isAbstract()
            || $reflection->isInterface()
            || ! $reflection->implementsInterface(ExperienceInterface::class)
        ) {
            return null;
        }

        try {
            return $className::getKey();
        } catch (\Throwable) {
            return null;
        }
    }

    public function clearCache(): void
    {
        $cacheKey = config('page-content-manager.experiences.cache.key', 'page-content-manager.experiences.registry');
        Cache::forget($cacheKey);

        $this->autoDiscovered = false;
        $this->experiences = [];
    }
}
