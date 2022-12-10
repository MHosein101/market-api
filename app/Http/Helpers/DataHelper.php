<?php

namespace App\Http\Helpers;

use App\Models\User;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Helper methods to get complex data or modify them
 * 
 * @author Hosein marzban
 */ 
class DataHelper
{
    /**
     * Return request field value or default if is null
     *
     * @param string $key
     * @param mixed $default
     * 
     * @return array
     */ 
    public static function post($key, $default = '')
    {
        $value = request()->post($key);
        return $value == null ? $default : $value;
    }

    /**
     * Validate request data with rules and return error response if fails
     *
     * @param Response $response
     * @param array $data
     * @param array $rulesAndNames
     * 
     * @return array
     */ 
    public static function validate($response, $data, $rulesAndNames)
    {
        $attrNames = [];
        $rules = [];

        foreach($rulesAndNames as $key => $value) {
            $attrNames[$key] = $value[0];
            $rules[$key] = $value[1];
        }

        $validator = Validator::make($data, $rules, [], $attrNames);

        if( $validator->fails() ) {
            return [
                'code' => 400 ,
                'response' => $response
                    ->json([ 
                        'status' => 400 ,
                        'message' => 'Data validation failed.' , 
                        'errors' => $validator->errors()->all() 
                    ], 400)
            ];
        }
        else return [ 'code' => 200 ];
    }
    
    /**
     * Return all categories with their chidren or filter them
     * 
     * @see SearchHelper::configQueryParams(array, array) : array
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * @see DataHelper::categoriesTree(int, string) : Model[]
     * 
     * @param array $queryString
     * 
     * @return Category[]
     */ 
    public static function categories($queryString) {

        $defaultParams = [
            'name' => null ,
            'state' => 'all' , // all active trashed
            'page' => 1 ,
            'limit' => 20 ,
            'order' => 'desc' // asc desc
        ];

        $queryParams = SearchHelper::configQueryParams($queryString, $defaultParams);

        if( $queryParams['name'] != null ) {
            
            return SearchHelper::dataWithFilters(
                $queryString , 
                Category::class , 
                '*' , 
                [ 'name' => null ] , 
                'filterCategories'
            );
        }
        
        $qbuilder = Category::selectRaw('*');

        switch( $queryParams['state'] ) {
            case 'all': 
                $qbuilder = $qbuilder->withTrashed()->where('parent_id', null);
                break;
            case 'active': 
                $qbuilder = $qbuilder->where('parent_id', null);
                break;
            case 'trashed': 
                $qbuilder = $qbuilder->onlyTrashed()
                    ->where(function($qb) {
                        return $qb
                            ->whereNotIn('parent_id', function ($sqb) {
                                $sqb->from('categories')
                                    ->select('id')
                                    ->where('deleted_at', '!=', null);
                            })
                            ->orWhere('parent_id', null);
                    });
                break;
        }

        // $r = DB::select('select * from `categories` where `categories`.`deleted_at` is not null and (`parent_id` not in (select `id` from `categories` where `deleted_at` is not null) or `parent_id` is null)');
        // dd($r);

        $mains = SearchHelper::dataWithFilters($queryString, clone $qbuilder, null, [ 'limit' => 999 ]);
        extract($mains);

        $categories = [];

        foreach($data as $c)
            $categories[] = DataHelper::categoriesTree($c->id, $queryParams['state']);

        return [
            'data' => $categories ,
            'count' => $count ,
            'pagination' => $pagination
        ];
    }

    /**
     * Get children of a parent category
     *
     * @param int $categoryId
     * @param string $state
     * 
     * @return Category[]
     */ 
    public static function categoriesTree($categoryId, $state) {
        $category = null;
        $subsIDs = [];

        $category = Category::withTrashed()->find($categoryId);

        switch($state) {
            case 'all': $subsIDs = Category::withTrashed()->where('parent_id', $categoryId)->get();
                break;
            case 'active': $subsIDs = Category::where('parent_id', $categoryId)->get();
                break;
            case 'trashed': $subsIDs = Category::onlyTrashed()->where('parent_id', $categoryId)->get();
                break;
        }

        if(  count($subsIDs) > 0  ) {
            $subs = [];
            foreach($subsIDs as $s)
                $subs[] = DataHelper::categoriesTree($s->id, $state);

            $category["sub_categories"] = $subs;
        }

        return $category;
    }

    /**
     * Return ids of a category's parents
     * 
     * @param int $categoryId
     * @param boolean $isTrashed
     * 
     * @return array
     */ 
    public static function categoryParentsIds($categoryId, $isTrashed = false) {
        $ids = [];
  
        do {
            $category = Category::selectRaw('id, parent_id');
            
            if($isTrashed)
                $category = $category->withTrashed();
    
            $category = $category->find($categoryId);

            if($category == null)
                return [];

            $categoryId = $category->parent_id;

            if($isTrashed) {
                $isTrashed = false;
                continue;
            }

            $ids[] = $category->id;
        }
        while($category->parent_id != null);

        return $ids;
    }

    /**
     * Return ids of a category's children
     * 
     * @param int $categoryId
     * @param array $foundIDs
     * 
     * @return array
     */ 
    public static function categoryChildrenIds($categoryId, $foundIDs = []) {
        $ids = $foundIDs ? $foundIDs : [ (int)$categoryId ];

        $subs = Category::withTrashed()->where('parent_id', $categoryId)->get();

        foreach($subs as $s) {
            $ids[] = $s->id;
            $ids = DataHelper::categoryChildrenIds($s->id, $ids);
        }

        return $ids;
    }

    /**
     * Control the model image
     * 
     * @param Request $request
     * @param boolean $isCreate
     * @param string $path
     * @param Model $class
     * @param int $id
     * @param string $input
     * @param string $column
     * 
     */ 
    public static function dataImage($request, $isCreate, $path, $class, $id, $input, $column) {
        $imageUrl = $isCreate 
        ? ''
        : $class::withTrashed()->find($id)->$column;

        if($request->file($input) != null) {
            $image = $request->file($input);
            $image->store("public/$path");
            $imageUrl = $request->getSchemeAndHttpHost() .'/'. $path .'/'. $image->hashName();
        }

        $class::withTrashed()->where('id', $id)
        ->update([ $column => $imageUrl ]);

        if ($request->post($input) == '1') {
            $class::withTrashed()->where('id', $id)
            ->update([ $column => '' ]);
        }
    }

    /**
     * Check value is unique in name column of brands and categories in create and update
     * 
     * @param Model $class
     * @param string $value
     * @param int|null $id
     * 
     * @return boolean
     */ 
    public static function checkUnique($class, $value, $id = null) {
        $check = $class::where('name', $value);

        if($id != null)
            $check = $check->where('id', '!=', $id);

        return $check->get()->first() == null;
    }

    /**
     * Log the request into files
     * 
     * @param Request $request
     * 
     */ 
    public static function logRequest($request) {
        $data = DataHelper::readLog(null, true);

        array_unshift($data, [
            '__time__' => now() ,
            '__endpoint__' => $request->method() .' '. $request->path() ,
            '__query_string__' => $request->query() ,
            '__inputs__' => $request->post() ,
            '__files__' => $request->file() ,
        ]);

        Storage::put('public/request.log.txt', json_encode($data));
    }

    /**
     * Show request log content
     * 
     * @param string|null $action
     * @param boolean $output
     * 
     * @return array
     */ 
    public static function readLog($action = null, $output = false) {
        if(!Storage::exists('public/request.log.txt') || $action == 'clear')
            Storage::put('public/request.log.txt', json_encode([]));

        $data = Storage::get('public/request.log.txt');
        if($data == null) $data = '[]';

        return json_decode($data, $output);
    }

}