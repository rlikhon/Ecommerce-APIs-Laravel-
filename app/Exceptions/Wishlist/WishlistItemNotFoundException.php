<?php

namespace App\Exceptions\Wishlist;

use Exception;

class WishlistItemNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Wishlist item not found');
        $this->code = 404;
    }
}
