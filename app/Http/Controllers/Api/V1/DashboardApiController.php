<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    /**
     * Return summary metrics and recent items for the mobile quick action dashboard.
     */
    public function summary(Request $request): JsonResponse
    {
        $totalClients = Client::count();
        $activeProjects = Project::whereIn('status', ['in_progress', 'approved', 'draft'])->count();
        
        $unpaidInvoices = Invoice::where('status', 'issued')->count();
        $unpaidInvoicesAmount = (float) Invoice::where('status', 'issued')->sum('total_amount');

        $recentProjects = Project::with('client:id,name,company_name')
            ->latest()
            ->take(5)
            ->get(['id', 'client_id', 'title', 'status', 'progress_percentage', 'created_at']);

        $recentInvoices = Invoice::with('project:id,title')
            ->latest('issued_date')
            ->take(5)
            ->get(['id', 'project_id', 'invoice_number', 'total_amount', 'status', 'due_date', 'issued_date']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'counts' => [
                    'clients' => $totalClients,
                    'active_projects' => $activeProjects,
                    'unpaid_invoices' => $unpaidInvoices,
                    'unpaid_invoices_amount' => $unpaidInvoicesAmount,
                ],
                'recent_projects' => $recentProjects,
                'recent_invoices' => $recentInvoices,
            ],
        ]);
    }
}

