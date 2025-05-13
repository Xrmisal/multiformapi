<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FormToken;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;


class FormController extends Controller
{
    protected function findCustomer(int $id, string $token): ?Customer {
    return Customer::where('id', $id)
                    ->first();
    }
    public function start(Request $request) {
        $data = $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string',
            'name' => 'required|string',
        ]);

        $existing = Customer::where('email', $data['email'])
        ->orWhere('phone', $data['phone'])
        ->whereNotNull('completed_at')
        ->first();

        if ($existing) {
            if ($existing->completed_at) {
                return response()->json([
                    'error' => 'Sorry, this email or phone number has already been used for a completed application.'
                ], 422); // Unprocessable Entity
            }
            $existing->delete();
        }

        $token = bin2hex(random_bytes(32));

        $customer = Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'step' => 1,
            'last_active_at' => Carbon::now()
        ]);

        FormToken::create([
            'token' => $token,
            'customer_id' => $customer->id,
        ]);

        return response()->json([
            'message' => 'New form started.',
            'id' => $customer->id,
            'step' => 1,
        ])->cookie(
            'multiform_access_token',
            $token,
            60,
            '/',
            null,
            true,
            true
        );

    }

    public function updateStep(Request $request, $id)
    {
        $token = $request->cookie('multiform_access_token');

        $formToken = FormToken::where('token', $token)->first();

        if(!$formToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }


        $customer = $formToken->customer;
    
        $step = $customer->step;
    
        switch ($step) {
            case 1:
                $validated = $request->validate([
                    'address'  => 'required|string',
                    'postcode' => 'required|string',
                ]);
                $customer->update([
                    'address' => $validated['address'],
                    'postcode' => $validated['postcode'],
                    'step' => 2,
                    'last_active_at' => now(),
                ]);
                break;
    
            case 2:
                $validated = $request->validate([
                    'dob'    => 'required|date|before:-18 years',
                    'income' => 'required|numeric|min:1.01',
                ]);
                $customer->update([
                    'dob' => $validated['dob'],
                    'income' => $validated['income'],
                    'step' => 3,
                ]);
                break;
    
            case 3:
                $customer->update([
                    'completed_at' => now(),
                    'step' => 4
                ]);
                break;
            default:
                return response()->json([
                    'message' => 'Form already complete'
                ]);
        }
    
        return response()->json([
            'message' => 'Step updated.',
            'step'    => $customer->step,
        ]);
    }

    public function checkStatus(Request $request, $id)
    {
        $customer = AuthHelper::getAuthenticatedCustomer($request);

        if (!$customer || $customer->id != $id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        return response()->json([
            'step' => $customer->step,
            'message' => 'Form progress retrieved.',
        ]);
    }

    public function show(Request $request, $id) {
        $customer = Customer::where('id', $id)
            ->firstOrFail();

        return response()->json([
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'postcode' => $customer->postcode,
            'dob' => $customer->dob,
            'income' => $customer->income,
            'step' => $customer->step,
            'completed_at' => $customer->completed_at,
        ]);
    }

    public function delete(Request $request, $id) {

        $customer = AuthHelper::getAuthenticatedCustomer($request);

        if (!$customer || $customer->id != $id) {
            return response()->json([
                'message' => 'Unauthorized or invalid customer.'
            ], 401);
        }

        if (!$customer->completed_at) {
            $customer->delete();
            return response()->json([
                'message' => 'Partial form deleted'
            ], 200);
            
        } else {
            return response()->json([
                'message' => 'Form is complete, cannot delete'
            ], 403);
        }


    }
}
