<?php

namespace App\Exceptions\Wishlist;

use Exception;

class ProductNotFoundException extends Exception
{
    public function __construct(int $productId)
    {
        parent::__construct("Product with ID {$productId} not found");
        $this->code = 404;
    }
}
