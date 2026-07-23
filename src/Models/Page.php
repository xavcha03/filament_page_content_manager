<?php

namespace Xavcha\PageContentManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Xavcha\PageContentManager\Enums\DeletedPageResponseType;
use Xavcha\PageContentManager\Experiences\ExperienceRegistry;
use Xavcha\PageContentManager\Models\Concerns\HasContentBlocks;

class Page extends Model
{
    use HasFactory, HasContentBlocks, SoftDeletes;

    public const CONTENT_MODE_BLOCKS = 'blocks';

    public const CONTENT_MODE_EXPERIENCE = 'experience';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'content_mode',
        'experience_key',
        'slug',
        'title',
        'content',
        'experience_content',
        'seo_title',
        'seo_description',
        'seo_noindex',
        'deleted_response_type',
        'redirect_target_page_id',
        'redirect_target_url',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'experience_content' => 'array',
            'seo_noindex' => 'boolean',
            'deleted_response_type' => DeletedPageResponseType::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'content_mode' => 'blocks',
    ];

    public function redirectTargetPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'redirect_target_page_id');
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Empêcher la création de plusieurs pages Home
        static::creating(function (Page $page) {
            if ($page->type === 'home') {
                $existingHome = static::where('type', 'home')->first();
                if ($existingHome) {
                    throw new \RuntimeException('Une page Home existe déjà. Il ne peut y avoir qu\'une seule page Home.');
                }
            }
        });

        // Empêcher la suppression de la page Home
        static::deleting(function (Page $page) {
            if ($page->isHome()) {
                throw new \RuntimeException('La page Home ne peut pas être supprimée.');
            }
        });

        // Empêcher le changement de type
        static::updating(function (Page $page) {
            if ($page->isDirty('type')) {
                throw new \RuntimeException('Le type de la page ne peut pas être modifié après création.');
            }
        });

        // Normaliser le content et experience_content avant sauvegarde
        static::saving(function (Page $page) {
            $page->normalizeContent();
            $page->normalizeExperienceContent();
            $page->validateExperienceMode();
        });
    }

    public function isHome(): bool
    {
        return $this->type === 'home';
    }

    public function isStandard(): bool
    {
        return $this->type === 'standard';
    }

    public function isBlocksMode(): bool
    {
        return ($this->content_mode ?? self::CONTENT_MODE_BLOCKS) === self::CONTENT_MODE_BLOCKS;
    }

    public function isExperienceMode(): bool
    {
        return ($this->content_mode ?? self::CONTENT_MODE_BLOCKS) === self::CONTENT_MODE_EXPERIENCE;
    }

    /**
     * Contenu brut de l'Experience active (avant transform API).
     *
     * @return array<string, mixed>
     */
    public function getActiveExperienceContent(): array
    {
        $key = $this->experience_key;
        if (! is_string($key) || $key === '') {
            return [];
        }

        $bag = $this->experience_content;
        if (! is_array($bag)) {
            return [];
        }

        $data = $bag[$key] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * Remplace le contenu de l'Experience active (conserve les autres clés).
     *
     * @param  array<string, mixed>  $data
     */
    public function setActiveExperienceContent(array $data): void
    {
        $key = $this->experience_key;
        if (! is_string($key) || $key === '') {
            throw new \InvalidArgumentException('experience_key is required to set active experience content.');
        }

        $bag = is_array($this->experience_content) ? $this->experience_content : [];
        $bag[$key] = $data;
        $this->experience_content = $bag;
    }

    /**
     * Merge partiel dans le contenu de l'Experience active.
     *
     * @param  array<string, mixed>  $partial
     * @return array<string, mixed>
     */
    public function mergeActiveExperienceContent(array $partial): array
    {
        $merged = array_replace_recursive($this->getActiveExperienceContent(), $partial);
        $this->setActiveExperienceContent($merged);

        return $merged;
    }

    protected function normalizeExperienceContent(): void
    {
        $bag = $this->experience_content;
        if ($bag === null || $bag === '') {
            $this->experience_content = [];

            return;
        }

        if (! is_array($bag)) {
            $this->experience_content = [];

            return;
        }

        // Garantir un objet associatif (clés = experience keys)
        $normalized = [];
        foreach ($bag as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            $normalized[$key] = is_array($value) ? $value : [];
        }

        $this->experience_content = $normalized;
    }

    protected function validateExperienceMode(): void
    {
        $mode = $this->content_mode ?? self::CONTENT_MODE_BLOCKS;

        if (! in_array($mode, [self::CONTENT_MODE_BLOCKS, self::CONTENT_MODE_EXPERIENCE], true)) {
            throw new \InvalidArgumentException("Invalid content_mode '{$mode}'. Allowed: blocks, experience.");
        }

        if ($mode === self::CONTENT_MODE_BLOCKS) {
            return;
        }

        $key = $this->experience_key;
        if (! is_string($key) || $key === '') {
            throw new \InvalidArgumentException('experience_key is required when content_mode is experience.');
        }

        $registry = app(ExperienceRegistry::class);
        if (! $registry->has($key)) {
            throw new \InvalidArgumentException("Unknown experience key '{$key}'. Register it in app/Experiences.");
        }
    }

    /**
     * Scope pour filtrer les pages publiées.
     * Une page est considérée comme publiée si :
     * - Le statut est 'published' ET
     * - La date de publication est nulle (publication immédiate) OU la date est passée
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Vérifie si la page est planifiée.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Vérifie si la page est publiée (statut + date).
     */
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at <= now());
    }
}
