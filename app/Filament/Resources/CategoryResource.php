<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Filament\Resources\CategoryResource\RelationManagers\ProductsRelationManager;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;


class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    
     public static function getNavigationLabel(): string
    {
        return __('admin.categories');
    }

    public static function getModelLabel(): string
    {
        return __('admin.categories');
    }
    
    public static function getPluralModelLabel(): string
    {
        return __('admin.categories');
    }
    public static function getNavigationGroup(): string
    {
        return __('admin.products_category');
    }

     public static function getNavigationBadge(): ?string
    {
            return static::getModel()::count();
    }


    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Category')
                ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()->label(__('admin.name'))
                    ->maxLength(255),

                    Forms\Components\FileUpload::make('image')
                   ->required()->label(__('strings.image'))->image()->directory('uploads/category'),

                Forms\Components\Toggle::make('is_active')
                    ->required()->label(__('admin.is_active')),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->label(__('admin.name')),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('admin.is_active')),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products Count')
                    ->counts('products'),
    

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable(),

                 Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('admin.updated_at')),

             ])
            ->filters([
                //
            ])
           ->actions([
                Tables\Actions\ViewAction::make()->button(),

                Tables\Actions\EditAction::make()->button(),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->action(function (Category $record) {

                        if ($record->products()->exists()) {

                            Notification::make()
                                ->danger()
                                ->title('Cannot Delete Category')
                                ->body('This category has linked products.')
                                ->send();

                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Deleted Successfully')
                            ->send();
                    }),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {

                            $records->each(function ($record) {

                                if ($record->products()->exists()) {

                                    Notification::make()
                                        ->danger()
                                        ->title("Cannot Delete Category: {$record->name}")
                                        ->body('This category has linked products.')
                                        ->send();

                                    return;
                                }

                                $record->delete();
                            });
                        }),

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            // 'view' => Pages\ViewCategory::route('/{record}'),
            // 'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    
}
