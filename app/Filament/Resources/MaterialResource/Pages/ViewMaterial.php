<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use App\Support\MaterialUnit;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterial extends ViewRecord
{
    protected static string $resource = MaterialResource::class;

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
                        TextEntry::make('type')
                            ->label('Type'),
                        TextEntry::make('color')
                            ->label('Color'),
                        TextEntry::make('is_active')
                            ->label('Is Active')
                            ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),
                    ])->columns(3),

                Section::make('Stock Status')
                    ->schema([
                        TextEntry::make('quantity_in_stock')
                            ->label('Quantity in Stock')
                            ->numeric(decimalPlaces: 3)
                            ->suffix(fn($record) => ' ' . MaterialUnit::label($record->unit)),
                        TextEntry::make('current_quantity_material')
                            ->label('Current Quantity')
                            ->numeric(decimalPlaces: 3)
                            ->suffix(fn($record) => ' ' . MaterialUnit::label($record->unit)),
                        TextEntry::make('min_stock_level')
                            ->label('Min Stock Level'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'out_of_stock' => 'danger',
                                'low_stock' => 'warning',
                                'good' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn($state) => __("admin.{$state}")),    

                   
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
