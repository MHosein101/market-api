<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\CategoryBrand;
use App\Models\ProductCategory;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;

/**
 * Admin panel categories management
 * 
 * @author Hosein marzban
 */ 
class AdminCategoryController extends Controller
{
    /**
     * Return all main categories with their children with filter 
     * OR all of them in one level with out filter (as list)
     * 
     * @see DataHelper::categories(array) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        $result = null;

        if($request->query('list') != null) {
            $categories = Category::get();
            
            $result = [
                'data' => $categories ,
                'count' => [ 'current' => count($categories) , 'total' => count($categories) , 'limit' => -1 ] ,
                'pagination' => [ 'current' => 1 , 'last' => 1 ]
            ];
        }
        else {
            $result = DataHelper::categories($request->query());
        }

        extract($result);
        
        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No category found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'categories' => $data
        ], 200);
    }

    /**
     * Return just main categories or direct children of one category
     * 
     * @param Request $request
     * @param int  $categoryId
     * 
     * @return Response
     */ 
    public function getSimpleList(Request $request, $categoryId = null)
    {
        $categories = Category::selectRaw('*');

        if($categoryId == null) {
            $categories = $categories->where('parent_id', null)->orWhere('parent_id', '<', 1)->get();
        }
        else {
            $categories = $categories->where('parent_id', $categoryId)->get();
        }
        
        $status = ( count($categories) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No category found.' ,
            'categories' => $categories
        ], 200);
    }
    
    /**
     * Create category or Update existing category by id
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see DataHelper::categories(array) : Model[]
     * 
     * @param Request $request
     * @param int|null  $categoryId
     * 
     * @return Response
     */ 
    public function createOrUpdateCategory(Request $request, $categoryId = null)
    {
        $isCreate = ($categoryId == null) ? true : false;

        $checkUnique = $isCreate ? '|unique:categories,name' : '';

        $v = DataHelper::validate( response() , $request->all() , 
        [
            'category_name'      => [ 'نام دسته', 'required|filled|between:3,50' . $checkUnique ] ,
            'category_parent_id' => [ 'شناسه والد', 'nullable|numeric' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $data = [
            'name' => $request->input('category_name') ,
            'slug' => preg_replace('/ +/', '-', $request->input('category_name')) ,
        ];

        if($isCreate) {
            $parentId = $request->input('category_parent_id', null);
            $data['parent_id'] = $parentId != null ? (int)$parentId : null;
            
            Category::create($data);
        }
        else {
            Category::withTrashed()
            ->where('id', $categoryId)
            ->update($data);
        }

        $result = DataHelper::categories($request->query());
        extract($result);

        $status = $isCreate ? 201 : 200;
        return response()
        ->json([ 
            'status' => $status ,
            'message' =>  $isCreate ? 'Category created.' : 'Category updated.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'categories' => $data
        ], $status);
    }

    /**
     * Soft delete or Restore category and all of it's children
     * 
     * @see DataHelper::categoryChildrenIds(int) : array
     * @see DataHelper::categories(array) : Model[]
     * 
     * @param Request $request
     * @param int  $categoryId
     * 
     * @return Response
     */ 
    public function changeCategoryState(Request $request, $categoryId)
    {
        $check = Category::withTrashed()->find($categoryId);
        $msg = '';

        $IDs = DataHelper::categoryChildrenIds($categoryId);

        if($check->deleted_at == null) {
            foreach($IDs as $cid) {
                Category::where('id', $cid)->delete();
            }
            $msg = 'Category and its children soft deleted.';
        }
        else {
            foreach($IDs as $cid) {
                Category::withTrashed()->where('id', $cid)->restore();
            }
            $msg = 'Category and its children restored.';
        }

        $result = DataHelper::categories($request->query());
        extract($result);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => $msg ,
            'count' => $count ,
            'pagination' => $pagination ,
            'categories' => $data
        ], 200);
    }

}
