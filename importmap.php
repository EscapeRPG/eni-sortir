<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'burger_controller' => [
        'path' => './assets/controllers/burger_controller.js',
    ],
    'modals_controller' => [
        'path' => './assets/controllers/modals_controller.js',
    ],
    'themes_controller' => [
        'path' => './assets/controllers/themes_controller.js',
    ],
    'select2_controller' => [
        'path' => './assets/controllers/select2_controller.js',
    ],
    'swiper' => [
        'version' => '11.2.10',
    ],
    'swiper_controller' => [
        'path' => './assets/controllers/swiper_controller.js',
    ],

];
