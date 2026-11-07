<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodRequest;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the administrator area.
     */
    public function index(): View
    {
        FoodRequest::expireOverdue();

        return view('admin.dashboard', [
            'pendingVerifications' => User::query()
                ->pendingVerification()
                ->count(),
            'openRequestsCount' => FoodRequest::query()
                ->openBoard()
                ->count(),
        ]);
    }
}
