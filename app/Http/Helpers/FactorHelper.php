<?php

namespace App\Http\Helpers;

use App\Models\FactorState;

/**
 * Helper methods for factors
 * 
 * @author Hosein marzban
 */ 
class FactorHelper
{

    /**
     * Change state of factor or factor items
     *
     * @param Model $class
     * @param int $id
     * @param string $state
     * 
     * @return boolean
     */ 
    public static function changeState($class, $id, $state)
    {
        $lastState = $class::find($id)->state;

        $allowChange = false;

        $isComment = false;

        $newState = null;

        switch($state)
        {
            case 'accept':

                if( $lastState == FactorState::Pending )
                {
                    $newState = FactorState::Accepted;
                    $isComment = true;
                    $allowChange = true;
                }
                break;

            case 'reject':

                if( $lastState == FactorState::Pending )
                {
                    $newState = FactorState::Rejected;
                    $isComment = true;
                    $allowChange = true;
                }
                break;
                    
            case 'sending':

                if( $lastState == FactorState::Accepted )
                {
                    $newState = FactorState::Sending;
                    $allowChange = true;
                }
                break;
                
            case 'finished':

                if( $lastState == FactorState::Sending )
                {
                    $newState = FactorState::Finished;
                    $allowChange = true;
                }
                break;
        }

        if($allowChange)
        {
            $updateData = 
            [ 
                'state'      => $newState ,
                'store_note' => $isComment ? request()->input('comment') ?? '' : ''
            ];

            $class::where('id', $id)->update($updateData);
        }

        return $allowChange;
    }
    
}