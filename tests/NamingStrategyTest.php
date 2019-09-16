<?php

namespace iml885203\Laravel5Tinx\Tests;

use iml885203\Laravel5Tinx\Models\Model;
use iml885203\Laravel5Tinx\Naming\ForbiddenNames;
use iml885203\Laravel5Tinx\Naming\StrategyFactory;
use PHPUnit\Framework\TestCase;

class NamingStrategyTest extends TestCase
{
    /**
     * @var array
     * */
    public $strategies;

    /**
     * @return void
     * */
    public function setUp()
    {
        $this->strategies = [
            'shortestUnique',
            'pascal',
            // Add your strategy here…
        ];
    }

    /** @test */
    function it_will_not_use_reserved_names_as_shortcuts()
    {
        $models = collect([

            // shortest unique = "min" (forbidden!)
            new Model("App\Mindblower"),
            new Model("App\Microscope"),

            // pascal = "min" (forbidden!)
            new Model("App\MaximumInstanceNode")

            // Add more special cases here?

            // Add ~1000 nouns from faker?

        ]);

        foreach ($this->strategies as $strategy) {
            $names = StrategyFactory::make($strategy, $models)->getNames();
            foreach ($names as $name) {
                $this->assertTrue(!function_exists($name));
                $this->assertTrue(!ForbiddenNames::exists($name));
            }
        }
    }
}
