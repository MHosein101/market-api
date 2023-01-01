<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\UserAccountType;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;

/**
 * Admin panel products management
 * 
 * @author Hosein marzban
 */ 
class AdminProductController extends Controller
{
    
    /**
     * Return all products with filter OR one product by id
     * If store user requested, default state filter value is 'active'
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        if( $request->query('id') != null ) {

            $product = Product::withTrashed()->find( $request->query('id') );
            $status = ( $product != null ) ? 200 : 404;

            return response()
            ->json([ 
                'status' => $status ,
                'message' => ($status == 200) ? 'OK' : 'No product found.' ,
                'product' => $product
            ], $status);
        }

        $state = 'all';
        if($request->user->account_type == UserAccountType::Store) {
            $state = 'active';
            
            unset($request['state']);
        }

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            Product::class , 
            '*' , 
            [ 
                'title' => null ,
                'barcode' => null ,
                'brand_id' => null ,
                'category_id' => null ,
                'state' => $state
            ] , 
            'filterProducts'
        );

        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No product found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], 200);
    }

    /**
     * Create new product info or Update existing info by id
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see SearchHelper::categoryParentsIds(int) : Model[]
     * 
     * @param Request $request
     * @param int|null $productId
     * 
     * @return Response
     */ 
    public function createOrUpdateProduct(Request $request, $productId = null)
    {
        $isCreate = ($productId == null) ? true : false;

        $uniqueIgnore = $isCreate ? '' : ",$productId,id";

        $v = DataHelper::validate( response() , $request->post() , 
        [
            'title'       => [ 'عنوان محصول', 'required|filled|max:250|unique:products,title' . $uniqueIgnore ] ,
            'barcode'     => [ 'بارکد', 'required|filled|numeric|unique:products,barcode' . $uniqueIgnore ] ,
            'description' => [ 'توضیحات', 'nullable|max:500' ] ,
            'brand_id'    => [ 'برند', 'required|numeric' ] ,
            'category_id' => [ 'دسته بندی', 'required|numeric' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];
        
        if($isCreate && count($request->file()) == 0) {
            response()
            ->json([ 
                'status' => 400 ,
                'message' => 'Data validation failed.' , 
                'errors' => [ 'حداقل یک عکس برای محصول الزامی است' ]
            ], 400);
        }
        
        $filesRules = [];   
        foreach($request->file() as $name => $_)
            $filesRules[$name] = [ 'عکس محصول' , 'file|image|between:4,1024' ];

        $v = DataHelper::validate( response() , $request->file(), $filesRules);
        if( $v['code'] == 400 ) return $v['response'];

        $data = [
            'title' => $request->post('title') , 
            'slug' => preg_replace('/ +/', '-', $request->post('title')) ,
            'barcode' => $request->post('barcode') , 
            'description' => DataHelper::post('description', '') , 
            'brand_id' => (int)$request->post('brand_id')
        ];

        $product = null;

        if($isCreate) {
            $product = Product::create($data);

            $productId = $product->id;
        }
        else {
            Product::withTrashed()
            ->where('id', $productId)
            ->update($data);

            $product = Product::withTrashed()->find($productId);
        }

        $imagesCount = (int)$request->input("images_count", 0);

        ProductImage::where('product_id', $productId)->update([ 'is_main' => false ]);

        for($i = 0; $i < $imagesCount; $i++) {

            $image = $request->file("product_image_{$i}_image");
            $isMain = $request->input("product_image_{$i}_is_main");
            $isMain = ( strtolower($isMain) == "true" );

            if($image != null) {
                $image->store('public/products');
                $imageUrl = $request->getSchemeAndHttpHost() . '/products/' . $image->hashName();
                
                ProductImage::create([
                    'url' => $imageUrl ,
                    'is_main' => $isMain ,
                    'product_id' => $productId
                ]);
            }

            if (!$isCreate && $image == null && $isMain) {
                $id = (int)$request->input("product_image_{$i}_id");
                ProductImage::where('id', $id)->update([ 'is_main' => true ]);
            }
        }

        $categoryChanged = true;

        if(!$isCreate) {
            $firstCategory = ProductCategory::where('product_id', $productId)->first();

            $categoryChanged = ( $firstCategory->category_id != (int)$request->input('category_id') );

            if($categoryChanged)
                ProductCategory::where('product_id', $productId)->delete();
        }

        if($categoryChanged) {
            $categoriesIDs = DataHelper::categoryParentsIds((int)$request->input('category_id'));

            foreach($categoriesIDs as $cid) {
                ProductCategory::create([
                    'product_id' => $productId ,
                    'category_id' => $cid
                ]);
            }
        }

        $status = $isCreate ? 201 : 200;
        return response()
        ->json([ 
            'status' => $status ,
            'message' =>  $isCreate ? 'Product created.' : 'Product updated.' ,
            'product' => $product
        ], $status);
    }

    /**
     * Soft delete product image by id
     * 
     * @param Request $request
     * @param int $imageId
     * 
     * @return Response
     */ 
    public function deleteProductImage(Request $request, $imageId)
    {
        $productId = ProductImage::find($imageId)->product_id;

        ProductImage::where('id', $imageId)->delete();

        $product = Product::withTrashed()->find($productId);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'product' => $product
        ], 200);
    }
    
    /**
     * Soft delete or Restore product
     * 
     * @param Request $request
     * @param int $productId
     * 
     * @return Response
     */ 
    public function changeProductState(Request $request, $productId)
    {
        $check = Product::withTrashed()->find($productId);
        $msg = '';

        if($check->deleted_at == null) {
            Product::where('id', $productId)->delete();
            $msg = 'Product soft deleted.';
        }
        else {
            Product::withTrashed()->where('id', $productId)->restore();
            $msg = 'Product restored.';
        }

        $product = Product::withTrashed()->find($productId);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => $msg ,
            'product' => $product
        ], 200);
    }

}
