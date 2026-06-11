<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileResource\Pages\ListFiles;
use App\Models\File;
use App\Models\Folder;
use App\Services\SmartGoogleDriveUploadService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use MmesDesign\FilamentFileManager\Forms\Components\FilePicker;
use MmesDesign\FilamentFileManager\Services\FileManagerService;
use MmesDesign\FilamentFileManager\Services\FileTypeResolver;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static string|\UnitEnum|null $navigationGroup = 'Drive';

    protected static ?string $navigationLabel = 'File Browser';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->ownedBy(auth()->id())
                ->with(['folder.parent.parent', 'storageAccount']))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('folder.path')
                    ->label('Virtual folder')
                    ->state(fn(File $record): string => $record->folder->path)
                    ->searchable(['name']),
                TextColumn::make('storageAccount.google_email')
                    ->label('Stored on')
                    ->toggleable(),
                TextColumn::make('formatted_size')
                    ->label('Size')
                    ->state(fn(File $record): string => $record->formatted_size),
                TextColumn::make('mime_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('smartUpload')
                    ->label('Smart Upload')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->schema([
                        Select::make('folder_id')
                            ->label('Virtual folder')
                            ->options(fn(): array => static::folderOptions())
                            ->default(fn(): int => Folder::rootForUser(auth()->id())->id)
                            ->required()
                            ->searchable(),
                        FileUpload::make('upload')
                            ->label('Upload now')
                            ->storeFiles(false),
                        FilePicker::make('file_manager_file')
                            ->label('Or choose from File Manager'),
                    ])
                    ->action(function (array $data, SmartGoogleDriveUploadService $uploader): void {
                        $folder = Folder::query()
                            ->ownedBy(auth()->id())
                            ->findOrFail($data['folder_id']);

                        $uploadedFile = Arr::first(Arr::wrap($data['upload'] ?? null));

                        if ($uploadedFile instanceof UploadedFile) {
                            $file = $uploader->uploadUploadedFile($uploadedFile, $folder);
                        } elseif (filled($data['file_manager_file'] ?? null)) {
                            $fileManagerFile = static::resolveFileManagerFile($data['file_manager_file']);

                            if (! $fileManagerFile) {
                                throw ValidationException::withMessages([
                                    'file_manager_file' => 'Choose a valid File Manager file.',
                                ]);
                            }

                            $path = static::fileManagerFilePath($fileManagerFile);

                            $file = $uploader->uploadPath(
                                path: $path,
                                folder: $folder,
                                name: $fileManagerFile['name'],
                                size: $fileManagerFile['size'],
                                mimeType: $fileManagerFile['mimeType'],
                            );
                        } else {
                            throw ValidationException::withMessages([
                                'upload' => 'Upload a file or choose one from File Manager.',
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title('File uploaded')
                            ->body("$file->name was stored on $file->storageAccount->google_email.")
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('rename')
                    ->icon(Heroicon::OutlinedPencil)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->fillForm(fn(File $record): array => [
                        'name' => $record->name,
                    ])
                    ->action(fn(File $record, array $data) => $record->update([
                        'name' => $data['name'],
                    ])),
                Action::make('move')
                    ->icon(Heroicon::OutlinedFolderArrowDown)
                    ->schema([
                        Select::make('folder_id')
                            ->label('Target folder')
                            ->options(fn(): array => static::folderOptions())
                            ->required()
                            ->searchable(),
                    ])
                    ->fillForm(fn(File $record): array => [
                        'folder_id' => $record->folder_id,
                    ])
                    ->action(fn(File $record, array $data) => $record->update([
                        'folder_id' => $data['folder_id'],
                    ])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiles::route('/'),
        ];
    }

    protected static function folderOptions(): array
    {
        Folder::rootForUser(auth()->id());

        return Folder::optionsForUser(auth()->id());
    }

    protected static function resolveFileManagerFile(string $state): ?array
    {
        if (blank($state)) {
            return null;
        }

        $disk = config('filament-file-manager.disk', 'public');
        $fileManagerService = app(FileManagerService::class);

        if (! $fileManagerService->exists($disk, $state)) {
            return null;
        }

        $pathInfo = pathinfo($state);
        $extension = strtolower($pathInfo['extension'] ?? '');
        $mimeType = app(FileTypeResolver::class)->mimeType($extension);

        return [
            'path' => $state,
            'name' => $pathInfo['basename'],
            'size' => Storage::disk($disk)->size($state),
            'mimeType' => $mimeType,
        ];
    }

    protected static function fileManagerFilePath(array $fileInfo): string
    {
        $disk = config('filament-file-manager.disk', 'public');
        return Storage::disk($disk)->path($fileInfo['path']);
    }
}
