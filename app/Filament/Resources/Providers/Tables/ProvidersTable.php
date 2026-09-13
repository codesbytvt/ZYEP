<?php

namespace App\Filament\Resources\Providers\Tables;

use App\Models\Provider;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('experience')
                    ->suffix(' years')
                    ->sortable(),
                TextColumn::make('area')
                    ->label('Service Area')
                    ->searchable(),
                IconColumn::make('status')
                    ->options([
                        'heroicon-o-clock' => 0,
                        'heroicon-o-check-circle' => 1,
                        'heroicon-o-x-circle' => 2,
                    ])
                    ->colors([
                        'warning' => 0,
                        'success' => 1,
                        'danger' => 2,
                    ]),
                IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn (Provider $record) => $record->update(['status' => 1]))
                    ->requiresConfirmation()
                    ->visible(fn (Provider $record) => $record->status == 0),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn (Provider $record) => $record->update(['status' => 2]))
                    ->requiresConfirmation()
                    ->visible(fn (Provider $record) => $record->status == 0),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
