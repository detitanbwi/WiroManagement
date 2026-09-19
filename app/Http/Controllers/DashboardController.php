<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $totalClients = Client::count();
        $projectQuery = Project::visibleTo($user);
        $activeProjects = (clone $projectQuery)->where('status', 'in_progress')->count();
        $totalContractValue = Invoice::sum('total_amount');
        $totalPaid = Payment::sum('amount');
        
        $recentProjects = (clone $projectQuery)->with('client')->latest()->take(5)->get();
        $unpaidInvoices = Invoice::with(['project.client'])->where('status', '!=', 'paid')->latest()->take(5)->get();

        return view('dashboard', compact(
            'totalClients', 
            'activeProjects', 
            'totalContractValue', 
            'totalPaid',
            'recentProjects',
            'unpaidInvoices'
        ));
    }
}
