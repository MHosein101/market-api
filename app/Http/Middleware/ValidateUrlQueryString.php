<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Helpers\DataHelper;

/**
 * Validate request query string parameters
 * 
 * @author Hosein marzban
 */
class ValidateUrlQueryString
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
        $v = DataHelper::validate( response() , $request->query() , 
        [
            'state' => [ 'state', 'nullable|in:all,active,trashed,pending,accepted,rejected,sending,finished,canceled,true,false' ] ,
            'page'  => [ 'page', 'nullable|numeric|between:1,9999' ] ,
            'limit' => [ 'limit', 'nullable|numeric|between:1,50' ] ,
            'order' => [ 'order', 'nullable|in:asc,desc' ] ,
            
            'priority' => [ 'priority', 'nullable|in:asc,desc' ] ,

            'perPage' => [ 'perPage', 'nullable|numeric|between:1,50' ] ,

            'id'      => [ 'id', 'nullable|numeric|between:1,2140000000' ] ,

            'full_name'     => [ 'full_name', 'nullable|max:50' ] ,
            'national_code' => [ 'national_code', 'nullable|numeric|digits_between:1,10' ] ,
            'number'        => [ 'number', 'nullable|numeric|digits_between:1,13' ] ,

            'name'    => [ 'name', 'nullable|max:50' ] ,
            'company' => [ 'company', 'nullable|max:50' ] ,

            'title'       => [ 'title', 'nullable|max:100' ] ,
            'barcode'     => [ 'barcode', 'nullable|numeric' ] ,
            'brand_id'    => [ 'brand_id', 'nullable|numeric|between:1,2140000000' ] ,
            'category_id' => [ 'category_id', 'nullable|numeric|between:1,2140000000' ] ,

            'economic_code' => [ 'economic_code', 'nullable|numeric|between:1,2140000000' ] ,
            'province'      => [ 'province', 'nullable|max:50' ] ,
            'city'          => [ 'city', 'nullable|max:50' ] ,

            'q'          => [ 'q', 'nullable|max:50' ] ,
            'brand'      => [ 'brand', 'nullable|max:50' ] ,
            'category'   => [ 'category', 'nullable|max:50' ] ,
            'fromPrice'  => [ 'fromPrice', 'nullable|numeric|between:0,2140000000' ] ,
            'toPrice'    => [ 'toPrice', 'nullable|numeric|between:0,2140000000' ] ,
            'price_from' => [ 'price_from', 'nullable|numeric|between:0,2140000000' ] ,
            'price_to'   => [ 'price_to', 'nullable|numeric|between:0,2140000000' ] ,
            'available'  => [ 'available', 'nullable|in:0,1,true,false' ] ,
            'sort'       => [ 'sort', 'nullable|in:time_desc,price_min,price_max,dateRecent,priceMin,priceMax' ] ,
        ]);

        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        return $next($request);
    }
}
