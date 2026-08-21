<?php

namespace App\Filament\Resources\Providers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProviderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('business_name'),
                TextEntry::make('category_id')
                    ->numeric(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('experience')
                    ->numeric(),
                TextEntry::make('latitude')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('longitude')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('area')
                    ->placeholder('-'),
                TextEntry::make('preferred_call_time')
                    ->placeholder('-'),
                TextEntry::make('rating')
                    ->numeric(),
                TextEntry::make('status')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('aadhaar_number')
                    ->placeholder('-'),
                TextEntry::make('aadhaar_verification_method')
                    ->placeholder('-'),
                TextEntry::make('aadhaar_verification_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('aadhaar_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('aadhaar_rejection_reason')
                    ->label('Rejection Reason')
                    ->placeholder('-')
                    ->visible(fn ($record) => $record->aadhaar_verification_status === 'rejected'),
                TextEntry::make('aadhaar_document_path')
                    ->label('Aadhaar Document')
                    ->formatStateUsing(fn ($state) => $state ? 'View / Download Document' : '-')
                    ->url(fn ($state) => $state ? asset('storage/' . $state) : null)
                    ->openUrlInNewTab()
                    ->placeholder('-'),
            ]);
    }
}
