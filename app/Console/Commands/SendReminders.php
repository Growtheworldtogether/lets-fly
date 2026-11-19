<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\PaymentService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Email balance reminders: 3 days before the due date (day 7 of 10) and 1 day before (day 9)';

    public function handle(PaymentService $payments): int
    {
        $sent = 0;
        $rules = ['day7' => [3, 'Your balance is due in 3 days'], 'day9' => [1, 'Your balance is due tomorrow']];
        Reservation::with('user', 'reminders')->where('status', 'balance_invoiced')->whereNotNull('balance_due_at')->whereNull('cancelled_at')->each(function ($r) use ($payments, $rules, &$sent) {
            foreach ($rules as $kind => [$days, $heading]) {
                $window = $r->balance_due_at->copy()->subDays($days);
                // Due for a reminder once its day has arrived, as long as the due date has not already passed.
                if (now()->lt($window) || now()->gt($r->balance_due_at) || $r->reminders->contains('kind', $kind)) {
                    continue;
                }
                $r->reminders()->create(['kind' => $kind, 'sent_at' => now()]);
                $payments->notice($r->user, $heading, ['Your balance is due on '.$r->balance_due_at->timezone('America/New_York')->format('F j, Y').'.', 'Sign in to your dashboard for the wire details and to upload your proof.']);
                $sent++;
                break;
            }
        });
        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
