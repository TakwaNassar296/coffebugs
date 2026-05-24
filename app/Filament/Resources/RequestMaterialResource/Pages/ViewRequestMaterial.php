<?php

namespace App\Filament\Resources\RequestMaterialResource\Pages;

use App\Filament\Resources\RequestMaterialResource;
use App\Models\MaterialRequestApproval;
use App\Models\RequestMaterial;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewRequestMaterial extends ViewRecord
{
    protected static string $resource = RequestMaterialResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->guard('admin')->user();
        $isSuperAdmin = $user && $user->super_admin == 1;

        return [
            Action::make('approve')
                ->label(__('admin.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn($record) => $isSuperAdmin && $record && $record->status === 'pending')
                ->modalHeading(__('admin.approve_material_request'))
                ->modalSubmitActionLabel(__('admin.approve'))
                ->form([
                    TextInput::make('quantity')
                        ->label(__('admin.approved_quantity'))
                        ->numeric()
                        ->required()
                        ->default(fn($record) => $record->quantity)
                        ->minValue(0.01)
                        ->maxValue(fn($record) => $record->quantity)
                        ->helperText(fn($record) => __('admin.maximum', ['max' => $record->quantity ?? 0])),

                    Textarea::make('comment')
                        ->label(__('admin.description'))
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data, RequestMaterial $record) {
                    DB::beginTransaction();
                    try {
                        $adminId = auth()->guard('admin')->id();
                        $approvedQty = (float) $data['quantity'];
                        $requestedQty = (float) $record->quantity;

                        $status = ($approvedQty < $requestedQty) ? 'partially_approved' : 'approved';

                        $record->update([
                            'status' => $status,
                            'approved_quantity' => $approvedQty,
                        ]);

                        MaterialRequestApproval::create([
                            'request_material_id' => $record->id,
                            'admin_id' => $adminId,
                            'action' => ($approvedQty < $requestedQty) ? 'updated' : 'approved',
                            'quantity' => $approvedQty,
                            'comment' => $data['comment'] ?? null,
                        ]);

                        DB::commit();
                        Notification::make()->title(__('admin.request_approved_successfully'))->success()->send();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()->title(__('admin.error'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('reject')
                ->label(__('admin.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn($record) => $isSuperAdmin && $record && $record->status === 'pending')
                ->modalHeading(__('admin.reject_material_request'))
                ->modalSubmitActionLabel(__('admin.reject'))
                ->form([
                    Textarea::make('comment')
                        ->label(__('admin.rejection_reason'))
                        ->required()
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText(__('admin.please_provide_rejection_reason')),
                ])
                ->action(function (array $data, RequestMaterial $record) {
                    DB::beginTransaction();
                    try {
                        $record->update([
                            'status' => 'rejected',
                            'approved_quantity' => null,
                        ]);

                        MaterialRequestApproval::create([
                            'request_material_id' => $record->id,
                            'admin_id' => auth()->guard('admin')->id(),
                            'action' => 'rejected',
                            'quantity' => null,
                            'comment' => $data['comment'],
                        ]);

                        DB::commit();
                        Notification::make()->title(__('admin.request_rejected'))->success()->send();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()->title(__('admin.error'))->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
