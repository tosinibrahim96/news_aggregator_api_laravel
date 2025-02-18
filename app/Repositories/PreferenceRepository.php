<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PreferenceRepositoryInterface;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PreferenceRepository implements PreferenceRepositoryInterface
{
    /**
     * @param UserPreference $model
     */
    public function __construct(
        private readonly UserPreference $model
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getUserPreferences(User $user): array
    {
        $preferences = $this->model->where('user_id', $user->id)->get();

        return [
            'sources' => $preferences->filter(fn ($pref) => !is_null($pref->source_id))
                ->map(fn ($pref) => $pref->source->slug)
                ->values(),
            'categories' => $preferences->filter(fn ($pref) => !is_null($pref->category_id))
                ->map(fn ($pref) => $pref->category->slug)
                ->values(),
            'authors' => $preferences->filter(fn ($pref) => !is_null($pref->author_name))
                ->pluck('author_name')
                ->values(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function updatePreferences(User $user, array $preferences): array
    {
        return DB::transaction(function () use ($user, $preferences) {
            // First, validate all sources and categories exist before making any changes
            if (!empty($preferences['sources'])) {
                $sourcesCount = Source::whereIn('slug', $preferences['sources'])->count();
                if ($sourcesCount !== count($preferences['sources'])) {
                    throw new \InvalidArgumentException('One or more invalid sources provided');
                }
            }

            if (!empty($preferences['categories'])) {
                $categoriesCount = Category::whereIn('slug', $preferences['categories'])->count();
                if ($categoriesCount !== count($preferences['categories'])) {
                    throw new \InvalidArgumentException('One or more invalid categories provided');
                }
            }

            // If validation passes, proceed with update
            $this->model->where('user_id', $user->id)->delete();

            // Store source preferences
            if (!empty($preferences['sources'])) {
                $sources = Source::whereIn('slug', $preferences['sources'])->get();
                foreach ($sources as $source) {
                    $this->model->create([
                        'user_id' => $user->id,
                        'source_id' => $source->id,
                    ]);
                }
            }

            // Store category preferences
            if (!empty($preferences['categories'])) {
                $categories = Category::whereIn('slug', $preferences['categories'])->get();
                foreach ($categories as $category) {
                    $this->model->create([
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                    ]);
                }
            }

            // Store author preferences
            if (!empty($preferences['authors'])) {
                foreach ($preferences['authors'] as $author) {
                    $this->model->create([
                        'user_id' => $user->id,
                        'author_name' => $author,
                    ]);
                }
            }

            return $this->getUserPreferences($user);
        });
    }
}
