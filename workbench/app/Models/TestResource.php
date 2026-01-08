<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Xavcha\PageContentManager\Models\Concerns\HasPageDetail;

class TestResource extends Model
{
    use HasFactory, HasPageDetail;

    protected $fillable = [
        'name',
        'seo_title',
        'seo_description',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }
}

