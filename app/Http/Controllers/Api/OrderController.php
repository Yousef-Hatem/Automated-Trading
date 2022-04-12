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
        $orders = Order::where('sold_at', null)->select('id', 'symbol', 'price', 'users', 'msg_id', 'created_at')->get();

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
            'symbol' => 'required|max:15|min:4',
            'price' => 'required|numeric',
            'users' => 'required|array',
            'msg_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'code' => 400, 'error' => $validator->errors()->first()], 400);
        }

        $trades = Order::where('symbol', $order['symbol'])->where('sold_at', null)->get();

        for ($i=0; $i < count($order['users']); $i++)
        {
            $user = $order['users'][$i];
            $user['grid'] = 1;

            foreach ($trades as $trade)
            {
                $tradeUsers = json_decode($trade->users);

                foreach ($tradeUsers as $tradeUser)
                {
                    if ($tradeUser->username === $user['username']) {
                        $user['grid']++;
                    }
                }
            }

            $order['users'][$i] = $user;
        }

        $order['users'] = json_encode($order['users']);

        $order = Order::create($order);

        return response()->json(['status' => true, 'data' => $order], 201);
    }

    public function sell($id, Request $request)
    {
        $order = $request->all();

        $order['id'] = $id;

        $validator = Validator::make($order, [
            'id' => 'required|numeric',
            'price' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'code' => 400, 'error' => $validator->errors()->first()], 400);
        }

        $order = Order::find($id);

        if (isset($order->sold_at)) {
            return response()->json(['status' => false, 'code' => 400, 'error' => 'This order is already sold out'], 400);
        }

        $order->selling_price = $request->price;

        $order->sold_at = date("Y:m:d H:i:s");

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
            $orders = Order::where('sold_at', null)->select('id', 'symbol', 'price', 'users', 'msg_id', 'created_at')->reorder('created_at', 'asc')->get();
        } else {
            return response()->json(['status' => false, 'code' => 400, 'error' => "This type is not defined. You can send 'sell' or 'buy'"], 400);
        }

        $users = [];

        foreach ($orders as $order)
        {
            foreach (json_decode($order['users']) as $user)
            {
                $i = array_search($user->username, $users, true);

                if (!($i === 0 || $i > 0)) {
                    if (!isset($username) || $username == $user->username) {
                        array_push($users, $user->username);
                    }
                }
            }
        }

        $report = [];

        foreach ($users as $username) {
            $total_profits = 0;
            $currencies = [];
            $symbols = [];

            if ($type ==  "sell")
            {
                foreach ($orders as $order)
                {
                    foreach (json_decode($order['users']) as $user)
                    {
                        $i = array_search($order['symbol'], $currencies, true);

                        if ($username == $user->username && !($i === 0 || $i > 0))
                        {
                            array_push($currencies, $order['symbol']);

                            $symbol = [
                                'symbol' => $order['symbol'],
                                'total_profits' => 0,
                                'grids' => [],
                            ];

                            foreach ($orders as $order)
                            {
                                foreach (json_decode($order['users']) as $user)
                                {
                                    if ($username == $user->username && $symbol['symbol'] == $order['symbol'])
                                    {
                                        $grid = [
                                            'grid' => $user->grid,
                                            'earning' => number_format(($user->size * $order['selling_price']) - ($user->size * $order['price']), 4, '.', ''),
                                            'size' => $user->size,
                                            'selling_price' => $order['selling_price'],
                                            'date' => gmdate("Y-m-d h:i:sA", strtotime($order['sold_at']) + 3600*(7+date("I")))
                                        ];

                                        array_push($symbol['grids'], $grid);

                                        $symbol['total_profits'] += $grid['earning'];
                                    }
                                }
                            }

                            $symbol['total_profits'] = number_format($symbol['total_profits'], 4, '.', '');

                            array_push($symbols, $symbol);

                            $total_profits += $symbol['total_profits'];
                        }
                    }
                }
            }

            if ($type == "buy")
            {
                foreach ($orders as $order)
                {
                    foreach (json_decode($order['users']) as $user)
                    {
                        if ($username == $user->username)
                        {
                            $openTrade = [
                                'symbol' => $order['symbol'],
                                'grid' => $user->grid,
                                'price' => $order['price'],
                                'size' => $user->size,
                                'amount_buy' => $user->size * $order['price'],
                                'date' => gmdate("Y-m-d h:i:sA", strtotime($order['created_at']) + 3600*(7+date("I")))
                            ];

                            array_push($symbols, $openTrade);
                        }
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
