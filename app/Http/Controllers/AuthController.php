<?php

namespace App\Http\Controllers;

/* Models */
use App\User;
use App\Register;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;


/* Mail support*/
use \Swift_Mailer;
use \Swift_SmtpTransport;
use \Swift_Message;

class AuthController extends Controller
{
    
    function index(Request $request){
        
        if ($request->isJson()) {
            $users = User::all();
            $users = DB::table('users')->paginate(15);
            return response()->json( $users, 200 );
        }
         
        return response()->json(['error','Unauthorized'] , 401 , []);
    }


    function sendRecoverAccount(Request $request){

        if ($request->isJson()) {
        
            $data = $request->json()->all();
            $register = new Register;

            if ($register->userExist($data['email']))
            {
                
                User::where('email', $data['email'])
                    ->update(
                    ['email_token' => str_random(60)]
                );
                

                $user = User::select('id','email','name','lastName','email_token')
                    ->where('email',$data['email'])
                    ->where('verified', 1)
                    ->first();

                    

                    $content = View::make('mail.recover', ['user' => $user]);

                    sendEmail(
                            [
                                'authWith' => 'info@email.com',
                                'setSubject' => 'Recover account',
                                'setFrom' => ['info@email.com', 'Info'],
                                'emailbody' => $content->render(),
                                'to' => $user['email']
                                
                            ]
                        );
            }
            else
            {
                return response()->json(['error','Unauthorized'] , 401 , []);
            }


           


            return response()->json( [] , 201 );
        }
         
        return response()->json(['error','Unauthorized'] , 401 , []);

    }



    function recoverAttemp(Request $request){

        if ($request->isJson()) {
        
            $data = $request->json()->all();
            $register = new Register;

            // Si el usuario existe
            if ($register->userExist($data['email']))
            {   
                // Si las claves son verdaderas
                if ($data['password'] == $data['password2'])
                {
                    
                    $user = User::where('email_token', $data['email_token'])
                        ->where('email', $data['email'])
                        ->get()
                        ->first();

                    if ($user['id'])
                    {   
                        
                        User::where('email', $user['email'])
                            ->where('email_token', $data['email_token'] )
                            ->update(
                                [
                                    'password'  => Hash::make($data['password']),
                                    'email_token' => str_random(60)
                                ]
                        );

                        return response()->json( $user , 201 );
                    }
                    else
                    {
                        return response()->json(['error','The token to recover the account is invalid'] , 401 , []);     
                    }
                }
                else
                {
                                                                                            // Preguntar que numero de error va aqui .-.
                   return response()->json(['error','The confirmation key does not match'] , 401 , []);

                   
                }


                
                   
                    
                    
            }
            else
            {
                return response()->json(['error','Unauthorized'] , 401 , []);
            }


           


            return response()->json( $user , 201 );
        }
         
        return response()->json(['error','Unauthorized'] , 401 , []);

    }




    function registerTalent(Request $request){

        if ($request->isJson()) {
        
            $data = $request->json()->all();
            $register = new Register;

            if (!$register->userExist($data['email']))
                {
                    
                    $user = User::create([
                        'name'      => $data['name'],
                        'lastName'      => $data['lastName'],
                        'email'     => $data['email'],
                        'password'  => Hash::make($data['password']),
                        'api_token' => str_random(60),
                        'email_token' => str_random(60),
                        'primaryPhone' => $data['primaryPhone'],
                        'secondaryPhone' => $data['secondaryPhone'],
                        'securityQuestion' => $data['securityQuestion'],
                        'securityAnswer' => $data['securityAnswer'],
                        'userType' => 2,
                        'verified' => 0,
                    ]);
            }
            else
            {
                return response()->json(['error','User already exists'] , 401 , []);
            }


            $content = View::make('mail.activation', ['user' => $user]);
            
            sendEmail(
                    [
                        'authWith' => 'info@email.com',
                        'setSubject' => 'Account activation',
                        'setFrom' => ['info@email.com', 'Info'],
                        'emailbody' => $content->render(),
                        'to' => $user['email']
                        
                    ]
                );


            return response()->json( $user , 201 );
        }
         
        return response()->json(['error','Unauthorized'] , 401 , []);


        
    }



    function activateAccount(Request $request,$id,$email_token){

        
            try {
                
                    $user = User::where('email_token',$email_token)
                    ->where('verified', 0 )
                    ->find($id);
                    
                    if ($user['email_token'] == $email_token && $user['id'] == $id)
                    {   

                        User::select('id','email','name','lastName','userType','api_token','password','verified')->where('id', $user['id'])
                            ->where('email_token', $email_token )
                            ->update(
                                ['verified' => 1]
                        );



                        return response()->json($user,201);
                    }
                    else{
                       return response()->json(['error' => 'Incorrect token or the account has already been activated', 401]);
                    }
            } catch (Exception $e) {
                
                   return response()->json(['error' => 'Incorrect token or the account has already been activated', 401]);
            }

    }



    function getToken(Request $request){

        if ($request->isJson()) {
            try {
                
                $data = $request->json()->all();
                
              if ($data['email'] && $data['password']) {
                  # code...
                $user = User::select('id','email','name','lastName','userType','api_token','password','verified')
                    ->where('email',$data['email'])
                    ->where('verified', 1)
                    ->first();
                
                if ($user && Hash::check($data['password'], $user['password']))
                {
                    return response()->json($user,200);
                }
                else{

                   return response()->json(['error' => 'Incorrect password or email', 401],401);
                }
              }else{
                return response()->json(['error' => 'Missing parameters to complete the authentication', 401],401);
              }

            } catch (Exception $e) {
                
                   return response()->json(['error' => 'No content', 406]);
            }
        }

    }

    
    function logout(Request $request)
    {

        if ($request->isJson()) {
            try {

               $data = $request->json()->all();
                

               $user =  User::where('id', $data['users_id'])
               ->where('api_token', $request->header('Authorization'))->first();

               // Verificar si el usuario existe
                if ($user['api_token'] == $request->header('Authorization') )
                {   

                    User::where('id',$data['users_id'])
                    ->where('api_token', $request->header('Authorization') )
                    ->update(
                        ['api_token' => str_random(60)]
                    );

                    return response()->json([],200);
                }
                else
                {
                   return response()->json(['error' => 'No content', 406]);
                }
            } catch (Exception $e) {
                
                   return response()->json(['error' => 'No content', 406]);
            }
        }



    }

}
