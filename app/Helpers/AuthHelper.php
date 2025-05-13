<?php

namespace App\Helpers;

use App\Models\FormToken;
use Illuminate\Http\Request;

class AuthHelper {
    public static function getAuthenticatedCustomer(Request $request) {
        $token = $request->cookie('muktiform_access_token');

        if (!$token) return null;

        $formToken =FormToken::where('token', $token)->first();

        return $formToken?->customer;
    }
}