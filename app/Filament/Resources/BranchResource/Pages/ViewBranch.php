<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('view_consumption')
                ->label(__('admin.view_consumption_history'))
                ->icon('heroicon-o-arrow-trending-down')
                ->color('info')
                ->url(fn () => BranchResource::getUrl('view', ['record' => $this->record]) . '?activeRelationManager=0&activeTab=materialConsumptions')
                ->openUrlInNewTab(false),
        ];
    }

 
}
