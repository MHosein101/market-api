<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PublicProduct;

class PublicProductValidate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $productSlug = $request->route('productSlug');

        $product = PublicProduct::where('slug', $productSlug)->first();
        
        if($product == null)
        {
            return 
                response()
                ->json(
                [ 
                    'status'  => 404 ,
                    'message' => 'Product not found.' 
                ], 404);
        }
        
        $request->merge(compact('product'));

        return $next($request);
    }
}
