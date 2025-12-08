<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use App\Services\FCMService;
use Carbon\Carbon;

class CheckAssignmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignments:check-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and send assignment reminders (H-3, D-day, H+3)';

    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking assignment reminders...');
        
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->copy()->startOfDay();
        
        // Only run at 07:00 AM (between 07:00 and 07:59)
        if ($now->hour != 7) {
            $this->info('⏰ Reminders only sent at 07:00 AM. Current time: ' . $now->format('H:i'));
            return;
        }
        
        // H-3 (3 days before deadline)
        $threeDaysBefore = $today->copy()->addDays(3);
        $this->sendReminders($threeDaysBefore, 'h_minus_3', '📅 Assignment Due in 3 Days', 
            'Reminder: "{title}" is due in 3 days. Start working on it!');
        
        // H-2 (2 days before deadline)
        $twoDaysBefore = $today->copy()->addDays(2);
        $this->sendReminders($twoDaysBefore, 'h_minus_2', '📅 Assignment Due in 2 Days', 
            'Reminder: "{title}" is due in 2 days. Make sure you\'re on track!');
        
        // H-1 (1 day before deadline - tomorrow)
        $oneDayBefore = $today->copy()->addDays(1);
        $this->sendReminders($oneDayBefore, 'h_minus_1', '⏰ Assignment Due Tomorrow', 
            'Important: "{title}" is due tomorrow! Finish it today if you can.');
        
        // D-day (deadline today)
        $this->sendReminders($today, 'd_day', '🔥 Assignment Due TODAY!', 
            'Urgent: "{title}" is due TODAY! Complete it before the deadline.');
        
        // H+1 (1 day overdue, if not done)
        $oneDayAfter = $today->copy()->subDays(1);
        $this->sendOverdueReminders($oneDayAfter, 'h_plus_1', '⚠️ Assignment 1 Day Overdue', 
            'Warning: "{title}" is 1 day overdue. Please complete it ASAP!');
        
        // H+2 (2 days overdue, if not done)
        $twoDaysAfter = $today->copy()->subDays(2);
        $this->sendOverdueReminders($twoDaysAfter, 'h_plus_2', '⚠️ Assignment 2 Days Overdue', 
            'Warning: "{title}" is 2 days overdue. Please submit it soon!');
        
        // H+3 (3 days overdue, if not done)
        $threeDaysAfter = $today->copy()->subDays(3);
        $this->sendOverdueReminders($threeDaysAfter, 'h_plus_3', '❗ Assignment 3 Days Overdue', 
            'Final reminder: "{title}" is 3 days overdue. Action required!');
        
        $this->info('✅ Assignment reminders checked successfully!');
    }

    /**
     * Send reminders for specific date
     */
    private function sendReminders($targetDate, $notificationType, $title, $bodyTemplate)
    {
        $assignments = Assignment::where('is_done', false)
            ->whereDate('deadline', $targetDate->toDateString())
            ->where(function($query) use ($notificationType) {
                $query->whereNull('last_notification_type')
                      ->orWhere('last_notification_type', '!=', $notificationType);
            })
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($assignments as $assignment) {
            if ($assignment->user && $assignment->user->fcm_token) {
                $body = str_replace('{title}', $assignment->title, $bodyTemplate);
                
                try {
                    $this->fcmService->sendNotification(
                        $assignment->user->fcm_token,
                        $title,
                        $body,
                        [
                            'type' => 'assignment_reminder',
                            'assignment_id' => (string) $assignment->id,
                            'notification_type' => $notificationType,
                            'deadline' => $assignment->deadline->toIso8601String(),
                        ]
                    );
                    
                    // Update last notification type
                    $assignment->last_notification_type = $notificationType;
                    $assignment->save();
                    
                    $sent++;
                } catch (\Exception $e) {
                    $this->error("Failed to send notification for assignment {$assignment->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("📤 Sent {$sent} {$notificationType} reminders");
    }

    /**
     * Send overdue reminders
     */
    private function sendOverdueReminders($targetDate, $notificationType, $title, $bodyTemplate)
    {
        $assignments = Assignment::where('is_done', false)
            ->whereDate('deadline', $targetDate->toDateString())
            ->where(function($query) use ($notificationType) {
                $query->whereNull('last_notification_type')
                      ->orWhere('last_notification_type', '!=', $notificationType);
            })
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($assignments as $assignment) {
            if ($assignment->user && $assignment->user->fcm_token) {
                $body = str_replace('{title}', $assignment->title, $bodyTemplate);
                
                try {
                    $this->fcmService->sendNotification(
                        $assignment->user->fcm_token,
                        $title,
                        $body,
                        [
                            'type' => 'assignment_reminder',
                            'assignment_id' => (string) $assignment->id,
                            'notification_type' => $notificationType,
                            'deadline' => $assignment->deadline->toIso8601String(),
                        ]
                    );
                    
                    // Update last notification type
                    $assignment->last_notification_type = $notificationType;
                    $assignment->save();
                    
                    $sent++;
                } catch (\Exception $e) {
                    $this->error("Failed to send notification for assignment {$assignment->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("📤 Sent {$sent} {$notificationType} reminders");
    }
}
