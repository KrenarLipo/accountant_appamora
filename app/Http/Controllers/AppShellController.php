<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AppShellController extends Controller
{
    /**
     * Serves the React application for every /app route.
     */
    public function __invoke(): View
    {
        return view('app');
    }
}
