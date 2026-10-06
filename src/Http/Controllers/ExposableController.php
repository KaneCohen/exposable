<?php

namespace ArjanWestdorp\Exposable\Http\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ExposableController extends Controller
{
    /**
     * Expose the given exposable.
     *
     * @param string $exposable
     * @param int $id
     * @return Response
     */
    public function show($exposable, $id)
    {
        $exposable = Arr::get(config('exposable.exposables'), $exposable);

        abort_if(is_null($exposable), 404);

        return app($exposable)->findOrFail($id)->expose();
    }
}
