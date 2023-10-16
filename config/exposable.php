<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Key
    |--------------------------------------------------------------------------
    |
    | The key which is used to sign the expose urls.
    |
    */
    'key' => config('app.key'),

    /*
    |--------------------------------------------------------------------------
    | Url prefix
    |--------------------------------------------------------------------------
    |
    | The url which is used to expose your models.
    |
    */
    'url-prefix' => '/expose',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Define all middleware of your application you want to add to the expose
    | url. This can be a string or an array and will be added to the
    | default expose middleware.
    |
    */
    'middleware' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Lifetime
    |--------------------------------------------------------------------------
    |
    | The lifetime after the expose url expires. By default it uses minutes.
    | But any date modification can be used like "10 days" for example.
    | See http://php.net/manual/en/datetime.formats.relative.php
    |
    */
    'lifetime' => 10, // 10 minutes

    /*
    |--------------------------------------------------------------------------
    | Exposable models
    |--------------------------------------------------------------------------
    |
    | Here you can configure which models you want to expose. This is done on
    | a key value basis. The key will be used in the url to retrieve the
    | model to expose.
    |
    */
    'exposables' => [// 'attachment' => App\Attachment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization guards
    |--------------------------------------------------------------------------
    |
    | Here you may configure your guards to protect data from being exposed to
    | user without the permission to do so. The key kan be used as guard
    | when generating the exposable url.
    |
    */
    'guards' => [
        'auth' => \ArjanWestdorp\Exposable\Guards\AuthGuard::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route overwrites
    |--------------------------------------------------------------------------
    |
    | Specify custom route handling for some of the exposables that might
    | require additional processing on app side.
    |
    */
    'routes' => [
        //'route' => 'Controller@method',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default guard
    |--------------------------------------------------------------------------
    |
    | Here you can configure the default guard that is used when generating the
    | expose url. Set to null if you don't want to use a guard by default.
    | The null option only works if "require-guard" is set fo false.
    |
    */
    'default-guard' => 'auth',

    /*
    |--------------------------------------------------------------------------
    | Requires guard
    |--------------------------------------------------------------------------
    |
    | Define if a guard is always required when exposing a model. Settings this
    | to false will give you the option to use no guard by setting the
    | default-guard option to null or $exposableGuard on a model.
    |
    */
    'require-guard' => true,
];
