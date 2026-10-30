<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\Sluggable\SluggableServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    PermissionServiceProvider::class,
    SluggableServiceProvider::class,
];
