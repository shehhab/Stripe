<?php

namespace App\Http\Controllers\Api;

use Stripe\Stripe;
use App\Models\Order;
use App\Models\Product;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class orderApiTwo extends Controller
{


    public function TwoPayment(Request $request)
    {

        
    }

}
