<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\CategoryBrand;
use App\Models\ProductCategory;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;

class AdminCategoryController extends Controller
{
    /**
     * Return all main categories with their children and with filter and pagination
     * if [list] query parameter is set, Return all categories without paginaition
     * 
     * @see DataHelper::categories()
     * 
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getList(Request $request)
    {
        $result = null;

        if( $request->query('list') != null ) 
        {
            $categories = Category::get();
            
            $result = 
            [
                'data'  => $categories ,
                'count' => 
                [ 
                    'current' => count($categories) , 
                    'total'   => count($categories) , 
                    'limit'   => -1 
                ] ,
                'pagination' => 
                [ 
                    'current' => 1 , 
                    'last'    => 1 
                ]
            ];
        }
        else 
        {
            $result = DataHelper::categories($request->query());
        }

        extract($result);
        
        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No category found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'categories' => $data
            ], 200);
    }

    /**
     * Return only main categories
     * or direct children of a category
     * 
     * @param \Illuminate\Http\Request
     * @param int  $categoryId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getSimpleList(Request $request, $categoryId = null)
    {
        $categories = null;

        if($categoryId == null) 
        {
            $categories = Category::where('parent_id', null)->get();
        }
        else {
            $categories = Category::where('parent_id', $categoryId)->get();
        }
        
        $status = count($categories) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No category found.' ,
                'categories' => $categories
            ], 200);
    }
    
    /**
     * Create category or Update a category by id
     * 
     * @see DataHelper::validate()
     * @see DataHelper::categories()
     * @see DataHelper::checkUnique()
     * 
     * @param \Illuminate\Http\Request
     * @param int|null  $categoryId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function createOrUpdateCategory(Request $request, $categoryId = null)
    {
        $isCreate = $categoryId == null;

        $v = DataHelper::validate( response() , $request->post() , 
        [
            'category_name'      => [ 'نام دسته', 'required|filled|max:50' ] ,
            'category_parent_id' => [ 'شناسه والد', 'nullable|numeric' ] ,
        ]);

        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        $data = 
        [
            'name' => $request->post('category_name') ,
            'slug' => preg_replace('/ +/', '-', $request->post('category_name')) ,
        ];

        $isUnique = DataHelper::checkUnique(Category::class, $data['name'], $categoryId);

        $msg = 'نام دسته بندی نمیتواند تکراری باشد';

        $status = 401;

        if($isUnique) 
        {
            if($isCreate) 
            {
                $parentId = $request->post('category_parent_id', null);

                $data['parent_id'] = $parentId != null ? (int)$parentId : null;
                
                Category::create($data);
            }
            else 
            {
                Category::withTrashed()
                ->where('id', $categoryId)
                ->update($data);
            }

            $msg = $isCreate ? 'دسته بندی با موفقیت ثبت شد' : 'تغییرات با موفقیت ثبت شد';
            
            $status = 200;
        }

        $result = DataHelper::categories($request->query());

        extract($result);

        return 
            response()
            ->json(
            [
                'status'     => $status ,
                'message'    => $msg ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'categories' => $data ,
            ], 200);
    }

    /**
     * Soft delete or Restore a category and all of it's children
     * 
     * @see DataHelper::categoryChildrenIds()
     * 
     * @param \Illuminate\Http\Request
     * @param int  $categoryId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function changeCategoryState(Request $request, $categoryId)
    {
        $category = Category::withTrashed()->find($categoryId);

        $childrenIDs = DataHelper::categoryChildrenIds($categoryId);

        if( $category->deleted_at == null ) 
        {
            foreach($childrenIDs as $cid) 
            {
                Category::where('id', $cid)->delete();
            }
        }
        else 
        {
            foreach($childrenIDs as $cid) 
            {
                Category::withTrashed()->where('id', $cid)->restore();
            }
        }

        return $this->getList($request);
    }

}
