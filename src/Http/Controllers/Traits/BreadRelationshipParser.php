<?php

namespace TCG\Voyager\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use TCG\Voyager\Models\DataType;

trait BreadRelationshipParser
{
    protected function removeRelationshipField($dataTypeRows)
    {
        $forget_keys = [];
        foreach ($dataTypeRows as $key => $row) {
            if ($row->type == 'relationship') {
                if ($row->details->type == 'belongsTo') {
                    $relationshipField = @$row->details->column;
                    $keyInCollection = key($dataTypeRows->where('field', '=', $relationshipField)->toArray());
                    array_push($forget_keys, $keyInCollection);
                }
            }
        }

        foreach ($forget_keys as $forget_key) {
            $dataTypeRows->forget($forget_key);
        }

        // Reindex collection
        $dataTypeRows = $dataTypeRows->values();

        return $dataTypeRows;
    }

    /**
     * Build the relationships array for the model's eager load.
     *
     * @param DataType $dataType
     *
     * @return array
     */
    protected function getRelationships(DataType $dataType, $dataTypeRows)
    {
        //Check relationships
        $relationships = [];

        $model = app($dataType->model_name);

        foreach ($dataTypeRows as $row) {
            if (in_array($row->type, ['relationship'])) {
                $details = $row->details;

                $relationships[] = $dataType->getRelationMethodName($details);
            }
        }

        return $relationships;
    }

    /**
     * Replace relationships' keys for labels and create READ links if a slug is provided.
     *
     * @param  $dataTypeContent     Can be either an eloquent Model, Collection or LengthAwarePaginator instance.
     * @param DataType $dataType
     *
     * @return $dataTypeContent
     */
    protected function resolveRelations($dataTypeContent, DataType $dataType)
    {
        // In case of using server-side pagination, we need to work on the Collection (BROWSE)
        if ($dataTypeContent instanceof LengthAwarePaginator) {
            $dataTypeCollection = $dataTypeContent->getCollection();
        }
        // If it's a model just make the changes directly on it (READ / EDIT)
        elseif ($dataTypeContent instanceof Model) {
            return $dataTypeContent;
        }
        // Or we assume it's a Collection
        else {
            $dataTypeCollection = $dataTypeContent;
        }

        return $dataTypeContent instanceof LengthAwarePaginator ? $dataTypeContent->setCollection($dataTypeCollection) : $dataTypeCollection;
    }

    /**
     * Eagerload relationships.
     *
     * @param mixed    $dataTypeContent     Can be either an eloquent Model or Collection.
     * @param string   $action
     * @param bool     $isModelTranslatable
     *
     * @return void
     */
    protected function eagerLoadRelations($dataTypeContent, $dataTypeRows, string $action, bool $isModelTranslatable)
    {
        // Eagerload Translations
        if (config('voyager.multilingual.enabled')) {
            // Check if BREAD is Translatable
            if ($isModelTranslatable) {
                $dataTypeContent->load('translations');
            }

            // DataRow is translatable so it will always try to load translations
            // even if current Model is not translatable
            $dataTypeRows->load('translations');
        }
    }
}
