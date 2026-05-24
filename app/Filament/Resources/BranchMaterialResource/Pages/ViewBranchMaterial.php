<?php

namespace App\Filament\Resources\BranchMaterialResource\Pages;

use App\Filament\Resources\BranchMaterialResource;
use App\Support\MaterialUnit;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBranchMaterial extends ViewRecord
{
    protected static string $resource = BranchMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Branch & Material Details')
                    ->schema([
                        TextEntry::make('branch.name')->label('Branch'),
                        TextEntry::make('material.name')->label('Material'),
                        TextEntry::make('unit')
                            ->label('Unit')
                            ->badge()
                            ->formatStateUsing(fn($state) => MaterialUnit::label($state)),
                    ])->columns(3),

                Section::make('Stock Management')
                    ->schema([
                        TextEntry::make('quantity_in_stock')
                            ->label('Quantity in Stock')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(fn($record) => ' ' . MaterialUnit::label($record->unit)),

                        TextEntry::make('current_quantity')
                            ->label('Current Quantity')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(fn($record) => ' ' . MaterialUnit::label($record->unit)),

                        TextEntry::make('available_to_request')
                            ->label('Available to Request')
                            ->state(fn($record) => max(0, (float)$record->max_limit - (float)$record->current_quantity))
                            ->numeric(decimalPlaces: 2)
                            ->color('success')
                            ->weight('bold')
                            ->suffix(fn($record) => ' ' . MaterialUnit::label($record->unit)),
                    ])->columns(3),

                Section::make('Stock Limits')
                    ->schema([
                        TextEntry::make('min_limit')
                            ->label('Minimum Limit')
                            ->numeric(decimalPlaces: 2)
                            ->color('warning'),

                        TextEntry::make('max_limit')
                            ->label('Maximum Limit')
                            ->numeric(decimalPlaces: 2)
                            ->color('primary'),
                    ])->columns(2),
            ]);
    }
}
