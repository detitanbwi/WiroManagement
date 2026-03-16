<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalClients = Client::count();
        $activeProjects = Project::where('status', 'in_progress')->count();
        $totalContractValue = Project::whereNotIn('status', ['cancelled', 'draft'])->get()->sum('grand_total');
        $totalPaid = Project::get()->sum('paid_amount');
        
        $recentProjects = Project::with('client')->latest()->take(5)->get();
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
