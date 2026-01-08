<?php

namespace Xavcha\PageContentManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Xavcha\PageContentManager\Models\Concerns\HasContentBlocks;

class Page extends Model
{
    use HasFactory, HasContentBlocks;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'slug',
        'title',
        'content',
        'seo_title',
        'seo_description',
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
            'published_at' => 'datetime',
        ];
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

        // Normaliser le content avant sauvegarde
        static::saving(function (Page $page) {
            $page->normalizeContent();
        });
    }

    /**
     * Vérifie si la page est de type Home.
     */
    public function isHome(): bool
    {
        return $this->type === 'home';
    }

    /**
     * Vérifie si la page est de type Standard.
     */
    public function isStandard(): bool
    {
        return $this->type === 'standard';
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

