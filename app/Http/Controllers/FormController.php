<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FormController extends Controller
{
    protected function findCustomer(int $id, string $token): ?Customer {
    return Customer::where('id', $id)
                    ->where('access_token', $token)
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
        ->whereNull('completed_at')
        ->first();

        if ($existing) {
            $existing->delete();
        }
        
        $customer = Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'access_token' => bin2hex(random_bytes(32)),
            'step' => 1,
            'last_active_at' => Carbon::now()
        ]);

        return response()->json([
            'message' => 'New form started.',
            'id' => $customer->id,
            'access_token' => $customer->access_token,
            'step' => 1,
        ]);

    }

    public function updateStep(Request $request, $id)
    {
        $token = $request->bearerToken(); // Read from Authorization header
    
        $customer = $this->findCustomer($id, $token);
        if (!$customer) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
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
                    'dob'    => 'required|date',
                    'income' => 'required|numeric|min:0',
                ]);
                $customer->update([
                    'dob' => $validated['dob'],
                    'income' => $validated['income'],
                    'step' => 3,
                    'completed_at' => now(),
                ]);
                break;
    
            case 3:
                $validated = $request->validate([
                    'step' => 'required|integer|min:1|max:3'
                ]);
                $customer->update([
                    'step' => $validated['step']
                ]);
                break;
        }
    
        return response()->json([
            'message' => 'Step updated.',
            'step'    => $customer->step,
        ]);
    }

    public function checkStatus(Request $request, $id)
    {
        $token = $request->bearerToken();
    
        $customer = $this->findCustomer($id, $token);
        if (!$customer) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        return response()->json([
            'step' => $customer->step,
            'message' => 'Form progress retrieved.',
        ]);
    }
}
