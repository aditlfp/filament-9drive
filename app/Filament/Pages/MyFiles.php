<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class MyFiles extends Page
{
    protected static string |BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected string $view = 'filament.pages.my-files';

    protected static ?string $navigationLabel = 'My Files';

    protected static ?int $navigationSort = 1;
}
