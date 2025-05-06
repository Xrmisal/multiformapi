<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FormController extends Controller
{
    public function start(Request $request) {
        $data = $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string',
            'name' => 'required|string',
        ]);

        $existing = Customer::where(function ($query) use ($data) {
            $query->where('email', $data['email'])
                ->orWhere('phone', $data['phone']);
        })->first();

        if ($existing) {
            if ($existing->completed_at) {
                return response()->json([
                    'error' => 'This email/phone number is already in use',
                ], 409);
            }
            return response()->json([
                'message' => 'Resuming existing form.',
                'id' => $existing->id,
                'step' => $existing->step,
            ]);
        }


        $customer = Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'step' => 1,
            'last_active_at' => Carbon::now()
        ]);

        return response()->json([
            'message' => 'New form started.',
            'id' => $customer->id,
            'step' => 1,
        ]);

    }

    public function updateStep(Request $request, $id) {
        $customer = Customer::findOrFail($id);

        $step = (int) $request->input('step', $customer->step);

        switch ($step) {
            case 1:
                $validated = $request->validate([
                    'address' => 'required|string',
                    'postcode' => 'required|string',
                ]);
                $customer->update([
                    'address' => $validated['address'],
                    'postcode' => $validated['postcode'],
                    'step' => 2,
                    'last_active_at' => now()
                ]);
                break;
            case 2:
                $validated = $request->validate([
                    'dob' => 'required|date',
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
                return response()->json([
                    'message' => 'Form is already completed.',
                    'step' => $customer->step,
                ], 409);
            default:
                return response()->json([
                    'error' => 'Invalid step progression.',
                ], 400);
        }

        return response()->json([
            'message' => "Step {$customer->step} completed.",
            'step' => $customer->step,
        ]);
    }

    public function checkStatus($id) {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'error' => 'Form not found.'
            ], 404);
        }

        if ($customer->completed_at) {
            return response()->json([
                'error' => 'Form already completed.'
            ], 409);
        }

        return response()->json([
            'id' => $customer->id,
            'step' => $customer->step
        ]);
    }
}
