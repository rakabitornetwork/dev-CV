<?php

return [

    'remote' => env('DEPLOY_REMOTE', 'origin'),

    'branch' => env('DEPLOY_BRANCH', 'main'),

    /*
    | On production the web updater installs without dev packages.
    | Locally it keeps them so the machine used for editing stays intact.
    */
    'no_dev' => filter_var(
        env('DEPLOY_NO_DEV', env('APP_ENV') === 'production'),
        FILTER_VALIDATE_BOOL,
    ),

    'binaries' => [
        'git' => env('DEPLOY_GIT', 'git'),
        'composer' => env('DEPLOY_COMPOSER', 'composer'),
    ],

];
