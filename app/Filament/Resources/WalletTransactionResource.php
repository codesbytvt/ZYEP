<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only: the ledger is append-only (see WalletService), so this resource
 * intentionally has no create/edit/delete pages — only a searchable list for
 * support to look up a user's credit history when a dispute comes in.
 */
class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Wallet';

    protected static ?string $navigationLabel = 'Wallet Transactions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'topup' => 'success',
                        'debit' => 'danger',
                        'refund' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('credits')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After'),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Reference'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'reversed' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('note')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'topup' => 'Top-up',
                        'debit' => 'Debit',
                        'refund' => 'Refund',
                    ]),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
        ];
    }
}
