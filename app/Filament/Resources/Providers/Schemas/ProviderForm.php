<?php

namespace App\Filament\Resources\Providers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('business_name')
                    ->required(),
                \Filament\Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('experience')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('area')
                    ->default(null),
                TextInput::make('preferred_call_time')
                    ->placeholder('e.g. Mon-Fri 9:00 AM - 5:00 PM, or Anytime')
                    ->default(null),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(0),
                \Filament\Forms\Components\Toggle::make('is_verified')
                    ->label('Is Verified')
                    ->default(false),

                Section::make('Aadhaar Verification Details')
                    ->description('Audit details for Aadhaar OTP and manual document uploads.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('aadhaar_number')
                            ->length(12)
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\Select::make('aadhaar_verification_method')
                            ->options([
                                'otp' => 'OTP',
                                'manual' => 'Manual Document',
                            ])
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\Select::make('aadhaar_verification_status')
                            ->options([
                                'unverified' => 'Unverified',
                                'pending' => 'Pending Admin Audit',
                                'verified' => 'Verified',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\DateTimePicker::make('aadhaar_verified_at')
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\Textarea::make('aadhaar_rejection_reason')
                            ->label('Rejection Reason')
                            ->placeholder('Enter the reason for rejection (this will be emailed to the provider)')
                            ->visible(fn ($get): bool => $get('aadhaar_verification_status') === 'rejected')
                            ->required(fn ($get): bool => $get('aadhaar_verification_status') === 'rejected')
                            ->columnSpanFull(),
                        \Filament\Forms\Components\FileUpload::make('aadhaar_document_path')
                            ->label('Aadhaar Document')
                            ->disk('public')
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull()
                            ->dehydrated(),
                    ]),
            ]);
    }
}
