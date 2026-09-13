<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.settings';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::SevenExtraLarge;
    }

    public function getBreadcrumbs(): array
    {
        return [
            null => 'Administration',
            route('filament.admin.pages.settings') => 'Settings',
        ];
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'app_name' => Setting::get('app_name', 'ZYEP'),
            'provider_auto_approval' => Setting::get('provider_auto_approval', 'none'),
            'verification_required' => Setting::get('verification_required', true),
            'max_providers_per_user' => Setting::get('max_providers_per_user', 1),
            'search_visibility' => Setting::get('search_visibility', 'guest'),
            'search_radius' => Setting::get('search_radius', 10),
            'demo_mode' => Setting::get('demo_mode', false),
            'customer_terms' => Setting::get('customer_terms', 'Please agree to our terms and conditions.'),
            'provider_terms' => Setting::get('provider_terms', 'Please agree to our provider terms.'),
            'privacy_policy' => Setting::get('privacy_policy', 'Privacy policy coming soon.'),
            'terms_version' => Setting::get('terms_version', 1),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                \Filament\Schemas\Components\Tabs::make('Settings')
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Section::make('General Settings')
                                    ->description('Basic platform configuration.')
                                    ->schema([
                                        TextInput::make('app_name')
                                            ->label('Platform Name')
                                            ->placeholder('ZYEP')
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Providers')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Provider Settings')
                                    ->description('Manage how providers are approved and verified.')
                                    ->schema([
                                        Select::make('provider_auto_approval')
                                            ->label('Auto Approval Policy')
                                            ->options([
                                                'paid only' => 'Paid Only (Auto-approve after payment)',
                                                'none' => 'None (Manual approval by admin)',
                                            ])
                                            ->required()
                                            ->columnSpanFull(),
                                        Toggle::make('verification_required')
                                            ->label('Verification Required')
                                            ->helperText('Whether providers need to be verified before appearing in search.')
                                            ->columnSpanFull(),
                                        TextInput::make('max_providers_per_user')
                                            ->label('Max Providers Per User')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Privacy')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Privacy & Visibility')
                                    ->description('Control who can access provider information and search limits.')
                                    ->schema([
                                        Select::make('search_visibility')
                                            ->label('Search Visibility')
                                            ->options([
                                                'guest' => 'Guest (Everyone can search)',
                                                'member only' => 'Member Only (Only logged-in users)',
                                            ])
                                            ->required()
                                            ->columnSpanFull(),
                                        TextInput::make('search_radius')
                                            ->label('Default Search Radius (km)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Legal')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Terms and Conditions')
                                    ->description('Manage platform legal agreements.')
                                    ->schema([
                                        \Filament\Forms\Components\Textarea::make('customer_terms')
                                            ->label('Customer Terms & Conditions')
                                            ->rows(10)
                                            ->required()
                                            ->columnSpanFull(),
                                        \Filament\Forms\Components\Textarea::make('provider_terms')
                                            ->label('Provider Terms & Conditions')
                                            ->rows(10)
                                            ->required()
                                            ->columnSpanFull(),
                                        TextInput::make('terms_version')
                                            ->label('Terms Version')
                                            ->helperText('Increment this to force all users to re-accept terms.')
                                            ->numeric()
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Privacy Policy')
                                    ->description('Manage the platform privacy policy displayed to users.')
                                    ->schema([
                                        \Filament\Forms\Components\Textarea::make('privacy_policy')
                                            ->label('Privacy Policy')
                                            ->rows(12)
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Development')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Section::make('Development Settings')
                                    ->description('Settings for development and testing.')
                                    ->schema([
                                        Toggle::make('demo_mode')
                                            ->label('Demo Mode')
                                            ->helperText('Enables automatic OTP pre-filling on the login page for testing.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('migrate')
                ->label('Run Database Migrations')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                        $output = \Illuminate\Support\Facades\Artisan::output();
                        
                        Notification::make()
                            ->title('Migration Successful')
                            ->body($output)
                            ->success()
                            ->persistent()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Migration Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            Action::make('save')
                ->label('Save Settings')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
    }
}
