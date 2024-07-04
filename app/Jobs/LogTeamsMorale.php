<?php

namespace App\Jobs;

use App\Models\Company\Team;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogTeamsMorale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Carbon $date;

    /**
     * Create a new job instance.
     */
    public function __construct(Carbon $date)
    {
        $this->date = $date;
    }

    /**
     * Log the morale of all teams by looking at the current day and all the
     * employees of those teams.
     * This job is meant to be executed every day at 11pm (UTC).
     */
    public function handle(): void
    {
        Team::select('id')->chunk(100, function ($teams) {
            $teams->each(function (Team $team) {
                ProcessTeamMorale::dispatch([
                    'team_id' => $team->id,
                    'date' => $this->date,
                ])->onQueue('low');
            });
        });
    }
}
