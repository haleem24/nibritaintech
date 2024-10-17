<?php

namespace App\Http\Controllers;

use App\Models\Api_request_model;
use Illuminate\Http\Request;

class ApiRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if($request->Serial != '' || $request->Imei != '' || $request->Imei2 != ''){

            // Create or update the resource
            $api_request = Api_request_model::firstOrNew([
                'request' => json_encode($request->getContent()),
            ]);
            $api_request->save();
            // Return response
            return response()->json([
                'status' => 'Success',
                'message' => 'Data received',
                'system_reference' => $api_request->id,
            ], 200);
        }else{
            // Return response
            return response()->json([
                'status' => 'Failed',
                'message' => 'Missing IMEI and Serial',
            ], 400);

        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
