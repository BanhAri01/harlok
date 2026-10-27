<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\Region;
use App\Services\BoardData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardController extends Controller
{
    public function index(Request $request, BoardData $board): View
    {
        return view('board', [
            'commodities' => Commodity::active(),
            'commodity' => $board->commodity($request->query('komoditas')),
            'stats' => $board->stats(),
            'waNumber' => config('lapor.whatsapp.device'),
        ]);
    }

    public function data(Request $request, BoardData $board): JsonResponse
    {
        return response()->json($board->payload($board->commodity($request->query('komoditas'))));
    }

    public function apiDocs(): View
    {
        return view('api-docs', ['commodities' => Commodity::active(), 'regions' => Region::ordered()]);
    }
}
