<?php

namespace App\Http\Controllers;

use App\Http\Helpers\DataHelper;
use Illuminate\Http\Request;
use App\Http\Helpers\SearchHelper;
use App\Models\Factor;
use App\Models\FactorState;

class StoreFactorController extends Controller
{
    
    /**
     * Return all store's factors with filter
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            Factor::class , 
            '*' , 
            [
                'state' => null
            ] , 
            'filterFactors'
        );

        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No factor found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'stores'     => $data
            ]
            , 200);
    }

    /**
     * Return factor's details
     * 
     * @param Request $request
     * @param int $factorId
     * @param string $state
     * 
     * @return Response
     */ 
    public function changeFactorState(Request $request, $factorId, $state)
    {
        $updateData = [];

        $isComment = false;

        switch($state)
        {
            case 'accept':

                $updateData = [ 'state' => FactorState::Accepted ];
                break;

            case 'reject':

                $updateData = [ 'state' => FactorState::Rejected ];
                $isComment = true;
                break;
                    
            case 'sending':

                $updateData = [ 'state' => FactorState::Sending ];
                break;
                
            case 'finished':

                $updateData = [ 'state' => FactorState::Finished ];
                break;
        }
        
        if($isComment)
        {
            $updateData['store_note'] = $request->input('comment') ?? '';
        }
        
        Factor::where('id', $factorId)->update($updateData);

        return
            response()
            ->json(
            [ 
                'status'     => 200 ,
                'message'    => 'OK'
            ]
            , 200);
    }


}
