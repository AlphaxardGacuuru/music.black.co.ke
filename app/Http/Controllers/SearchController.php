<?php

namespace App\Http\Controllers;

use App\Http\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(protected SearchService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->service->index();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $this->service->store($request);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        [$deleted, $message] = $this->service->destroy($id);

        return response(['message' => $message], 200);
    }
}
