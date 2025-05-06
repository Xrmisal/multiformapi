<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FormController extends Controller
{
    public function start(Request $request) {
        return response()->json(['message' => 'Start form endpoint hit']);
    }

    public function updateStep(Request $request, $id) {
        return response()->json(['message' => "Update step for ID $id"]);
    }

    public function checkStatus($id) {
        return response()->json(['message' => "Status check for ID $id"]);
    }
}
