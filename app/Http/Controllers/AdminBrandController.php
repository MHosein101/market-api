<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use App\Models\CategoryBrand;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;

/**
 * Admin panel brands management
 * 
 * @author Hosein marzban
 */ 
class AdminBrandController extends Controller
{
    
    /**
     * Return all brands with filter OR all of them with out filter (as list)
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        $result = null;

        if($request->query('list') != null) {
            $brands = Brand::get();
            
            $result = [
                'data' => $brands ,
                'count' => [ 'current' => count($brands) , 'total' => count($brands) , 'limit' => -1 ] ,
                'pagination' => [ 'current' => 1 , 'last' => 1 ]
            ];
        }
        else {
            $result = SearchHelper::dataWithFilters(
                $request->query() , 
                Brand::class , 
                '*' , 
                [ 
                    'name' => null , 
                    'company' => null 
                ] , 
                'filterBrands'
            );
        }
        
        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No brand found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'brands' => $data
        ], 200);
    }

    /**
     * Create new brand or Update existing brand by id
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * @see DataHelper::dataImage(Request, boolean, string, Model, int, string, string) : void
     * 
     * @param Request $request
     * @param int|null $brandId
     * 
     * @return Response
     */ 
    public function createOrUpdateBrand(Request $request, $brandId = null)
    {
        $isCreate = ($brandId == null) ? true : false;

        $v = DataHelper::validate( response() , $request->post() , 
        [
            'brand_name'         => [ 'نام برند', 'required|filled|max:50' ] ,
            'brand_english_name' => [ 'نام انگلیسی برند', 'required|filled|max:50' ] ,
            'brand_company'      => [ 'شرکت برند', 'required|filled|max:50' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $v = DataHelper::validate( response() , $request->file() , 
        [
            'brand_logo' => [ 'فایل لوگو', 'file|image|between:16,1024' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $data = [
            'name' => $request->post('brand_name') ,
            'english_name' => $request->post('brand_english_name') ,
            'slug' => preg_replace('/ +/', '-', $request->post('brand_name')) ,
            'company' => $request->post('brand_company') ,
        ];

        $isUnique = DataHelper::checkUnique(Brand::class, $data['name'], $brandId);
        $msg = 'نام برند نمیتواند تکراری باشد';
        $status = 401;

        if($isUnique) {
            if($isCreate) {
                $data['logo_url'] = '';
                $brand = Brand::create($data);

                $brandId = $brand->id;
            }
            else {
                Brand::withTrashed()
                ->where('id', $brandId)
                ->update($data);
            }

            DataHelper::dataImage($request, $isCreate, 'brands', Brand::class, $brandId, 'brand_logo', 'logo_url');

            $msg = $isCreate ? 'برند با موفقیت ثبت شد' : 'تغییرات با موفقیت ثبت شد';
            // $status = $isCreate ? 201 : 200;
            $status = 200;
        }

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            Brand::class , 
            '*' , 
            [ 
                'name' => null , 
                'company' => null 
            ] , 
            'filterBrands'
        );
        extract($result);

        return response()
        ->json([
            'status' => $status ,
            'message' => $msg ,
            'count' => $count ,
            'pagination' => $pagination ,
            'brands' => $data ,
        ], 200);
    }

    /**
     * Soft delete or Restore brand
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * @param int $brandId
     * 
     * @return Response
     */ 
    public function changeBrandState(Request $request, $brandId)
    {
        $check = Brand::withTrashed()->find($brandId);
        $msg = '';

        if($check->deleted_at == null) {
            Brand::where('id', $brandId)->delete();
            $msg = 'Brand soft deleted.';
        }
        else {
            Brand::withTrashed()->where('id', $brandId)->restore();
            $msg = 'Brand restores.';
        }

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            Brand::class , 
            '*' , 
            [ 
                'name' => null , 
                'company' => null 
            ] , 
            'filterBrands'
        );
        extract($result);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => $msg ,
            'count' => $count ,
            'pagination' => $pagination ,
            'brands' => $data
        ], 200);
    }

}
