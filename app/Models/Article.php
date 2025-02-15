<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'category_id',
        'external_id',
        'title',
        'slug',
        'description',
        'content',
        'author',
        'url',
        'image_url',
        'published_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime'
        ];
    }

    
    /**
     * The source the article belongs to
     * 
     * @return BelongsToMany<Source>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** 
     * The category the article belongs to
     * 
     * @return BelongsToMany<Category>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
    * Articles saved by users
    * 
    * @return BelongsToMany<User>
    */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_articles')
            ->withTimestamps();
    }
}
