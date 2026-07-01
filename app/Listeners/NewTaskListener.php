<?php

namespace App\Listeners;

use App\Events\NewTaskCreated;
use App\Services\EmailTemplateService;
use Exception;

class NewTaskListener
{
    public function handle(NewTaskCreated $event)
    {
         if(isEmailTemplateEnabled('New Task', createdBy()) && !IsDemo()){

        try {


            // Check if New Task email template is active for current user
            $emailService = new EmailTemplateService();

            $task = $event->task;

            if (!$task) {
                return;
            }

            // Load relationships
            $task->load(['creator', 'case', 'taskType', 'assignedUser']);

            // Get assigned user
            $assignedUser = $task->assignedUser;

            if (!$assignedUser || !$assignedUser->email) {
                return;
            }

            $creator = $task->creator;
            $case = $task->case;
            $taskType = $task->taskType;

            $variables = [
                '{user_name}' => $creator && $creator->name ? $creator->name : 'System Administrator',
                '{assigned_to}' => $assignedUser->name ?? 'Assigned User',
                '{title}' => $task->title ?? 'Task Title',
                '{priority}' => ucfirst($task->priority ?? 'medium'),
                '{due_date}' => $task->due_date ? $task->due_date->format('F j, Y') : 'Not specified',
                '{case}' => $case ? $case->title : 'General Task',
                '{task_type}' => $taskType ? $taskType->name : 'General',
                '{app_name}' => config('app.name', 'Legal Management System'),
            ];

            // Get language from currently logged-in user
            $userLanguage = auth()->user()->lang ?? 'en';

            $emailService->sendTemplateEmailWithLanguage(
                'New Task',
                $variables,
                (string) $assignedUser->email,
                (string) $assignedUser->name,
                $userLanguage
            );
        } catch (Exception $e) {
            return back()->withErrors(['error' => __($e->getMessage())]);
        }
    }
}
}
