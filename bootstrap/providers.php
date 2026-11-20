<?php

use App\Providers\AppServiceProvider;
use Bugsnag\BugsnagLaravel\BugsnagServiceProvider;

return [
    BugsnagServiceProvider::class,
    AppServiceProvider::class,
];
