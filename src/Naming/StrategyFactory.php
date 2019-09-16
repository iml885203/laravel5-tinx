<?php

namespace iml885203\Laravel5Tinx\Naming;

use iml885203\Laravel5Tinx\Models\Models;
use iml885203\Laravel5Tinx\Naming\Strategy;
use Exception;
use Illuminate\Container\Container;
use Throwable;

class StrategyFactory
{
    /**
     * @return \iml885203\Laravel5Tinx\Naming\Strategy
     * */
    public static function makeDefault()
    {
        $strategy = config('tinx.strategy', 'pascal');

        $models = with(new Models)->toBase();

        return static::make($strategy, $models);
    }

    /**
     * Accepts a string identifier (e.g. 'pascal') or any class implementing 'iml885203\Laravel5Tinx\Naming\Strategy'.
     *
     * @param string $strategy
     * @return \iml885203\Laravel5Tinx\Naming\Strategy
     * */
    public static function make($strategy, $models)
    {
        try {
            return static::resolveViaContainer($strategy, $models);
        } catch (Exception $e) {
            return static::resolveViaIdentifier($strategy, $models);
        }
    }

    /**
     * @param string $strategy
     * @return \iml885203\Laravel5Tinx\Naming\Strategy
     * @throws Exception
     * */
    private static function resolveViaContainer($strategy, $models)
    {
        /**
         * This is the same as calling Laravel's "app()" helper,
         * but we don't have that framework function available.
         * The make method check supports legacy Laravel installs.
         * */
        $container = Container::getInstance();
        $makeMethod = method_exists($container, 'makeWith') ? 'makeWith' : 'make';
        $instance = $container->$makeMethod($strategy, ['models' => $models]);

        if ($instance instanceof Strategy) {
            return $instance;
        }

        throw new Exception('Strategy must implement [iml885203\Laravel5Tinx\Naming\Strategy].');
    }

    /**
     * @param string $strategy
     * @return \iml885203\Laravel5Tinx\Naming\Strategy
     * */
    private static function resolveViaIdentifier($strategy, $models)
    {
        switch ($strategy) {
            case 'shortestUnique':
                return new ShortestUniqueStrategy($models);
            case 'pascal':
            default:
                return new PascalStrategy($models);
        }
    }
}
