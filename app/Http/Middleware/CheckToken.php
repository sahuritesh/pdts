<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\Common_model;
class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $role
     * @return mixed
     */
    public function handle($request, Closure $next){
        if(isset($request->header()['authorization'])){
            $bearer = explode(" ",$request->header()['authorization'][0]);
            $checkBearer = Common_model::countResult('users',$field='token',$value=$bearer[1], $limit=0,$groupBy = '');
            if($checkBearer == 1){
                return $next($request);
            }else{
                return response()->json(['status'=>401,'statusCode'=>'error','data' => 'Invalid authorization']);    
            }
        }else{
            return response()->json(['status'=>401,'statusCode'=>'error','data' => 'Invalid authorization']);
        }
    }
}
?>