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
            // Filament appends a secondary "order by <table>.id" tiebreaker
            // for every table by default, but the raw (non-aggregated) id
            // column isn't valid in this GROUP BY query under
            // only_full_group_by SQL mode. Not needed here anyway since
            // MIN(id) already makes each row's identity deterministic.
            ->defaultKeySort(false)
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
