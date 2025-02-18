<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class UserPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserPreferenceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'source_id',
        'category_id',
        'author_name',
    ];

    /**
     * Get the user that owns the preference
     *
     * @return BelongsTo<User, UserPreference>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source associated with the preference
     *
     * @return BelongsTo<Source, UserPreference>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Get the category associated with the preference
     *
     * @return BelongsTo<Category, UserPreference>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
