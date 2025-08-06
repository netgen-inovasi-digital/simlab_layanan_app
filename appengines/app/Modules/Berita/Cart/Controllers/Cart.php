<?php

namespace Modules\Cart\Controllers;

use App\Controllers\BaseController;
use App\Models\MyModel;

class Cart extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'Keranjang Belanja',
            'content' => 'Modules\Cart\Views\v_cart',
        ];
        $this->layoutHeaderFooter($data);
        return view('website', $data);
    }

    public function getProducts()
    {
        $cartData = $this->request->getGet('cart');
        
        if (empty($cartData)) {
            return $this->response->setJSON([
                'success' => true,
                'data' => []
            ]);
        }

        $cartItems = json_decode($cartData, true);
        if (!$cartItems) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid cart data'
            ]);
        }

        $validatedItems = [];
        $produkModel = new MyModel('produk');
        $variantModel = new MyModel('product_variants');
        $photoModel = new MyModel('produk_photos');
        
        foreach ($cartItems as $item) {
            if (isset($item['variant_id']) && isset($item['quantity'])) {
                // New variant-based cart
                $variant = $variantModel->getDataById('id', $item['variant_id']);
                if ($variant) {
                    $produk = $produkModel->getDataById('id_produk', $variant->produk_id);
                    if ($produk && $produk->status === 'publish') {
                        // Get variant details
                        $sizeModel = new MyModel('sizes');
                        $colorModel = new MyModel('colors');
                        $size = $sizeModel->getDataById('id', $variant->size_id);
                        $color = $colorModel->getDataById('id', $variant->color_id);
                        
                        // Get primary photo
                        $photo = $photoModel->getDataByArray(['produk_id' => $produk->id_produk, 'is_primary' => 1]);
                        
                        $validatedItems[] = [
                            'id' => $produk->id_produk,
                            'variant_id' => $variant->id,
                            'name' => $produk->name,
                            'size' => $size ? $size->name : 'Unknown',
                            'color' => $color ? $color->name : 'Unknown',
                            'harga' => $produk->harga,
                            'photo' => $photo ? $photo->photo : null,
                            'slug' => $produk->slug,
                            'quantity' => (int)$item['quantity']
                        ];
                    }
                }
            } elseif (isset($item['id']) && isset($item['quantity'])) {
                // Legacy direct product support (for backward compatibility)
                $produk = $produkModel->getDataById('id_produk', $item['id']);
                if ($produk && $produk->status === 'publish') {
                    // Get default variant
                    $defaultVariant = $variantModel->getDataByArray(['produk_id' => $produk->id_produk]);
                    $photo = $photoModel->getDataByArray(['produk_id' => $produk->id_produk, 'is_primary' => 1]);
                    
                    $validatedItems[] = [
                        'id' => $produk->id_produk,
                        'variant_id' => $defaultVariant ? $defaultVariant->id : null,
                        'name' => $produk->name,
                        'size' => 'Default',
                        'color' => 'Default',
                        'harga' => $produk->harga,
                        'photo' => $photo ? $photo->photo : null,
                        'slug' => $produk->slug,
                        'quantity' => (int)$item['quantity']
                    ];
                }
            }
        }
        
        return $this->response->setJSON([
            'success' => true,
            'data' => $validatedItems
        ]);
    }
}
