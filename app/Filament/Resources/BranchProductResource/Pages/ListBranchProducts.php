<?php

namespace App\Filament\Resources\BranchProductResource\Pages;

use App\Filament\Resources\BranchProductResource;
use App\Models\Branch;
use App\Models\Product;
use App\Support\BranchProductAdmin;
use Filament\Actions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListBranchProducts extends ListRecords
{
    protected static string $resource = BranchProductResource::class;

    protected function configureCreateAction(CreateAction|Tables\Actions\CreateAction $action): void
    {
        parent::configureCreateAction($action);

        if (static::getResource()::hasPage('create')) {
            return;
        }

        if ($action instanceof CreateAction) {
            $action->using(function (array $data, HasActions $livewire): Model {
                return BranchProductAdmin::createOrMerge($data);
            });

            return;
        }

        if ($action instanceof Tables\Actions\CreateAction) {
            $action->using(function (array $data, Table $table): Model {
                return BranchProductAdmin::createOrMerge($data);
            });
        }
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->whereIn('id', function ($query) {
                $query->from('branch_product')
                    ->selectRaw('MIN(id)')
                    ->groupBy('branch_id', 'product_id');
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulkAddBranchProducts')
                ->label(__('admin.bulk_add_branch_products'))
                ->icon('heroicon-o-rectangle-stack')
                ->modalHeading(__('admin.bulk_add_branch_products_heading'))
                ->modalDescription(__('admin.bulk_add_branch_products_description'))
                ->modalWidth('2xl')
                ->visible(fn (): bool => static::getResource()::canCreate())
                ->form([
                    Forms\Components\Select::make('branch_id')
                        ->label(__('admin.branch'))
                        ->options(fn (): array => Branch::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('product_ids')
                        ->label(__('admin.products'))
                        ->options(fn (): array => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->multiple()
                        ->required()
                        ->minItems(1),

                    Forms\Components\TextInput::make('amount')
                        ->label(__('admin.amount'))
                        ->helperText(__('admin.bulk_add_branch_products_amount_hint'))
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(0)
                        ->rules(['min:0']),

                    Forms\Components\Toggle::make('status')
                        ->label(__('admin.status'))
                        ->default(true)
                        ->inline(false),
                ])
                ->action(function (array $data): void {
                    $count = BranchProductAdmin::createManyForBranch(
                        (int) $data['branch_id'],
                        $data['product_ids'] ?? [],
                        (float) ($data['amount'] ?? 0),
                        (bool) ($data['status'] ?? true),
                    );

                    Notification::make()
                        ->success()
                        ->title(__('admin.bulk_add_branch_products_success', ['count' => $count]))
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
