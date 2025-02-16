<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Source extends Model
{
    /** @use HasFactory<\Database\Factories\SourceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'category_mapping',
        'base_url',
        'api_key',
        'is_active',
        'last_synced_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'category_mapping' => 'array',
        ];
    }


    /**
     * Get the articles from the source.
     *
     * @return HasMany<Article>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Get the users who have the source as a preference.
     *
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_preferences')
            ->withTimestamps();
    }

    /**
     * Get the mapped category for the news source
     *
     * @param string $category
     * @return string
     */
    public function getMappedCategory(string $category): string
    {
        return $this->category_mapping[$category] ?? $category;
    }
}
