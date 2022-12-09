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

        $checkUnique = $isCreate ? '|unique:brands,name' : '';

        $v = DataHelper::validate( response() , $request->all() , 
        [
            'brand_name'         => [ 'نام برند', 'required|filled|between:3,50' . $checkUnique ] ,
            'brand_english_name' => [ 'نام انگلیسی برند', 'required|filled|between:3,50' ] ,
            'brand_company'      => [ 'شرکت برند', 'required|filled|between:3,50' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $v = DataHelper::validate( response() , $request->file() , 
        [
            'brand_logo' => [ 'فایل لوگو', 'file|image|between:16,1024' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $data = [
            'name' => $request->input('brand_name') ,
            'english_name' => $request->input('brand_english_name') ,
            'slug' => preg_replace('/ +/', '-', $request->input('brand_name')) ,
            'company' => $request->input('brand_company') ,
        ];

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

        $status = $isCreate ? 201 : 200;
        return response()
        ->json([ 
            'status' => $status ,
            'message' =>  $isCreate ? 'Brand created.' : 'Brand updated.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'brands' => $data
        ], $status);
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
