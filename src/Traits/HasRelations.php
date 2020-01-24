<?php 

namespace TCG\Voyager\Traits;

use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

trait HasRelations
{
    protected $relationMethods;

    /**
     * Dynamically retrieve relationships.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $dataType = Voyager::model('DataType')->getCached()->where('model_name', get_class())->first();

        if (!empty($dataType->id)) {
            $dataRows = Voyager::model('DataRow')->getCached()->where('data_type_id', $dataType->id)->where('type', 'relationship');
            $this->setRelationMethods($dataRows);

            if (array_key_exists($method, $this->relationMethods ?? [])) {
                $relation = $this->relationMethods[$method];
                
                switch ($relation->type) {
                    case 'belongsTo':
                        return $this->belongsTo($relation->model, $relation->column, $relation->key);
                        break;
                    case 'belongsToMany':
                        return $this->belongsToMany($relation->model, $relation->pivot_table, $relation->foreign_pivot_key ?? null, $relation->related_pivot_key ?? null, $relation->key, $relation->related_key ?? null);
                        break;
                    case 'hasMany':
                        return $this->hasMany($relation->model, $relation->column, $relation->key);
                        break;
                    case 'hasOne':
                        return $this->hasOne($relation->model, $relation->column, $relation->key);
                        break;
                }
            }
        }

        return parent::__call($method, $parameters);
    }

    protected function setRelationMethods($relationships)
    {
        if (!isset($this->relationMethods)) {
            $dataType = Voyager::model('DataType');

            foreach ($relationships as $relationship) {
                $methodName = $dataType->getRelationMethodName($relationship->details);

                $this->relationMethods[$methodName] = $relationship->details;
            }
        }
    }
}
