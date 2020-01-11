<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Events\SettingUpdated;
use TCG\Voyager\Traits\HasCache;

class Setting extends Model
{
    use HasCache;

    protected $table = 'settings';

    protected $guarded = [];

    public $timestamps = false;
}
