<?php

namespace App\Http\Controllers;

use App\Http\Services\AdminService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends Controller
{
    public function __construct(protected AdminService $service)
    {
        //
    }

    /*
     * List All Aggregated Data */
    public function admin(): array
    {
        return $this->service->admin();
    }

    /*
     * List All Users */
    public function users(): AnonymousResourceCollection
    {
        return $this->service->users();
    }

    /*
     * List All Videos */
    public function videos(): AnonymousResourceCollection
    {
        return $this->service->videos();
    }

    /*
     * List All Audios */
    public function audios(): AnonymousResourceCollection
    {
        return $this->service->audios();
    }

    /*
     * List All Kopokopo Recipients */
    public function kopokopoRecipients(): array
    {
        return $this->service->kopokopoRecipients();
    }

    /*
     * List All Bought Song Payouts */
    public function songPayouts(): array
    {
        return $this->service->songPayouts();
    }
}
