<?php

namespace App\Jobs;

use App\Services\Logs\LogAccountAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogAccountAudit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The audit log instance.
     */
    public array $auditLog;

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new LogAccountAction)->execute([
            'company_id' => $this->auditLog['company_id'],
            'author_id' => $this->auditLog['author_id'],
            'author_name' => $this->auditLog['author_name'],
            'audited_at' => $this->auditLog['audited_at'],
            'action' => $this->auditLog['action'],
            'objects' => $this->auditLog['objects'],
        ]);
    }
}
