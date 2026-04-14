<?php

namespace App\Filament\Pages;

use Filament\Actions;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\SiteSetting;
use Filament\Actions\Action;
use App\Traits\GeneralSettings;
use Filament\Forms\Components\Tabs;
use Filament\Actions\LocaleSwitcher;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Support\Htmlable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;


class SiteSettingsPage extends Page
{
    public $record;

    use HasPageShield;

    public ?array $data = [];

    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.site-settings-page';

    public static function getNavigationLabel(): string
    {

        return __('strings.settings');
    }

    public function getTitle(): string|Htmlable
    {
        return __('strings.settings');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Section::make(__('strings.settings'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('strings.title'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('description')
                            ->label(__('strings.description'))
                            ->required()
                            ->maxLength(255),

                        FileUpload::make('image')
                            ->label(__('strings.image'))
                            ->image()
                            ->directory('uploads/site_images')
                            ->maxSize(2048)
                            ->rules('image', 'mimes:jpg,jpeg,png,webp')
                            ->required(),

                        TextInput::make('delivery_charge')
                            ->label('تكلفة التوصيل')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('tax_percentage')
                            ->label('نسبة الضريبة')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('free_delivery_minimum')
                            ->label('الحد الأدنى للتوصيل المجاني')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])

            ])->statePath('data');
    }


    public function mount()
    {
        $siteSetting = SiteSetting::first();
        if ($siteSetting) {
            $this->form->fill([
                'title' => $siteSetting->title ?? '',
                'image' => $siteSetting->image ?  $siteSetting->image : null,
                'description' => $siteSetting->description ?? '',
                'delivery_charge' => $siteSetting->delivery_charge ?? '',
                'tax_percentage' => $siteSetting->tax_percentage ?? '',
                'free_delivery_minimum' => $siteSetting->free_delivery_minimum ?? '',
            ]);
        } else {
            session()->flash('error', 'Settings credentials not found.');
        }
    }


    public function submit()
    {
        $data = $this->form->getState();

        $settings = SiteSetting::firstOrNew(['id' => 1]);

        $settings->fill([
            'title' => $data['title'],
            'description' => $data['description'],
            'image' => $data['image'],
            'delivery_charge' => $data['delivery_charge'],
            'tax_percentage' => $data['tax_percentage'],
            'free_delivery_minimum' => $data['free_delivery_minimum'],

        ]);

        if ($settings->save()) {
            Notification::make()
                ->title(__('setting.Setting updated successfully'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('setting.Failed to update setting'))
                ->error()
                ->send();
        }
    }


   public static function canAccess(): bool
    {
        return auth('admin')->user()?->super_admin == 1;
    }
}
