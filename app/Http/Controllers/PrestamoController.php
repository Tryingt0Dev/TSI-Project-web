<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrestamoController extends Controller
{
    public function index(){
    return view('prestamos.index');
    }
}