<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        if(!empty($request->cart) && count($request->cart) > 0){
            $order = new Order();
            $order->name = $request->name;
            $order->email = $request->email;
            $order->address = $request->address;
            $order->mobile = $request->mobile;
            $order->state = $request->name;
            $order->zip = $request->name;
            $order->city = $request->city;
            $order->grand_total = $request->grand_total;
            $order->sub_total = $request->sub_total;
            $order->discount = $request->discount;
            $order->shipping = $request->shipping_charges;
            $order->payment_method = $request->payment_method;
            $order->payment_status = $request->payment_status;
            $order->status = $request->status;
            $order->user_id = auth()->user()->id;
            $order->save();

            //Save order items
            foreach ($request->cart as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->name = $item['title'];
                $orderItem->product_id = $item['product_id'];
                $orderItem->quantity = $item['qty'];
                $orderItem->price = $item['qty']  *  $item['price'];
                $orderItem->unit_price = $item['price'];
                $orderItem->size = $item['size'] ?? null;
                $orderItem->save();
            }

            return response()->json([
                'message' => 'Order placed successfully', 
                'order_id' => $order->id,
                'status' => 201
            ], 201);
        } else {
            return response()->json(['message' => 'Cart is empty'], 400);
        }
    }

    /**
     * Return all orders for the authenticated user.
     *
     * GET /api/account/order
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with('orderItems')
            ->orderBy('created_at', 'desc')
            ->get();

        return OrderResource::collection($orders)->response()->setStatusCode(200);
    }
}
