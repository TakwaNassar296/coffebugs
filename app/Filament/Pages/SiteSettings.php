<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Traits\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\LocaleSwitcher;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SiteSettings extends Page
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

    public static function getNavigationGroup(): string
    {
        return __('admin.settings');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('strings.settings'))
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('strings.image'))
                            ->image()
                            ->directory('uploads/site_images')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->helperText(__('admin.main_image_helper'))
                            ->required(),

                        FileUpload::make('images')
                            ->label(__('strings.main_banner_images'))
                            ->image()
                            ->directory('uploads/images')
                            ->maxSize(2048)
                            ->multiple()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->helperText(__('admin.gallery_images_helper'))
                            ->required(),

                        // TextInput::make('delivery_charge')
                        //     ->label(__('strings.delivery_charge'))
                        //     ->numeric()
                        //     ->minValue(0)
                        //     ->required(),

                        TextInput::make('driver_finance')
                            ->label(__('strings.driver_finance'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),

                        TextInput::make('tax_percentage')
                            ->label(__('strings.tax_percentage'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('free_delivery_minimum')
                            ->label(__('strings.free_delivery_minimum'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('app_link_app_store')
                            ->label(__('strings.app_link_app_store'))
                            ->maxLength(2048)
                            ->rules(['nullable', 'string', 'max:2048'])
                            ->columnSpanFull(),

                        TextInput::make('app_link_google_play')
                            ->label(__('strings.app_link_google_play'))
                            ->maxLength(2048)
                            ->rules(['nullable', 'string', 'max:2048'])
                            ->columnSpanFull(),

                        TextInput::make('app_store_version')
                            ->label(__('strings.app_store_version'))
                            ->placeholder('1.0.0')
                            ->maxLength(50)
                            ->rules(['nullable', 'string', 'max:50']),

                        TextInput::make('google_play_version')
                            ->label(__('strings.google_play_version'))
                            ->placeholder('1.0.0')
                            ->maxLength(50)
                            ->rules(['nullable', 'string', 'max:50']),

                        Textarea::make('text_cart')
                            ->label(__('strings.cart_page_text'))
                            ->maxLength(65535)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('text_order')
                            ->label(__('strings.order_confirmation_page_text'))
                            ->maxLength(65535)
                            ->required()
                            ->columnSpanFull(),

                        Repeater::make('features')
                            ->label(__('strings.features'))
                            ->schema([
                                TextInput::make('title')->label(__('strings.title'))->required()->columnSpanFull(),
                                RichEditor::make('description')->label(__('strings.description'))->required(),
                                FileUpload::make('image')
                                    ->label(__('strings.image'))
                                    ->image()
                                    ->directory('uploads/site_images')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                                    ->helperText(__('admin.main_image_helper'))
                                    ->required(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])->columns(2)
            ])->statePath('data');
    }

    public function mount()
    {
        $siteSetting = SiteSetting::with('features')->first();
        if ($siteSetting) {
            $images = $siteSetting->images;
            if (is_string($images)) {
                $images = json_decode($images, true) ?: [];
            }

            $this->form->fill([
                'image' => $siteSetting->image ? $siteSetting->image : null,
                'images' => $images,
                // 'delivery_charge' => $siteSetting->delivery_charge ?? '',
                'driver_finance' => $siteSetting->driver_finance ?? '',
                'tax_percentage' => $siteSetting->tax_percentage ?? '',
                'free_delivery_minimum' => $siteSetting->free_delivery_minimum ?? '',
                'app_link_app_store' => $siteSetting->app_link_app_store ?? '',
                'app_link_google_play' => $siteSetting->app_link_google_play ?? '',
                'app_store_version' => $siteSetting->app_store_version ?? '',
                'google_play_version' => $siteSetting->google_play_version ?? '',
                'text_cart' => $siteSetting->text_cart ?? '',
                'text_order' => $siteSetting->text_order ?? '',
                'features' => $siteSetting->features->toArray(),
            ]);
        } else {
            session()->flash('error', __('strings.settings_credentials_not_found'));
        }
    }

    public function submit()
    {
        $data = $this->form->getState();
        $settings = SiteSetting::firstOrNew(['id' => 1]);
        $imagesJson = !empty($data['images']) ? json_encode($data['images']) : null;

        $settings->fill([
            'image' => $data['image'],
            'images' => $imagesJson,
            // 'delivery_charge' => $data['delivery_charge'],
            'driver_finance' => $data['driver_finance'],
            'tax_percentage' => $data['tax_percentage'],
            'free_delivery_minimum' => $data['free_delivery_minimum'],
            'app_link_app_store' => $data['app_link_app_store'] ?? null,
            'app_link_google_play' => $data['app_link_google_play'] ?? null,
            'app_store_version' => $data['app_store_version'] ?? null,
            'google_play_version' => $data['google_play_version'] ?? null,
            'text_cart' => $data['text_cart'],
            'text_order' => $data['text_order'],
        ]);

        if ($settings->save()) {
            if (isset($data['features'])) {
                $settings->features()->delete();
                foreach ($data['features'] as $feature) {
                    $settings->features()->create([
                        'title' => $feature['title'],
                        'description' => $feature['description'],
                        'image' => $feature['image'],
                    ]);
                }
            }
            Notification::make()
                ->title(__('strings.settings_updated_successfully'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('strings.settings_update_failed'))
                ->error()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->super_admin == 1;
    }
}
