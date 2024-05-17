<?php
namespace App\Http\Controllers\Api;
use Stripe\Stripe;
use App\Models\Order;
use App\Models\Product;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Stripe\PaymentMethod;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductApiController extends Controller
{
    public function checkout(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $products = Product::all();
        $totalPrice = 0;
        foreach ($products as $product) {
            $totalPrice += $product->price;
        }

        $paymentIntent = PaymentIntent::create([
            'amount' => $totalPrice * 100 ,
            'currency' => 'usd',
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
        ]);

        $order = new Order();
        $order->status = 'unpaid';
        $order->paymentRef = $paymentIntent->client_secret;
        $order->total_price = $totalPrice;
        $order->save();

        return response()->json([
            'client_secret' => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
            'order_id' => $order->id
        ]);
    }
    public function webhookStripe(Request $request){
        switch ($request->type) {
            case 'payment_intent.succeeded':
               Log::info('Payment Webhook stripe =====>'.json_encode($request->all()));
                $requestObject = $request->data->object;
             return  $this->paymentIntentSucceeded($requestObject->client_secret);
                break;
            
            default:
                # code...
                break;
        }
        // if($request->type === 'payment_intenet')
    }
    private function paymentIntentSucceeded($clientSecret){
        $order = Order::where('paymentRef',$clientSecret)->first();
        
        if($order){
            $order->update(['status'=>'paid']);
            
            return response()->json(['success' => true, 'message' => 'Payment confirmed successfully']);
        }
        return response()->json(['error' => 'Payment confirmation failed', 'message' => 'error in check the amount'], 400);
      
    }
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_intent_id' => 'required',
        ]);
        $order = Order::findOrFail($request->order_id);
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $paymentIntent = PaymentIntent::retrieve($request->input('payment_intent_id'));

        try {
            $paymentIntent->confirm([
                'payment_method' => $request->input('payment_method'),
            ]);

            $order->status = 'paid';
            $order->save();

            return response()->json(['success' => true, 'message' => 'Payment confirmed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment confirmation failed', 'message' => $e->getMessage()], 400);
        }
    }
    
}
