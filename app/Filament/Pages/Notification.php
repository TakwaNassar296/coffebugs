<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Models\User;
use App\Models\Driver;
use App\Notifications\AdminDashboardNotification;
use App\Notifications\DriverNotification;
use App\Notifications\UserNotification;
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

    public static function getNavigationGroup(): string
    {
        return __('admin.notifications');
    }

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
                    'driver' => 'Driver',
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

            Select::make('driver_id')
                ->label('Select Driver')
                ->options(
                    Driver::all()
                        ->mapWithKeys(function ($driver) {
                            return [
                                $driver->id => $driver->first_name . ' ' . $driver->last_name . ' - ' . $driver->phone_number,
                            ];
                        })
                        ->toArray()
                )
                ->searchable()
                ->required(fn ($get) => $get('receiver_type') === 'driver')
                ->visible(fn ($get) => $get('receiver_type') === 'driver'),
        ]);
    }

    public function submit(): void
    {
        try {
            $state = $this->form->getState();
            $receiverType = $state['receiver_type'] ?? 'admin';
            $title = __('admin.new_notification');
            $message = $state['template'];

            if ($receiverType === 'admin') {
                $sendToAll = $state['sendToAllAdmins'] ?? false;

                if ($sendToAll) {
                    $this->firebaseNotificationService->sendNotification($title, $message, 'all', true, ['type' => 'admin_notification']);
                    NotificationFacade::send(Admin::all(), new AdminDashboardNotification($message, $title, 'admin_notification'));
                } else {
                    $admin = Admin::find($state['admin_id'] ?? null);
                    if ($admin && $admin->fcm_token) {
                        $this->firebaseNotificationService->sendNotification($title, $message, $admin->fcm_token, false, ['admin_id' => (string) $admin->id, 'type' => 'admin_notification']);
                        NotificationFacade::send($admin, new AdminDashboardNotification($message, $title, 'admin_notification'));
                    }
                }
            } elseif ($receiverType === 'user') {
                $user = User::find($state['user_id'] ?? null);
                if ($user) {
                    NotificationFacade::send($user, new UserNotification($title, $message));
                    if ($user->fcm_token) {
                        $this->firebaseNotificationService->sendNotification($title, $message, $user->fcm_token, false, ['user_id' => (string) $user->id, 'type' => 'user_notification']);
                    }
                }
            } elseif ($receiverType === 'driver') {
                $driver = Driver::find($state['driver_id'] ?? null);
                if ($driver) {
                    NotificationFacade::send($driver, new DriverNotification($title, $message));
                    if ($driver->fcm_token) {
                        $this->firebaseNotificationService->sendNotification($title, $message, $driver->fcm_token, false, ['driver_id' => (string) $driver->id, 'type' => 'driver_notification']);
                    }
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