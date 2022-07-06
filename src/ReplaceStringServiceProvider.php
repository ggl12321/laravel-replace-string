<?php

namespace Yuki12321\ReplaceString;

use Illuminate\Support\ServiceProvider;
use Yuki12321\ReplaceString\Commands\ReplaceStringCommand;

class ReplaceStringServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            ReplaceStringCommand::class
        ]);
    }
}
