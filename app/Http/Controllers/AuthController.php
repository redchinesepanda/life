<?php
 
namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
 
class AuthController extends Controller
{
    /**
     * post guest register
     *
     * @return json
     */
    public function register( Request $request )
    {
        $input = $request->only( ['name', 'email', 'password'] );

        $validator = Validator::make(
            $input,
            [
                'name' => 'required|string|min:4',

                'email' => 'required|email',

                'password' => 'required|min:8',
            ]
        );

        if ($validator->fails()) {
            return response()->json( [
                'success' => false,

                'message' => 'Please see errors parameter for all errors.',

                'errors' => $validator->errors()
            ] );
        }

        $user = User::create( [
            'name' => $input['name'],

            'email' => $input['email'],

            'password' => Hash::make( $input['password'] )
        ] );
         
        return response()->json( [
            'success' => true,

            'message' => 'User registered succesfully, Use login method to receive token.'
        ], 200 );
    }

    /**
     * post guest login
     *
     * @return json
     */
    public function login( Request $request )
    {
        $input = $request->only(['email', 'password']);

        $validator = Validator::make(
            $input,
            [
                'email' => 'required|email',
    
                'password' => 'required|min:8',
            ]
        );
        
        if ( $validator->fails() ) {
            return response()->json( [
                'success' => false,

                'message' => 'Please see errors parameter for all errors.',

                'errors' => $validator->errors()
            ] );
        }

        if ( auth()->attempt( $input ) ) {
            $token = auth()->user()->createToken('auth_token')->plainTextToken;
            
            return response()->json( [
                'success' => true,

                'message' => 'User login succesfully, Use type and token to authenticate.',

                'type' => 'Bearer',

                'token' => $token
            ], 200 );
        } else {
            return response()->json( [
                'success' => false,

                'message' => 'User authentication failed.'
            ], 401 );
        }
    }
}