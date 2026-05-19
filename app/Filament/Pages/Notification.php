<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\NotificationAdmin;
use App\Services\FirebaseNotificationService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class Notification extends Page
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.notification';

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    public ?array $data = [];

    protected FirebaseNotificationService $firebaseNotificationService;

    public function __construct()
    {
        $this->firebaseNotificationService = new FirebaseNotificationService();
    }

    public function mount(): void
    {
        $this->form->fill([
            'receiver_type' => 'admin',
            'sendToAllAdmins' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([

            Radio::make('receiver_type')
                ->label('Receiver Type')
                ->options([
                    'admin' => 'Admin',
                    'user' => 'User',
                ])
                ->default('admin')
                ->inline()
                ->live(),

            Textarea::make('template')
                ->label(__('admin.template_notification'))
                ->required()
                ->rows(10),

            Checkbox::make('sendToAllAdmins')
                ->label(__('admin.send_to_all_admins'))
                ->default(true)
                ->live()
                ->visible(fn ($get) => $get('receiver_type') === 'admin'),

            Select::make('admin_id')
                ->label('Select Admin')
                ->options(Admin::pluck('name', 'id'))
                ->searchable()
                ->required(fn ($get) => $get('receiver_type') === 'admin' && !$get('sendToAllAdmins'))
                ->visible(fn ($get) => $get('receiver_type') === 'admin' && !$get('sendToAllAdmins')),

            Select::make('user_id')
                ->label('Select User')
                ->options(
                    User::all()
                        ->mapWithKeys(function ($user) {
                            return [
                                $user->id => $user->full_name . ' - ' . $user->phone_number,
                            ];
                        })
                        ->toArray()
                )
                ->searchable()
                ->required(fn ($get) => $get('receiver_type') === 'user')
                ->visible(fn ($get) => $get('receiver_type') === 'user'),
        ]);
    }

    public function submit(): void
    {
        try {
            $state = $this->form->getState();
            $receiverType = $state['receiver_type'] ?? 'admin';

            if ($receiverType === 'admin') {
                $sendToAll = $state['sendToAllAdmins'] ?? false;

                if ($sendToAll) {
                    $this->firebaseNotificationService->sendNotification(
                        __('admin.new_notification'),
                        $state['template'],
                        'all',
                        true,
                        ['type' => 'admin_notification']
                    );

                    NotificationFacade::send(
                        Admin::all(),
                        new NotificationAdmin(null, $state['template'], __('admin.notification'))
                    );
                } else {
                    $admin = Admin::find($state['admin_id'] ?? null);

                    if ($admin && $admin->fcm_token) {
                        $this->firebaseNotificationService->sendNotification(
                            __('admin.new_notification'),
                            $state['template'],
                            $admin->fcm_token,
                            false,
                            ['admin_id' => (string) $admin->id, 'type' => 'admin_notification']
                        );

                        NotificationFacade::send(
                            $admin,
                            new NotificationAdmin(null, $state['template'], __('admin.notification'))
                        );
                    }
                }
            } elseif ($receiverType === 'user') {
                $user = User::find($state['user_id'] ?? null);

                if ($user && $user->fcm_token) {
                    $this->firebaseNotificationService->sendNotification(
                        __('admin.new_notification'),
                        $state['template'],
                        $user->fcm_token,
                        false,
                        ['user_id' => (string) $user->id, 'type' => 'user_notification']
                    );
                }
            }

            FilamentNotification::make()
                ->title(__('admin.notification_sent_successfully'))
                ->success()
                ->send();

            $this->form->fill([
                'receiver_type' => 'admin',
                'sendToAllAdmins' => true,
            ]);

        } catch (\Exception $e) {
            FilamentNotification::make()
                ->title(__('admin.notification_send_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->super_admin == 1;
    }
}