<?php

use Illuminate\Support\Facades\Auth;

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class to use for widget preferences.
    |
    */
    'user_model' => \App\Models\User::class,

    // Neutralize vendor closures to allow config caching
    'user_id_resolver' => null,
    'permission_check' => null,

    /*
    |--------------------------------------------------------------------------
    | Default Grid Columns
    |--------------------------------------------------------------------------
    |
    | Default grid column configuration for the dashboard.
    |
    */
    'default_grid_columns' => [
        'md' => 2,
        'xl' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sortable Options
    |--------------------------------------------------------------------------
    |
    | Options for Sortable.js initialization.
    |
    */
    'sortable_options' => [
        'animation' => 150,
        'handle' => '[x-sortable-handle]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Customize My Dashboard Button
    |--------------------------------------------------------------------------
    |
    | The title of the customize my dashboard button.
    | Customize button color, colors can be added in AdminPanelProvider.php -> colors array.
    |
    */
    'customize_dashboard_title' => 'Customize My Dashboard',
    'customize_dashboard_button_color' => 'primary',
];


