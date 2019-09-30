<?php

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        return TCG\Voyager\Facades\Voyager::setting($key, $default);
    }
}

if (!function_exists('menu')) {
    function menu($menuName, $type = null, array $options = [])
    {
        return TCG\Voyager\Facades\Voyager::model('Menu')->display($menuName, $type, $options);
    }
}

if (!function_exists('voyager_asset')) {
    function voyager_asset($path, $secure = null)
    {
        return route('voyager.voyager_assets').'?path='.urlencode($path);
    }
}

if (!function_exists('get_file_name')) {
    function get_file_name($name)
    {
        preg_match('/(_)([0-9])+$/', $name, $matches);
        if (count($matches) == 3) {
            return Illuminate\Support\Str::replaceLast($matches[0], '', $name).'_'.(intval($matches[2]) + 1);
        } else {
            return $name.'_1';
        }
    }
}

if (! function_exists('getIncludeContent')) {
    function getIncludeContent($vars, $expression, $params = [])
    {
        $expression = Illuminate\Support\Facades\Blade::stripParentheses($expression);
        $__env = $vars['__env'];

        $vars = array_merge($vars, $params);
        $content = $__env->make($expression, Illuminate\Support\Arr::except($vars, ['__data', '__path']))->render();

        return trim($content);
    }
}
