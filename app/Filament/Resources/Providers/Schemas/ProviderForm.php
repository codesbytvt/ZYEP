<?php

namespace App\Filament\Resources\Providers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

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
                    ->label('Years of Experience')
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
                    ->label('Service Area')
                    ->default(null),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        0 => 'Pending',
                        1 => 'Approved',
                        2 => 'Rejected',
                    ])
                    ->required()
                    ->default(0),
                \Filament\Forms\Components\Toggle::make('is_verified')
                    ->label('Verified')
                    ->default(false),
            ]);
    }
}
