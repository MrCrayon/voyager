<?php 

namespace TCG\Voyager\Traits;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\Relations\BelongsToManyCustom;

trait HasCache
{
    protected static $cache_repository;
    protected static $cached_results = [];
    protected static $events_that_clear_cache = ['created', 'deleted', 'saved', 'updated'];

    public static function BootHasCache()
    {
        self::$cache_repository = app(Cache::class);

        foreach (self::$events_that_clear_cache as $event) {
            static::$event(function ($model) use ($event) {
                self::clearCache();
            });
        }
    }

    public function relationUpdated($relation)
    {
        self::clearCacheRelation($relation);
    }

    public static function getCached()
    {
        return self::loadCache('list');
    }

    public static function getCachedWith($relations = [])
    {
        // This method is different because result is not cached
        // I'll get the cached list and and cached relations ids for belongsToMany
        // then load relations, each relation list might be cached if model uses HasCache Trait
        $cachedList = self::loadCache('list');

        if (!$cachedList->isEmpty()) {
            $obj = $cachedList->first();
            $keyName = $obj->getKeyName();

            // Save list of relations in permanent cache so we have a list for clearing
            $relationList = array_merge(array_keys($relations), self::loadCache('relationList'));
            self::saveCache('relationList', $relationList);

            // Eager load relationships
            foreach ($relations as $name => $model) {
                if (method_exists($model, 'getCached')) {
                    $model = app($model);

                    $relation = self::loadCache('relations', ['name' => $name, 'obj' => $obj, 'keyName' => $keyName], $name);

                    switch ($relation['type']) {
                        case 'HasOne':
                        case 'HasMany':
                            $cachedList =  $cachedList->map(function ($item) use ($name, $model, $relation, $obj) {
                                $relationship = $model->getCached()->whereIn($relation['foreignKey'], $obj->getKey());
                                
                                $item->setRelation($name, $relationship);

                                return $item;
                            });
                            break;
                        case 'BelongsTo':
                            $cachedList =  $cachedList->map(function ($item) use ($name, $model, $relation, $keyName, $obj) {
                                $relationship = $model->getCached()->whereIn($model->getKeyName(), $obj->{$relation['foreignKey']});
                                
                                $item->setRelation($name, $relationship);

                                return $item;
                            });
                            break;
                        default:// BelongsToMany
                            $cachedList = $cachedList->map(function ($item) use ($name, $model, $relation, $keyName) {
                                $ids = $relation['keys'][$item->{$keyName}];

                                $relationship = $model->getCached()->whereIn($model->getKeyName(), $ids);

                                $item->setRelation($name, $relationship);

                                return $item;
                            });
                            break;
                    }
                } else {
                    $cachedList->load($name);
                }
            }
        }

        return $cachedList;
    }

    protected static function loadCache($type, $parameters = [], $key = null)
    {
        if (!isset(self::$cached_results[$type]) || (!is_null($key) && !isset(self::$cached_results[$type][$key]))) {
            if (self::$cache_repository->has(self::getCacheKey($type, $key))) {
                $content = self::$cache_repository->get(self::getCacheKey($type, $key));
            } else {
                $content = self::{'loadContent'.ucfirst($type)}($parameters);
            }

            self::saveCache($type, $content, $key);
        }

        return is_null($key) ? self::$cached_results[$type] : self::$cached_results[$type][$key];
    }

    public static function clearCache()
    {
        self::forgetCache('list');
        self::forgetCache('relations');
    }

    public static function clearCacheList()
    {
        self::forgetCache('list');
    }

    public static function clearCacheRelation($relation)
    {
        self::forgetCache('relations', $relation);
    }

    /**
     * Forget cached values from property and permantent cache
     * In case property is an array remove only passed key or all if key is null
     *
     * @param string      $type
     * @param string|null $key
     * @return void
     */
    protected static function forgetCache(string $type, string $key = null) : void
    {
        if (isset(self::$cached_results[$type]) && is_array(self::$cached_results[$type])) {
            if (!is_null($key)) {
                if (isset(self::$cached_results[$type]) && isset(self::$cached_results[$type][$key])) {
                    unset(self::$cached_results[$type][$key]);
                }

                self::forgetCache($type.'_'.$key);
            } else {
                foreach (self::$cached_results[$type] as $key) {
                    $key = is_array($key) ? array_key_first($key) : $key;

                    self::forgetCache($type, $key);
                }
            }
        } else {
            if (isset(self::$cached_results[$type])) {
                unset(self::$cached_results[$type]);
            }
        }

        self::$cache_repository->forget(self::getCacheKey($type, $key));
    }

    protected static function loadContentList($parameters)
    {
        return parent::all();
    }

    /**
     * Cache relations ids
     */
    protected static function loadContentRelations($parameters)
    {
        list($relation, $obj, $keyName) = array_values($parameters);

        $cachedList = self::getCached();

        $relationObj = $obj->$relation();
        $reflectionClass = new \ReflectionClass($relationObj);
        $type = $reflectionClass->getShortName();

        $relationList['type'] = $type;

        switch ($type) {
            case 'HasOne':
            case 'HasMany':
            case 'BelongsTo':
                $property = $reflectionClass->getProperty('foreignKey');
                $property->setAccessible(true);
                $foreignKeyFull = $property->getValue($relationObj);
                $parts = explode('.', $foreignKeyFull);
                $foreignKey = end($parts);

                $relationList['foreignKey'] = $foreignKey;
                break;
            default://BelongsToMany
                $cachedList->load($relation);

                $relationList['keys'] = $cachedList->pluck($relation, $keyName)->map(function($item) {
                    return $item->pluck('id')->toArray();
                });
                break;
        }

        return $relationList;
    }

    protected static function loadContentRelationList()
    {
        return [];
    }

    protected static function saveCache($type, $cache, $key = null)
    {
        if (!is_null($key)) {
            self::$cached_results[$type][$key] = $cache;
        } else {
            self::$cached_results[$type] = $cache;
        }

        self::$cache_repository->forever(self::getCacheKey($type, $key), $cache);
    }

    protected static function getCacheKey($type, $key = null)
    {
        $cacheKey = 'hascache_'.get_class().'_'.$type;
        $cacheKey .= !is_null($key) ? '_'.$type : '';

        return $cacheKey;
    }

    /**
     * Override belongsToMany relation so that we can apply a ExtendPivoEvents Trait
     */
    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToManyCustom($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }
}
