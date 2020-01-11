<?php

namespace TCG\Voyager\Traits;

trait ExtendPivotEvents
{
    /**
     * Attach a model to the parent.
     *
     * @param mixed $id
     * @param array $attributes
     * @param bool  $touch
     */
    public function attach($ids, array $attributes = [], $touch = true)
    {
        $parentResult = parent::attach($ids, $attributes, $touch);

        $this->parent->relationUpdated($this->getRelationName());

        return $parentResult;
    }

    /**
     * Detach models from the relationship.
     *
     * @param mixed $ids
     * @param bool  $touch
     *
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        $parentResult = parent::detach($ids, $touch);

        $this->parent->relationUpdated($this->getRelationName());

        return $parentResult;
    }
}
