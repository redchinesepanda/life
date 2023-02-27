<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogEntryController extends Controller
{
    /**
     * new LogEntry
     *
     * @return json
     */
    public function new(Request $request)
    {
        $input = $request->only( ['ip', 'url'] );

        $validator = Validator::make( $input, [
            'ip' => 'required|ip',

            'url' => 'required|url'
        ] );

        if ( $validator->fails() ) {
            return response()->json( [
                'success' => false,

                'message' => 'Change request data as described below.',

                'errors' => $validator->errors()
            ] );
        }

        $logEntry = new LogEntry;
 
        $logEntry->ip = $input['ip'];

        $logEntry->url = $input['url'];
 
        $logEntry->save();

        return response()->json( [
            'success' => true,

            'message' => 'LogEntry added succesfully.',

            'data' => $logEntry
        ], 200 );
    }
}
