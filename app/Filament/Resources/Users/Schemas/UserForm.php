<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Information')
                    ->description('Manage user basic details, authentication credentials, and system settings.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'provider' => 'Provider',
                                'customer' => 'Customer',
                            ])
                            ->required(),

                        Select::make('language')
                            ->options([
                                'en' => 'English',
                                'ml' => 'Malayalam',
                            ])
                            ->default('en')
                            ->required(),

                        Toggle::make('status')
                            ->label('Is Active')
                            ->helperText('Enable or disable user access to the platform.')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Location & Onboarding')
                    ->description('Track user onboarding progress and location preferences.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('area')
                            ->placeholder('e.g. Kakkanad, Kochi')
                            ->maxLength(255),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->numeric()
                                    ->minValue(-90)
                                    ->maxValue(90),

                                TextInput::make('longitude')
                                    ->numeric()
                                    ->minValue(-180)
                                    ->maxValue(180),
                            ]),

                        Select::make('onboarding_stage')
                            ->options([
                                'legal' => 'Terms Acceptance',
                                'profile' => 'Profile Setup',
                                'location' => 'Location Setup',
                                'completed' => 'Completed',
                            ])
                            ->default('legal')
                            ->required(),

                        DateTimePicker::make('onboarding_completed_at')
                            ->native(false)
                            ->placeholder('Onboarding completion date'),
                    ]),
            ]);
    }
}
