<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;

class AdminBrandController extends Controller
{
    
    /**
     * Return brands with with filters and pagination
     * if [list] query parameter is set, Return all brands without paginaition
     * 
     * @see SearchHelper::dataWithFilters()
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
            $brands = Brand::get();
            
            $result = 
            [
                'data'  => $brands ,
                'count' => 
                [ 
                    'current' => count($brands) ,
                    'total'   => count($brands) ,
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
            $result = SearchHelper::dataWithFilters(
                $request->query() , 
                Brand::class , 
                '*' , 
                [ 
                    'name'    => null , 
                    'company' => null 
                ] , 
                'filterBrands'
            );
        }
        
        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No brand found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'brands'     => $data
            ], 200);
    }

    /**
     * Create new brand or Update a brand by id
     * 
     * @see DataHelper::validate()
     * @see SearchHelper::dataWithFilters()
     * @see DataHelper::dataImage()
     * @see DataHelper::checkUnique()
     * 
     * @param \Illuminate\Http\Request
     * @param int|null $brandId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function createOrUpdateBrand(Request $request, $brandId = null)
    {
        $isCreate = $brandId == null;

        $v = DataHelper::validate( response() , $request->post() , 
        [
            'brand_name'         => [ 'نام برند', 'required|filled|max:50' ] ,
            'brand_english_name' => [ 'نام انگلیسی برند', 'required|filled|max:50' ] ,
            'brand_company'      => [ 'شرکت برند', 'required|filled|max:50' ] ,
        ]);

        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        $v = DataHelper::validate( response() , $request->file() , 
        [
            'brand_logo' => [ 'فایل لوگو', 'file|image|between:4,1024' ] ,
        ]);

        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        $data = 
        [
            'name'    => $request->post('brand_name') ,
            'slug'    => preg_replace('/ +/', '-', $request->post('brand_name')) ,
            'company' => $request->post('brand_company') ,
            'english_name' => $request->post('brand_english_name') ,
        ];

        $isUnique = DataHelper::checkUnique(Brand::class, $data['name'], $brandId);

        $msg = 'نام برند نمیتواند تکراری باشد';

        $status = 401;

        if($isUnique) 
        {
            if($isCreate) 
            {
                $data['logo_url'] = '';
                $brand = Brand::create($data);

                $brandId = $brand->id;
            }
            else 
            {
                Brand::withTrashed()
                ->where('id', $brandId)
                ->update($data);
            }

            DataHelper::dataImage($request, $isCreate, 'brands', Brand::class, $brandId, 'brand_logo', 'logo_url');

            $msg = $isCreate ? 'برند با موفقیت ثبت شد' : 'تغییرات با موفقیت ثبت شد';

            $status = 200;
        }

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            Brand::class , 
            '*' , 
            [ 
                'name'    => null , 
                'company' => null 
            ] , 
            'filterBrands'
        );

        extract($result);

        return 
            response()
            ->json(
            [
                'status'     => $status ,
                'message'    => $msg ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'brands'     => $data ,
            ], 200);
    }

    /**
     * Soft delete or Restore brand
     * 
     * @see SearchHelper::dataWithFilters()
     * 
     * @param \Illuminate\Http\Request
     * @param int $brandId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function changeBrandState(Request $request, $brandId)
    {
        $brand = Brand::withTrashed()->find($brandId);

        if( $brand->deleted_at == null ) 
        {
            $brand->delete();
        }
        else 
        {
            $brand->restore();
        }

        return $this->getList($request);
    }

}
