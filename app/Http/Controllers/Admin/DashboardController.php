<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the administrator area.
     */
    public function index(): View
    {
        return view('admin.dashboard');
    }
}
