<?php

namespace App\Http\Controllers;

use App\Events\AudioCommentedEvent;
use App\Http\Services\AudioCommentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AudioCommentController extends Controller
{
    public function __construct(protected AudioCommentService $service)
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return $this->service->index();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'id' => 'required|exists:audios,id',
            'text' => 'required',
        ]);

        [$canDispatch, $message, $audioComment] = $this->service->store($request);

        AudioCommentedEvent::dispatchIf(
            $canDispatch,
            $audioComment->audio,
            $audioComment->user
        );

        return response([
            'message' => $message,
            'data' => $audioComment,
        ], 200);
    }

    /**
     * Display the comments of the specified audio (paginated).
     */
    public function show(string $id): AnonymousResourceCollection
    {
        return $this->service->show($id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): Response
    {
        [$deleted, $message] = $this->service->destroy($id);

        return response(['message' => $message], 200);
    }
}
