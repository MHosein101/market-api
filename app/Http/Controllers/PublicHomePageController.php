<?php

namespace App\Http\Controllers;

use App\Http\Helpers\PublicHomePageHelper;
use App\Http\Helpers\PublicSearchHelper;
use Illuminate\Http\Request;

class PublicHomePageController extends Controller
{
    
    /**
     * Return first time data for home page
     * 
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function initialData(Request $request)
    {
        $searchBar = PublicSearchHelper::searchBarData($request->user);

        return 
            response()
            ->json(
            [
                'status'     => 200 ,
                'message'    => 'OK' ,
                'search_bar' => $searchBar ,
            ], 200);
    }

}
