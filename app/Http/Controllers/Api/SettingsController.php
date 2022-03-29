<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Settings::first();

        return response()->json(['status' => true, 'data' => $settings], 200);
    }

    public function edit(Request $request)
    {
        $request = $request->all();

        $validator = Validator::make($request, [
            'bot_key' => 'required|string|max:255',
            'chat_id' => 'required|numeric',
            'max_grids' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'code' => 400, 'error' => $validator->errors()->first()], 400);
        }

        $settings = Settings::first();

        $settings->bot_key = $request['bot_key'];
        $settings->chat_id = $request['chat_id'];
        $settings->max_grids = $request['max_grids'];

        $settings->save();

        return response()->json(['status' => true, 'data' => $settings], 204);
    }

    public function status()
    {
        $status = Settings::pluck('status')->first();

        return response()->json(['status' => true, 'trading_status' => $status], 200);
    }

    public function editStatus($status)
    {
        $validator = Validator::make(['status' => $status], [
            'status' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'code' => 400, 'error' => $validator->errors()->first()], 400);
        }

        $settings = Settings::first();

        $settings->status = $status;

        $settings->save();

        return response()->json(['status' => true, 'trading_status' => $settings->status], 204);
    }

    public function replyToMessage()
    {
        $status = Settings::pluck('reply_to_message')->first();

        return response()->json(['status' => true, 'reply_to_message' => $status], 200);
    }

    public function editReplyToMessage($replyToMessage)
    {
        $validator = Validator::make(['replyToMessage' => $replyToMessage], [
            'replyToMessage' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'code' => 400, 'error' => $validator->errors()->first()], 400);
        }

        $settings = Settings::first();

        $settings->reply_to_message = $replyToMessage;

        $settings->save();

        return response()->json(['status' => true, 'reply_to_message' => $settings->reply_to_message], 204);
    }
}
