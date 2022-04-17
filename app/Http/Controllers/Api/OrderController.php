<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();

        return response()->json(['status' => true, 'data' => $orders], 200);
    }

    public function show($id)
    {
        $order = Order::find($id);

        return response()->json(['status' => true, 'data' => $order], 200);
    }

    public function trades()
    {
        $orders = Order::where('sold_at', null)->get();

        return response()->json(['status' => true, 'data' => $orders], 200);
    }

    public function sellOrders()
    {
        $orders = Order::where('sold_at', '!=', null)->get();

        return response()->json(['status' => true, 'data' => $orders], 200);
    }

    public function store(Request $request)
    {
        $order = $request->all();

        $validator = Validator::make($order, [
            'symbol' => 'required',
            'users' => 'required|array',
            'users.*' => 'required|array',
            'users.*.username' => 'required|string',
            'users.*.price' => 'required|numeric',
            'users.*.size' => 'required|numeric',
            'users.*.fee' => 'required|numeric',
            'users.*.created_at' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'code' => 400, 'error' => $validator->errors()->first()], 400);
        }

        $trades = Order::where('symbol', $order['symbol'])->where('sold_at', null)->get();

        foreach ($order['users'] as $user) {
            $user['symbol'] = $order['symbol'];
            $user['grid'] = 1;

            foreach ($trades as $trade)
            {
                if ($trade->username === $user['username']) {
                    $user['grid']++;
                }
            }

            $order = Order::create($user);
        }

        return response()->json(['status' => true, 'data' => $order], 201);
    }

    public function sell($id, Request $request)
    {
        $order = $request->all();

        $order['id'] = $id;

        $validator = Validator::make($order, [
            'id' => 'required|numeric',
            'price' => 'required|numeric',
            'fee' => 'required|numeric',
            'sold_at' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'code' => 400, 'error' => $validator->errors()->first()], 400);
        }

        $order = Order::find($id);

        if (isset($order->sold_at)) {
            return response()->json(['status' => false, 'code' => 400, 'error' => 'This order is already sold out'], 400);
        }

        $order->selling_price = $request->price;
        $order->selling_fee = $request->fee;
        $order->sold_at = date('Y-m-d H:i:s', $request->sold_at);

        $order->save();

        return response()->json(['status' => true, 'data' => $order], 204);
    }

    public function report(Request $request, $username = null)
    {
        if (!isset($request->all()['type'])) {
            return response()->json(['status' => false, 'code' => 400, 'error' => 'The type field is required.'], 400);
        }

        $type = $request->all()['type'];

        if ($type == "sell") {
            $orders = Order::where('sold_at', '!=', null)->reorder('sold_at', 'asc')->get();
        } elseif ($type == "buy") {
            $orders = Order::where('sold_at', null)->reorder('created_at', 'asc')->get();
        } else {
            return response()->json(['status' => false, 'code' => 400, 'error' => "This type is not defined. You can send 'sell' or 'buy'"], 400);
        }

        $usersnames = [];

        foreach ($orders as $order)
        {
            $i = array_search($order->username, $usersnames, true);

            if (!($i === 0 || $i > 0)) {
                if (!isset($username) || $username == $order->username) {
                    array_push($usersnames, $order->username);
                }
            }
        }

        $report = [];

        foreach ($usersnames as $username) {
            $total_profits = 0;
            $currencies = [];
            $symbols = [];

            if ($type ==  "sell")
            {
                foreach ($orders as $order)
                {
                    $i = array_search($order->symbol, $currencies, true);

                    if ($username == $order->username && !($i === 0 || $i > 0))
                    {
                        array_push($currencies, $order->symbol);

                        $symbol = [
                            'symbol' => $order->symbol,
                            'total_profits' => 0,
                            'grids' => [],
                        ];

                        foreach ($orders as $order)
                        {
                            if ($username == $order->username && $symbol['symbol'] == $order->symbol)
                            {
                                $accuracyOfData = "High";

                                if ($order->fee === null) {
                                    $order->fee = 0;
                                    $accuracyOfData = "Low";
                                }

                                $grid = [
                                    'grid' => $order->grid,
                                    'earning' => number_format((($order->size - $order->fee) * $order->selling_price) - ($order->size * $order->price), 4, '.', ''),
                                    'size' => $order->size - $order->fee,
                                    'selling_price' => number_format($order->selling_price, 8),
                                    'fee' => $order->selling_fee,
                                    'sold_at' => gmdate("Y-m-d h:i:sA", strtotime($order->sold_at) + 3600*(7+date("I"))),
                                    'accuracy_of_data' => $accuracyOfData
                                ];

                                if ($order->selling_price == 0) {
                                    $grid = [
                                        'grid' => $order->grid,
                                        'earning' => null,
                                        'size' => $order->size - $order->fee,
                                        'selling_price' => null,
                                        'fee' => null,
                                        'sold_at' => null,
                                        'accuracy_of_data' => $accuracyOfData,
                                        'message' => 'The user has already sold this deal by hand'
                                    ];
                                }

                                array_push($symbol['grids'], $grid);

                                $symbol['total_profits'] += $grid['earning'];
                            }
                        }

                        $symbol['total_profits'] = number_format($symbol['total_profits'], 4, '.', '');

                        array_push($symbols, $symbol);

                        $total_profits += $symbol['total_profits'];
                    }
                }
            }

            if ($type == "buy")
            {
                foreach ($orders as $order)
                {
                    if ($username == $order->username)
                    {
                        $accuracyOfData = "High";

                        if ($order->fee === null) {
                            $order->fee = 0;
                            $accuracyOfData = "Low";
                        }

                        $openTrade = [
                            'symbol' => $order->symbol,
                            'grid' => $order->grid,
                            'price' => number_format($order->price, 8),
                            'size' => $order->size,
                            'fee' => $order->fee,
                            'amount_buy' => $order->size * $order['price'],
                            'created_at' => gmdate("Y-m-d h:i:sA", strtotime($order->created_at) + 3600*(7+date("I"))),
                            'accuracy_of_data' => $accuracyOfData
                        ];

                        array_push($symbols, $openTrade);
                    }
                }
            }

            $data = [
                "username" => $username,
                "total_profits" => 0,
                "symbols" => $symbols
            ];

            if ($type ==  "sell") {
                $data['total_profits'] = number_format($total_profits, 4, '.', '');
            } else {
                unset($data['total_profits']);
            }

            array_push($report, $data);
        }

        return response()->json(['status' => true, 'data' => $report], 200);
    }

}
