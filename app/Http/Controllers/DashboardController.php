<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the donor dashboard.
     */
    public function donor(): View
    {
        return view('dashboard');
    }

    /**
     * Show the recipient dashboard.
     */
    public function recipient(): View
    {
        return view('dashboard');
    }
}
