<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TenantDashboardController extends Controller
{
    public function index()
    {
        return view('tenant.dashboard');
    }
}