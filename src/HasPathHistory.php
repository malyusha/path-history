<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory;

use Illuminate\Database\Eloquent\Model;
use Malyusha\PathHistory\Contracts\DescendantsRetrieverContract;
use Malyusha\PathHistory\Exceptions\PathHistoryException;

trait HasPathHistory
{
    use HasSlug;

    protected static $pathGenerationEnabled = true;

    /**
     * @var []callable
     */
    protected $onPathCreate = [];

    protected static function bootHasPathHistory()
    {
        static::saved(function (Model $model) {
            if (! static::$pathGenerationEnabled) {
                return;
            }

            if ($model->isDirty($model->getChangersAttributes()) && $model->getSlug() && ! $model->isExternal() && ! $model->isSlug()) {
                $model->generateNewPath();

                $model->createDescendantsPaths();
            }
        });

        static::deleted(function (Model $model) {
            $paths = $model->paths()->get();

            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $paths->each->delete();
            }
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                $path = $model->paths()->orderByDesc('id')->first();

                if ($path !== null) {
                    $path->setCurrent();
                    $path->save();
                }
            });
        }
    }

    protected function getChangersAttributes(): array
    {
        $default = [$this->slugAttribute()];
        if (property_exists($this, 'updatePathOnChangeAttributes')) {
            return array_merge($default, $this->updatePathOnChangeAttributes);
        }

        return $default;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function paths(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(config('path_history.model'), 'related');
    }

    public function currentPath(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(config('path_history.model'), 'related')->orderByDesc('id')->where('is_current', true);
    }

    /**
     * Generates new history path for model and saves it to polymorphic relation.
     *
     * @return string
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public function generateNewPath()
    {
        $history = [];
        $slug = $this->getSlug();

        if ($this->defaultShouldUseParentPaths()) {
            foreach ($this->getPathParents() as $parent) {
                /**@var \Malyusha\PathHistory\HasPathHistory $parent */
                $history[] = $parent->getCurrentPath();
            }
        }
        $history[] = $slug;

        return $this->setCurrentPath(implode('/', $history));
    }

    /**
     * Creates new instance of history path.
     *
     * @param string $path
     *
     * @return \Malyusha\PathHistory\Contracts\PathHistoryContract
     */
    public function setCurrentPath(string $path): \Malyusha\PathHistory\Contracts\PathHistoryContract
    {
        return $this->currentPath()->create(['link' => $path]);
    }

    /**
     * Checks if model need to prepend parent's paths to own path or not.
     *
     * @return bool
     */
    public function defaultShouldUseParentPaths(): bool
    {
        if ($this->isExternal()) {
            return false;
        }

        if (method_exists($this, 'shouldUseParentPaths')) {
            return $this->shouldUseParentPaths();
        }

        return true;
    }

    /**
     * Returns parent models with paths for current.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    protected function getPathParents(): \Illuminate\Support\Collection
    {
        if (! property_exists($this, 'parentPathRelation')) {
            throw new PathHistoryException('Model must have property parentRelation to use HasPathHistory trait');
        }
        $relation = $this->{$this->parentPathRelation}();
        if (! $relation instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
            throw new PathHistoryException("Method {$this->parentPathRelation} must return instance of Relation");
        }

        if (! in_array(HasPathHistory::class, class_uses_recursive($relation->getRelated()), true)) {
            throw new PathHistoryException("Related model of {$this->parentPathRelation} must use trait HasPathHistory to create path history.");
        }

        return $relation->get()->filter(function ($parent) {
            return ! $parent->isExternal();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPathHistory(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->paths()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOldPaths(): \Illuminate\Database\Eloquent\Collection
    {
        $paths = $this->paths()->orderByDesc('id')->get();
        $paths->shift();

        return $paths;
    }

    /**
     * Returns link of last created history path.
     *
     * @return null|string
     */
    public function getCurrentPath()
    {
        return $this->currentPath()->value('link') ?? '';
    }

    /**
     * @return null|string
     */
    public function getPathAttribute()
    {
        return $this->relationLoaded('currentPath') ? optional($this->currentPath)->link : $this->getSlug();
    }

    /**
     * Returns last created for this entity history path instance.
     *
     * @param array $columns
     *
     * @return \Malyusha\PathHistory\Contracts\PathHistoryContract|null
     */
    public function getCurrentPathInstance($columns = ['*'])
    {
        return $this->currentPath()->first($columns);
    }

    /**
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public function createDescendantsPaths()
    {
        if ($this->isExternal()) {
            return;
        }

        $descendantsRetrievers = [];

        if (property_exists($this, 'descendantRetrievers')) {
            $descendantsRetrievers = $this->descendantRetrievers;
        }
        foreach ($descendantsRetrievers as $retriever) {
            $retriever = new $retriever;
            if (! ($retriever instanceof DescendantsRetrieverContract)) {
                throw new PathHistoryException("{$retriever} must implement DescendantsRetrieverContract");
            }

            foreach ($retriever->getDescendants($this) as $model) {
                $model->generateNewPath();
                $model->createDescendantsPaths();
            }
        }
    }

    /**
     * Disables path generation.
     */
    public static function disablePathGeneration()
    {
        static::$pathGenerationEnabled = false;
    }

    /**
     * Enables path generation.
     */
    public static function enablePathGeneration()
    {
        static::$pathGenerationEnabled = true;
    }

    /**
     * Add callback after path relation is created.
     *
     * @param callable $cb
     */
    public function onPathCreate(callable $cb)
    {
        $this->onPathCreate[] = $cb;
    }
}
