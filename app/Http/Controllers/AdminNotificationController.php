<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\AdminNotification;
use App\Http\Helpers\SearchHelper;

class AdminNotificationController extends Controller
{
    /**
     * Return all admin notifications
     * 
     * @see SearchHelper::dataWithFilters()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getList(Request $request)
    {
        $stores = Store::selectRaw('id as store_id, name as store');

        $notifs = AdminNotification::selectRaw('admin_notifications.*')
        
        ->leftJoinSub($stores, 'n_stores', function ($join) 
        {
            $join->on('admin_notifications.store_id', 'n_stores.store_id');
        })
        
        ->orderBy('is_new', 'desc');

        unset($request['state']);

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            clone $notifs , 
            null , 
            [
                'store' => null ,
                'state' => 'active' ,
            ] , 
            'filterAdminNotifications'
        );

        extract($result);

        foreach($data as $nf)
        {
            if( $nf->is_new )
            {
                AdminNotification::where('id', $nf->id)->update(['is_new' => false]);
            }
        }

        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'        => $status ,
                'message'       => $status == 200 ? 'OK' : 'No notifications found.' ,
                'count'         => $count ,
                'pagination'    => $pagination ,
                'notifications' => $data
            ], 200);
    }

    /**
     * Delete all or selected notifications
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function deleteNotifications(Request $request)
    {
        $items = $request->input('items') ?? [] ;

        foreach($items as $id)
        {
            AdminNotification::where('id', (int)$id)->delete();
        }
        
        return $this->getList($request);
    }


}
