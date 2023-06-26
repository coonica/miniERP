<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all();
//        dd($clients);
        return view('clients', ['clients' => $clients]);
    }
}
