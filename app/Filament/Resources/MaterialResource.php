<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Models\Material;
use App\Support\MaterialUnit;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function getNavigationLabel(): string
    {
        return __('admin.materials');
    }

    public static function getModelLabel(): string
    {
        return __('admin.materials');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.materials-main');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.materials');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('material_type', 'internal')->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('material_type', 'internal');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('admin.add_materail'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label(__('admin.code'))
                            ->maxLength(255)
                            ->disabled(fn ($record) => $record !== null) // Disable on edit
                            ->dehydrated()
                            ->helperText(__('admin.code_auto_generated')),

                        Forms\Components\Select::make('category_id')
                            ->label(__('admin.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('admin.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\FileUpload::make('image')
                                    ->label(__('admin.image'))
                                    ->image()
                                    ->directory('uploads/categories')
                                    ->nullable(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('admin.is_active'))
                                    ->default(true),
                            ]),

                        Forms\Components\FileUpload::make('image')
                            ->label(__('admin.image'))
                            ->image()
                            ->directory('uploads/materials')
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('color')
                            ->label(__('admin.color'))
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Select::make('type')
                            ->label(__('admin.type'))
                            ->options([
                                'cold' => __('admin.cold'),
                                'hot' => __('admin.hot'),
                            ])
                            ->nullable(),

                        Forms\Components\TextInput::make('quantity_in_stock')
                            ->label(__('admin.quantity_in_stock'))
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->minValue(0)
                            ->step(0.01)
                            ->reactive()
                            ->suffix(fn (Get $get) => ' '.MaterialUnit::label($get('unit'))),

                        Forms\Components\TextInput::make('current_quantity_material')
                            ->label(__('admin.current_quantity_material'))
                            ->numeric()
                            ->default(0.00)
                            ->minValue(0)
                            ->step(0.01)
                            ->helperText(__('admin.current_quantity_material_helper'))
                            ->suffix(fn (Get $get) => ' '.MaterialUnit::label($get('unit'))),

                        Forms\Components\Select::make('unit')
                            ->label(__('admin.unit'))
                            ->required()
                            ->helperText(__('admin.material_unit_standard_hint'))
                            ->options(fn (Get $get) => MaterialUnit::optionsForForm($get('unit'), null))
                            ->live()
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label(__('admin.status'))
                            ->options([
                                'low_stock' => __('admin.low_stock'),
                                'good' => __('admin.good'),
                                'out_of_stock' => __('admin.out_of_stock'),
                            ])
                            ->default('good')
                            ->required(),

                        Forms\Components\Hidden::make('material_type')
                            ->default('internal'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('admin.image'))
                    ->circular()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label(__('admin.code'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('admin.category'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label(__('admin.quantity_in_stock'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd()
                    ->suffix(fn (Material $record) => ' '.MaterialUnit::label($record->unit)),

                Tables\Columns\TextColumn::make('current_quantity_material')
                    ->label(__('admin.current_quantity_material'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd()
                    ->toggleable()
                    ->suffix(fn (Material $record) => ' '.MaterialUnit::label($record->unit)),

                Tables\Columns\TextColumn::make('unit')
                    ->label(__('admin.unit'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => MaterialUnit::label($state)),

                Tables\Columns\TextColumn::make('color')
                    ->label(__('admin.color'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __("admin.{$state}") : '-')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('admin.status'))
                    ->colors([
                        'danger' => 'out_of_stock',
                        'warning' => 'low_stock',
                        'success' => 'good',
                    ])
                    ->formatStateUsing(fn ($state) => __("admin.{$state}"))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin.status'))
                    ->options([
                        'low_stock' => __('admin.low_stock'),
                        'good' => __('admin.good'),
                        'out_of_stock' => __('admin.out_of_stock'),
                    ]),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('admin.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'view' => Pages\ViewMaterial::route('/{record}'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
