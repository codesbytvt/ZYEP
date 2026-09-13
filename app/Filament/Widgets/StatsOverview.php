<?php

namespace App\Filament\Widgets;

use App\Models\ActionLog;
use App\Models\Provider;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        
        return [
            Stat::make('Activity Today', ActionLog::whereDate('created_at', $today)->count())
                ->description('Clicks, Views, and Searches')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('success'),
            
            Stat::make('New Providers (Pending)', Provider::where('status', 0)->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Popular Category Today', $this->getMostPopularCategory()),
        ];
    }

    private function getMostPopularCategory(): string
    {
        $log = ActionLog::where('action_type', 'category_click')
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('target_id, count(*) as count')
            ->groupBy('target_id')
            ->orderByDesc('count')
            ->first();

        if (!$log) return 'N/A';

        $category = \DB::table('mcategories')->where('id', $log->target_id)->first();
        return $category ? "{$category->name} ({$log->count})" : 'N/A';
    }
}
