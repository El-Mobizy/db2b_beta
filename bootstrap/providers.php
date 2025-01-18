<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
    // MongoDB\Laravel\MongoDBServiceProvider::class,
  PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider::class,
];
