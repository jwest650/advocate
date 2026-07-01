<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $plan = $user->getCurrentPlan();
        $year = $request->get('year', date('Y'));

        return Inertia::render('analytics/index', [
            'kpiMetrics' => $this->getKPIMetrics(),
            'dashboardWidgets' => $this->getDashboardWidgets($year),
            'financialReports' => $this->getFinancialReports(),
            'revenueAnalytics' => $this->getRevenueAnalytics(),
            'caseAnalytics' => $this->getCaseAnalytics(),
            'customReports' => $this->getCustomReports(),
            'is_demo' => IsDemo(),
            'planInfo' => [
                'name' => $plan ? $plan->name : 'Free Plan',
                'storage_limit' => $plan ? $plan->storage_limit : 5,
                'max_users' => $plan ? $plan->max_users : 5
            ]
        ]);
    }



    private function getKPIMetrics()
    {
        $companyId = createdBy();

        $activeCases = CaseModel::where('created_by', $companyId)
            ->count();

        $activeClients = Client::where('created_by', $companyId)
            ->count();

        $totalRevenue = Invoice::where('created_by', $companyId)
            ->where('status', 'paid')
            ->sum('total_amount');

        $pendingTasks = Task::where('created_by', $companyId)
            ->count();

        $caseSuccessRate = $this->calculateCaseSuccessRate($companyId);
        $avgResolutionTime = $this->calculateAvgResolutionTime($companyId);

        return [
            'activeCases' => $activeCases,
            'activeClients' => $activeClients,
            'totalRevenue' => $totalRevenue,
            'pendingTasks' => $pendingTasks,
            'caseSuccessRate' => $caseSuccessRate,
            'avgResolutionTime' => $avgResolutionTime,
            'collectionRate' => $this->calculateCollectionRate($companyId),
            'billableHours' => $this->calculateBillableHours($companyId)
        ];
    }

    private function getDashboardWidgets($year = null)
    {
        $companyId = createdBy();
        $year = $year ?: date('Y');

        if (IsDemo()) {
            $tasksByMonth = [
                ['month' => 'Jan', 'critical' => 8, 'high' => 15, 'medium' => 22],
                ['month' => 'Feb', 'critical' => 6, 'high' => 18, 'medium' => 25],
                ['month' => 'Mar', 'critical' => 10, 'high' => 20, 'medium' => 28],
                ['month' => 'Apr', 'critical' => 7, 'high' => 16, 'medium' => 24],
                ['month' => 'May', 'critical' => 9, 'high' => 19, 'medium' => 26],
                ['month' => 'Jun', 'critical' => 12, 'high' => 22, 'medium' => 30],
                ['month' => 'Jul', 'critical' => 11, 'high' => 21, 'medium' => 29],
                ['month' => 'Aug', 'critical' => 8, 'high' => 17, 'medium' => 25],
                ['month' => 'Sep', 'critical' => 13, 'high' => 24, 'medium' => 32],
                ['month' => 'Oct', 'critical' => 10, 'high' => 20, 'medium' => 28],
                ['month' => 'Nov', 'critical' => 7, 'high' => 16, 'medium' => 23],
                ['month' => 'Dec', 'critical' => 9, 'high' => 18, 'medium' => 26]
            ];
            
            $recentCases = [
                (object)['title' => 'Smith vs. Johnson Corp', 'client' => (object)['name' => 'John Smith'], 'status' => 'active'],
                (object)['title' => 'Personal Injury Case #2024-001', 'client' => (object)['name' => 'Sarah Wilson'], 'status' => 'pending'],
                (object)['title' => 'Divorce Settlement', 'client' => (object)['name' => 'Michael Brown'], 'status' => 'active'],
                (object)['title' => 'Contract Dispute Resolution', 'client' => (object)['name' => 'Tech Solutions Inc'], 'status' => 'on_hold'],
                (object)['title' => 'Criminal Defense Case', 'client' => (object)['name' => 'David Miller'], 'status' => 'active']
            ];
             $overdueInvoices = Invoice::where('created_by', $companyId)
                ->where('status', 'overdue')
                ->with('client')
                ->take(5)
                ->get();
        } else {
            $tasksByMonth = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthData = [
                    'month' => Carbon::create()->month($month)->format('M'),
                    'critical' => 0,
                    'high' => 0,
                    'medium' => 0
                ];
                
                $tasks = Task::where('created_by', $companyId)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->get();
                    
                foreach ($tasks as $task) {
                    $priority = strtolower($task->priority);
                    if (isset($monthData[$priority])) {
                        $monthData[$priority] = $task->count;
                    }
                }
                
                $tasksByMonth[] = $monthData;
            }
            
            $recentCases = CaseModel::where('created_by', $companyId)
                ->with(['client', 'caseStatus'])
                ->latest()
                ->take(5)
                ->get();
                
            $overdueInvoices = Invoice::where('created_by', $companyId)
                ->where('status', 'overdue')
                ->with('client')
                ->take(5)
                ->get();
        }

        return [
            'recentCases' => $recentCases,
            'overdueInvoices' => $overdueInvoices,
            'tasksByPriority' => $tasksByMonth
        ];
    }

    private function getFinancialReports()
    {
        $companyId = createdBy();

        if (IsDemo()) {
            $yearlyRevenue = [
                ['year' => 2024, 'month' => 1, 'month_name' => 'Jan', 'revenue' => 235000],
                ['year' => 2024, 'month' => 2, 'month_name' => 'Feb', 'revenue' => 248000],
                ['year' => 2024, 'month' => 3, 'month_name' => 'Mar', 'revenue' => 265000],
                ['year' => 2024, 'month' => 4, 'month_name' => 'Apr', 'revenue' => 258000],
                ['year' => 2024, 'month' => 5, 'month_name' => 'May', 'revenue' => 275000],
                ['year' => 2024, 'month' => 6, 'month_name' => 'Jun', 'revenue' => 285000],
                ['year' => 2024, 'month' => 7, 'month_name' => 'Jul', 'revenue' => 295000],
                ['year' => 2024, 'month' => 8, 'month_name' => 'Aug', 'revenue' => 305000],
                ['year' => 2024, 'month' => 9, 'month_name' => 'Sep', 'revenue' => 315000],
                ['year' => 2024, 'month' => 10, 'month_name' => 'Oct', 'revenue' => 325000],
                ['year' => 2024, 'month' => 11, 'month_name' => 'Nov', 'revenue' => 335000],
                ['year' => 2024, 'month' => 12, 'month_name' => 'Dec', 'revenue' => 350000]
            ];
            $outstandingAmount = 125000;
        } else {
            $yearlyRevenue = [];
            for ($year = date('Y'); $year <= date('Y'); $year++) {
                for ($month = 1; $month <= 12; $month++) {
                    $revenue = Invoice::where('created_by', $companyId)
                        ->where('status', 'paid')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->sum('total_amount');
                    
                    $yearlyRevenue[] = [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => Carbon::create()->month($month)->format('M'),
                        'revenue' => $revenue ?: 0
                    ];
                }
            }
            $outstandingAmount = Invoice::where('created_by', $companyId)
                ->whereIn('status', ['sent', 'overdue'])
                ->sum('total_amount');
        }

        return [
            'yearlyRevenue' => $yearlyRevenue,
            'outstandingAmount' => $outstandingAmount
        ];
    }

    private function getRevenueAnalytics()
    {
        $companyId = createdBy();

        return [];
    }

    private function getCaseAnalytics()
    {
        $companyId = createdBy();

        if (IsDemo()) {
            $casesByYear = [
                ['year' => 2024, 'month' => 1, 'month_name' => 'Jan', 'critical' => 20, 'high' => 36, 'medium' => 44, 'low' => 25],
                ['year' => 2024, 'month' => 2, 'month_name' => 'Feb', 'critical' => 21, 'high' => 38, 'medium' => 46, 'low' => 27],
                ['year' => 2024, 'month' => 3, 'month_name' => 'Mar', 'critical' => 23, 'high' => 41, 'medium' => 49, 'low' => 29],
                ['year' => 2024, 'month' => 4, 'month_name' => 'Apr', 'critical' => 19, 'high' => 37, 'medium' => 45, 'low' => 26],
                ['year' => 2024, 'month' => 5, 'month_name' => 'May', 'critical' => 22, 'high' => 40, 'medium' => 48, 'low' => 28],
                ['year' => 2024, 'month' => 6, 'month_name' => 'Jun', 'critical' => 24, 'high' => 43, 'medium' => 51, 'low' => 30],
                ['year' => 2024, 'month' => 7, 'month_name' => 'Jul', 'critical' => 25, 'high' => 45, 'medium' => 53, 'low' => 31],
                ['year' => 2024, 'month' => 8, 'month_name' => 'Aug', 'critical' => 23, 'high' => 42, 'medium' => 50, 'low' => 29],
                ['year' => 2024, 'month' => 9, 'month_name' => 'Sep', 'critical' => 26, 'high' => 46, 'medium' => 54, 'low' => 32],
                ['year' => 2024, 'month' => 10, 'month_name' => 'Oct', 'critical' => 24, 'high' => 44, 'medium' => 52, 'low' => 30],
                ['year' => 2024, 'month' => 11, 'month_name' => 'Nov', 'critical' => 22, 'high' => 41, 'medium' => 49, 'low' => 28],
                ['year' => 2024, 'month' => 12, 'month_name' => 'Dec', 'critical' => 21, 'high' => 39, 'medium' => 47, 'low' => 27]
            ];
            
            $casesByType = [
                (object)['case_type_id' => 1, 'count' => 145, 'caseType' => (object)['name' => 'Criminal Defense']],
                (object)['case_type_id' => 2, 'count' => 98, 'caseType' => (object)['name' => 'Personal Injury']],
                (object)['case_type_id' => 3, 'count' => 76, 'caseType' => (object)['name' => 'Family Law']],
                (object)['case_type_id' => 4, 'count' => 54, 'caseType' => (object)['name' => 'Corporate Law']],
                (object)['case_type_id' => 5, 'count' => 32, 'caseType' => (object)['name' => 'Immigration']]
            ];
        } else {
            $casesByYear = [];
            for ($year = date('Y'); $year <= date('Y'); $year++) {
                for ($month = 1; $month <= 12; $month++) {
                    $monthData = [
                        'year' => $year,
                        'month' => $month,
                        'month_name' => Carbon::create()->month($month)->format('M'),
                        'critical' => 0,
                        'high' => 0,
                        'medium' => 0,
                        'low' => 0
                    ];
                    
                    $cases = CaseModel::where('created_by', $companyId)
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->selectRaw('priority, COUNT(*) as count')
                        ->groupBy('priority')
                        ->get();
                        
                    foreach ($cases as $case) {
                        $priority = strtolower($case->priority);
                        if (isset($monthData[$priority])) {
                            $monthData[$priority] = $case->count;
                        }
                    }
                    
                    $casesByYear[] = $monthData;
                }
            }
            
            $casesByType = CaseModel::where('created_by', $companyId)
                ->with('caseType')
                ->selectRaw('case_type_id, COUNT(*) as count')
                ->groupBy('case_type_id')
                ->get();
        }

        return [
            'casesByType' => $casesByType,
            'casesByYear' => $casesByYear
        ];
    }

    private function getCustomReports()
    {
        $companyId = createdBy();

        return [];
    }

    // Helper calculation methods
    private function calculateCaseSuccessRate($companyId)
    {
        $totalCases = CaseModel::where('created_by', $companyId)->count();
        $successfulCases = CaseModel::where('created_by', $companyId)
            ->where('status', 'closed')
            ->count();
        return $totalCases > 0 ? round(($successfulCases / $totalCases) * 100, 1) : 0;
    }

    private function calculateAvgResolutionTime($companyId)
    {
        return CaseModel::where('created_by', $companyId)
            ->where('status', 'closed')
            ->whereNotNull('expected_completion_date')
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days') ?? 0;
    }

    private function calculateCollectionRate($companyId)
    {
        $totalInvoiced = Invoice::where('created_by', $companyId)
            ->sum('total_amount');
        $totalPaid = Invoice::where('created_by', $companyId)
            ->where('status', 'paid')
            ->sum('total_amount');
        return $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100, 1) : 0;
    }

    private function calculateBillableHours($companyId)
    {
        // Mock calculation - implement based on time tracking system
        return Task::where('created_by', $companyId)
            ->where('status', 'completed')
            ->sum('estimated_duration') ?? 0;
    }



    private function calculateResourceUtilization($companyId)
    {
        // Mock calculation - implement based on user activity
        return 78.2;
    }
}