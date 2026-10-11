<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Tampilkan halaman dasbor/beranda admin.
     */
    public function index(): View
    {
        return view('admin.beranda');
    }
}
