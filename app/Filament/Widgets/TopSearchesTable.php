<?php

namespace App\Filament\Widgets;

use App\Models\ActionLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopSearchesTable extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActionLog::where('action_type', 'search')
                    ->whereNotNull('metadata->keyword')
                    ->select(DB::raw('MIN(id) as id'), 'metadata->keyword as keyword', DB::raw('count(*) as search_count'))
                    ->groupBy('keyword')
                    ->having('keyword', '!=', '')
                    ->orderByDesc('search_count')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('keyword')
                    ->label('Search Keyword')
                    ->searchable(),
                Tables\Columns\TextColumn::make('search_count')
                    ->label('Total Searches')
                    ->badge()
                    ->color('primary'),
            ])
            ->heading('Top Searches');
    }
}
