<?php 

namespace TCG\Voyager\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use TCG\Voyager\Traits\ExtendPivotEvents;

class BelongsToManyCustom extends BelongsToMany
{
    use ExtendPivotEvents;
}
