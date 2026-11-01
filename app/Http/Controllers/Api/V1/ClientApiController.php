<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientApiController extends Controller
{
    /**
     * Display a listing of clients with optional search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::withCount('projects');

        if ($search = $request->input('search', $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $clients = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $clients,
        ]);
    }

    /**
     * Store a newly created client (Quick Client Creation).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string',
        ]);

        $client = Client::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Klien berhasil ditambahkan.',
            'data' => $client,
        ], 201);
    }

    /**
     * Display the specified client with active projects.
     */
    public function show(Client $client): JsonResponse
    {
        $client->load([
            'projects' => function ($query) {
                $query->select('id', 'client_id', 'title', 'status', 'progress_percentage', 'created_at')
                      ->latest();
            }
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $client,
        ]);
    }
}

