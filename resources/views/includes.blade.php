<php

use iml885203\Laravel5Tinx\Console\State;
use Illuminate\Support\Arr;

/**
 * Restarts Tinker.
 *
 * @return void
 * */
function re() {
    State::requestRestart();
    exit();
}

/**
 * Regenerate Composer's optimized autoload files before restarting Tinker.
 *
 * @return void
 * */
function reo() {
    exec("composer dump -o");
    re();
}

/**
 * Aliases.
 * */
function reboot() {
    re();
}

function reload() {
    re();
}

function restart() {
    re();
}

/**
 * Renders the "Class/Shortcuts" names table.
 *
 * @param array $args If passed, filters classes to these terms (e.g. "names('banana')", "names(['banana', 'carrot'])").
 * @return void
 * */
function names($args = []) {
    if(is_string($args)) {
        $args = [$args];
    }
    event('tinx.names', compact('args'));
}

/**
 * @param string $class
 * @return void
 * */
function tinx_forget_name($class) {
    Arr::forget($GLOBALS, "tinx.names.$class");
}

/**
 * Magic query method to handle all "u(x [y, z])" calls.
 *
 * @param string $class
 * @param mixed $args
 * @return mixed
 * */
function tinx_query($class, $arg)
{
    /**
     * Zero argument (i.e. u() returns "App\User").
     * */
    if (is_null($arg)) {
        return app($class); /* Return a clean starting point for the query builder. */
    }

    /**
     * One argument (i.e. u(2) returns App\User::find(2)).
     * */
    if (!is_null($arg)) {

        /**
         * Int? Use "find()".
         * */
        if (is_int($arg)) {
            return $class::find($arg);
        }

        /**
         * String? Search all columns.
         * */
        if (is_string($arg)) {
            if ($class::first() === null) {
                throw new Exception(
                    "You can only search where there is data. ".
                    "There is no way for Tinx to get a column listing ".
                    "for a model without an existing instance…");
            }
            $columns = Schema::getColumnListing($class::first()->getTable());
            $query = $class::select('*');
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', '%'.$arg.'%');
            }
            return $query->get();
        }

        throw new Exception("Don't know what to do with this datatype. Please make PR.");
    }

    throw new Exception("Too many arguments!");
}

/**
 * Insert "first" and "last" variables (e.g. '$u', '$u_', etc) and model functions (e.g. 'u()', etc).
 * For "first" variable, returns "::first()" if class DB table exists, otherwise "new" (if 'tableless_models' set to true).
 * For "last" variable, returns "::latest()->first()" if class DB table exists, otherwise "new" (if 'tableless_models' set to true).
 * */
Arr::set($GLOBALS, 'tinx.names', {!! var_export($names); !!});
$latestColumn = '{{ Illuminate\Support\Arr::get($config, 'latest_column', 'created_at') }}';
@foreach ($names as $class => $name)
    try {
        ${!! $name !!} = {!! $class !!}::first() ?: app('{!! $class !!}');
        ${!! $name !!}_ = {!! $class !!}::latest($latestColumn)->first() ?: app('{!! $class !!}');
        Arr::set($GLOBALS, 'tinx.shortcuts.{!! $name !!}', ${!! $name !!});
        Arr::set($GLOBALS, 'tinx.shortcuts.{!! $name !!}_', ${!! $name !!}_);
        if (!function_exists('{!! $name !!}')) {
            function {!! $name !!}($arg = null) {
                return tinx_query('{!! $class !!}', $arg);
            }
        }
    } catch (Throwable $e) {
        @include('tinx::on-name-error')
    } catch (Exception $e) {
        @include('tinx::on-name-error')
    }
@endforeach
unset($latestColumn);

/**
 * Quick names reference array.
 * */
$names = Arr::get($GLOBALS, 'tinx.names');

/**
 * Define shortcuts for "names()" table, and also set quick shortcuts reference array.
 * */
$shortcuts = collect($names)->map(function ($name, $class) {
    $shortcuts = [];
    if (Arr::has($GLOBALS, "tinx.shortcuts.$name")) $shortcuts[] = "\${$name}";
    if (Arr::has($GLOBALS, "tinx.shortcuts.{$name}_")) $shortcuts[] = "\${$name}_";
    if (function_exists($name)) $shortcuts[] = "{$name}()";
    return implode(', ', $shortcuts);
})->all();
Arr::set($GLOBALS, 'tinx.names', $shortcuts);

/**
 * Conditionally render the "Class/Shortcuts" names table.
 * */
event('tinx.names.conditional');
