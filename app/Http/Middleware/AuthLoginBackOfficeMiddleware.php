<?php

namespace App\Http\Middleware;

use Closure;
use App\BackOfficeUserTokens;
use App\Events\BackOfficeUserLogEvent;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthLoginBackOfficeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $check =  BackOfficeUserTokens::where('token', $token)->first();

            if (!$check) {
                return response()->json(['statusCode' => '601','message' => 'Token tidak sesuai']);
            } else {
                $email = $check != '' ? $check->user_back_office->emailUser : '';
                if ($email != '') {
                    try 
                    {
                        $token = JWTAuth::getToken();
                        $a = JWTAuth::getPayload($token)->toArray();
                    } catch (TokenExpiredException $e) {
                        event(new BackOfficeUserLogEvent($email,'Cek Token','Cek Token - '.$email,'Token Expired'));
                        $check->delete();
                        \Sentry\captureException($e);
                        return json_encode(array('statusCode' => '555', 'message' => "Token Expired"));
                    } catch (TokenInvalidException $e) {
                        event(new BackOfficeUserLogEvent($email,'Cek Token','Cek Token - '.$email,'Invalid Token'));
                        \Sentry\captureException($e);
                        return json_encode(array('statusCode' => '666', 'message' => "Invalid Token"));
                    } catch (JWTException $e) {
                        event(new BackOfficeUserLogEvent($email,'Cek Token','Cek Token - '.$email,'Token Absent'));
                        \Sentry\captureException($e);
                        return json_encode(array('statusCode' => '777', 'message' => "Token Absent"));
                    }
                    // return json_encode(array('statusCode' => '000', 'message' => "Token Valid", 'token' => JWTAuth::getToken()));
                    return $next($request);
                }
                else {
                    return json_encode(array('statusCode' => '888', 'message' => "Token Kosong"));
                }
                // return $next($request);
            }
        } else {
            return response()->json(['statusCode' => '401','message' => 'Silahkan Masukkan Token.']);
        }
    }
}
