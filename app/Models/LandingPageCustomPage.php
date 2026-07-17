<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LandingPageCustomPage extends BaseModel
{
    public const NAV_GROUPS = [
        'practice-type' => 'Practice Type',
        'solution' => 'Solutions',
    ];

    protected $fillable = [
        'title',
        'slug',
        'nav_group',
        'content',
        'page_data',
        'summary',
        'icon',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'page_data' => 'array'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInNavGroup($query, string $group)
    {
        return $query->where('nav_group', $group);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            if (is_null($page->sort_order)) {
                $page->sort_order = static::max('sort_order') + 1;
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }
}