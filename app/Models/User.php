<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime'
        ];
    }

    /**
     * Get the users saved articles.
     *
     * @return BelongsToMany<Article>
     */
    public function savedArticles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'saved_articles')
            ->withTimestamps();
    }

    /**
     * Get the users preferred categories.
     *
     * @return BelongsToMany<Article>
     */
    public function preferredCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'user_preferences')
            ->withTimestamps();
    }

    /**
     * Get the users preferred sources.
     *
     * @return BelongsToMany<Article>
     */
    public function preferredSources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class, 'user_preferences')
            ->withTimestamps();
    }
}
