<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateLang;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTemplate::with('notificationTemplateLangs');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('type', 'like', '%' . $request->search . '%');
        }

        $type = $request->get('type', 'slack');
        $query->where('type', $type);

        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $templates = $query->paginate($perPage);

        return Inertia::render('notification-templates/index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page', 'type'])
        ]);
    }

    public function show(NotificationTemplate $notificationTemplate)
    {
        // Load company-specific content
        $template = $notificationTemplate->load(['notificationTemplateLangs' => function ($query) {
            $query->where('created_by', createdBy());
        }]);
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);

        $variables = $this->getVariablesByNameAndType($template->name, $template->type);

        return Inertia::render('notification-templates/show', [
            'template' => $template,
            'languages' => $languages,
            'variables' => $variables
        ]);
    }

    private function getVariablesByNameAndType($name, $type)
    {
        $key = $name . '_' . $type;

        switch ($key) {
            case 'New Case_slack':
                return [
                    '{case_number}' => 'Case Number',
                    '{client_name}' => 'Client Name',
                    '{case_type}' => 'Case Type',
                    '{created_by}' => 'Created By User',
                    '{channel}' => 'Slack Channel'
                ];
            case 'New Case_twilio':
                return [
                    '{case_number}' => 'Case Number',
                    '{case_type}' => 'Case Type',
                    '{created_by}' => 'Created By User',

                ];
            case 'New Court_slack':
                return [
                    '{court_name}' => 'Court Name',
                    '{court_type}' => 'Court Type',
                    '{location}' => 'Court Location'
                ];
            case 'New Court_twilio':
                return [
                    '{court_name}' => 'Court Name',
                    '{location}' => 'Court Location'
                ];
            case 'Invoice Sent_slack':
                return [
                    '{invoice_number}' => 'Invoice Number',
                    '{client_name}' => 'Client Name',
                    '{amount}' => 'Invoice Amount',
                    '{sent_date}' => 'Date Sent'
                ];
            case 'Invoice Sent_twilio':
                return [
                    '{invoice_number}' => 'Invoice Number',
                    '{due_date}' => 'Due Sent'
                ];
            case 'New Client_slack':
                return [
                    '{client_name}' => 'Client Name',
                    '{client_type}' => 'Client Type',
                    '{email}' => 'Client Email',
                    '{created_by}' => 'Created By User'
                ];
            case 'New Client_twilio':
                return [
                    '{client_name}' => 'Client Name',
                    '{client_type}' => 'Client Type',

                ];

            default:
                return $this->getDefaultVariablesByName($name);
        }
    }

    private function getDefaultVariablesByName($name)
    {
        switch ($name) {
            case 'New Case':
                return [
                    '{case_number}' => 'Case Number',
                    '{client_name}' => 'Client Name',
                    '{case_type}' => 'Case Type',
                    '{created_by}' => 'Created By User'
                ];
            case 'New Client':
                return [
                    '{client_name}' => 'Client Name',
                    '{client_type}' => 'Client Type',
                    '{email}' => 'Client Email',
                    '{created_by}' => 'Created By User'
                ];
            case 'New Task':
                return [
                    '{task_title}' => 'Task Title',
                    '{priority}' => 'Task Priority',
                    '{due_date}' => 'Task Due Date',
                    '{assigned_to}' => 'Assigned To User'
                ];
            case 'New Hearing':
                return [
                    '{case_number}' => 'Case Number',
                    '{hearing_date}' => 'Hearing Date',
                    '{court}' => 'Court Name',
                    '{judge}' => 'Judge Name'
                ];
            case 'New Invoice':
                return [
                    '{invoice_number}' => 'Invoice Number',
                    '{client_name}' => 'Client Name',
                    '{amount}' => 'Invoice Amount',
                    '{due_date}' => 'Invoice Due Date'
                ];
            case 'Invoice Sent':
                return [
                    '{invoice_number}' => 'Invoice Number',
                    '{client_name}' => 'Client Name',
                    '{amount}' => 'Invoice Amount',
                    '{sent_date}' => 'Date Sent'
                ];
            case 'New Court':
                return [
                    '{court_name}' => 'Court Name',
                    '{court_type}' => 'Court Type',
                    '{location}' => 'Court Location'
                ];
            case 'New Judge':
                return [
                    '{judge_name}' => 'Judge Name',
                    '{court}' => 'Court Name',
                    '{specialization}' => 'Judge Specialization'
                ];
            case 'New License':
                return [
                    '{license_number}' => 'License Number',
                    '{license_type}' => 'License Type',
                    '{issuing_authority}' => 'Issuing Authority',
                    '{expiry_date}' => 'Expiry Date'
                ];
            case 'New Regulatory Body':
                return [
                    '{body_name}' => 'Regulatory Body Name',
                    '{jurisdiction}' => 'Jurisdiction',
                    '{contact_info}' => 'Contact Information'
                ];
            case 'New CLE Record':
                return [
                    '{course_title}' => 'Course Title',
                    '{credits_earned}' => 'Credits Earned',
                    '{completion_date}' => 'Completion Date',
                    '{provider}' => 'Course Provider'
                ];
            case 'Team Member Created':
                return [
                    '{member_name}' => 'Team Member Name',
                    '{email}' => 'Team Member Email',
                    '{role}' => 'Team Member Role'
                ];
            default:
                return [];
        }
    }

    public function updateSettings(NotificationTemplate $notificationTemplate, Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string|max:255'
            ]);

            $notificationTemplate->update([
                'type' => $request->type
            ]);

            return redirect()->back()->with('success', __('Template settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update template settings: :error', ['error' => $e->getMessage()]));
        }
    }

    public function updateContent(NotificationTemplate $notificationTemplate, Request $request)
    {
        try {
            $request->validate([
                'lang' => 'required|string|max:10',
                'title' => 'required|string|max:255',
                'content' => 'required|string'
            ]);

            $notificationTemplate->notificationTemplateLangs()
                ->where('lang', $request->lang)
                ->where('created_by', createdBy())
                ->update([
                    'title' => $request->title,
                    'content' => $request->content
                ]);

            return redirect()->back()->with('success', __('Notification content updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update notification content: :error', ['error' => $e->getMessage()]));
        }
    }
}
