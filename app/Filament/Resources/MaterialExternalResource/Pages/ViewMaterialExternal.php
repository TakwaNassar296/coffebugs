<?php

namespace App\Filament\Resources\MaterialExternalResource\Pages;

use App\Filament\Resources\MaterialExternalResource;
use App\Support\MaterialUnit;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialExternal extends ViewRecord
{
    protected static string $resource = MaterialExternalResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('Image')
                            ->circular(),
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('code')
                            ->label('Code'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('is_active')
                            ->label('Is Active')
                            ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),
                    ])->columns(3),

                Section::make('Stock Status')
                    ->schema([
                        TextEntry::make('quantity_in_stock')
                            ->label('Quantity in Stock')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(fn($record) => ' ' . MaterialUnit::label($record->unit)),
                        TextEntry::make('min_stock_level')
                            ->label('Min Stock Level')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state)))
                            ->color(fn(string $state): string => match ($state) {
                                'good' => 'success',
                                'low_stock' => 'warning',
                                'out_of_stock' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(4),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
