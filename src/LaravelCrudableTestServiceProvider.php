<?php

namespace Mindz\LaravelCrudableTest;

use Illuminate\Support\ServiceProvider;
use Mindz\LaravelCrudableTest\Commands\CreateCrudableTestCommand;

class LaravelCrudableTestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateCrudableTestCommand::class,
            ]);
        }
    }
}
