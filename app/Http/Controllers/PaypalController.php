<?php

namespace App\Http\Controllers;

use App\Models\Payment; // Use the Payment model instead of Report
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class PaypalController extends Controller
{
    private $client;

    public function __construct()
    {
        $clientId = 'AfZvGbk3_PHgf7qrLs3Yyt8Mo4thDqgN9LjAhBi4-vVFMxGkMgFujUZGzq29d7iRgy-OgnJjs-DOOaBq';
        $clientSecret = 'EOHxEeOHPoVMoM-po62qPAiKRfTTz0jumWnReEq4HoBwZoVbaV0AhzZJwW4ZIsId_PLYb6OGZxje6D2p';
        $environment = new SandboxEnvironment($clientId, $clientSecret);
        $this->client = new PayPalHttpClient($environment);
    }

    // Create PayPal Order
    public function createOrder(Request $request)
    {
        $order = new OrdersCreateRequest();
        $order->prefer('return=representation');
        $order->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => "USD",
                    "value" => $request->amount
                ]
            ]],
            "application_context" => [
                "return_url" => route('paypal.success'),
                "cancel_url" => route('paypal.cancel')
            ]
        ];

        try {
            $response = $this->client->execute($order);
            return response()->json([
                'approval_url' => collect($response->result->links)->firstWhere('rel', 'approve')->href
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Capture Payment after user returns from PayPal
    public function success(Request $request)
    {
        $token = $request->query('token');
        
        if (!$token) {
            return view('paypal.cancle', ['message' => 'Order ID missing in request.']);
        }
        
        $captureRequest = new OrdersCaptureRequest($token);
        $captureRequest->prefer('return=representation');
        
        try {
            $response = $this->client->execute($captureRequest);
            
            // Determine the status based on PayPal's response
            $paymentStatus = $response->result->status; // Get status from the response
            $status = 'completed'; // Default to 'completed'

            // Check PayPal's response status and assign the appropriate value
            if ($paymentStatus === 'PENDING') {
                $status = 'pending';  // If the payment is still pending
            } elseif ($paymentStatus === 'FAILED') {
                $status = 'failed';   // If the payment has failed
            } elseif ($paymentStatus !== 'COMPLETED') {
                $status = 'failed';   // If it's neither completed, pending, nor failed
            }

            // Get the logged-in user's ID dynamically
            $userId = Auth::id(); // Retrieve the currently logged-in user's ID

            // Save the payment details in the Payment table with dynamic user_id
            Payment::create([
                'user_id' => $userId,  // Dynamically assigned user_id
                'amount' => $response->result->purchase_units[0]->payments->captures[0]->amount->value,
                'payment_method' => 'PayPal',
                'status' => $status,  // Use the dynamically assigned status
                'paid_at' => now(),   // Set the paid_at timestamp
            ]);

            // Return appropriate view based on the status
            if ($status === 'completed') {
                return view('paypal.success', [
                    'message' => 'Payment captured successfully.',
                    'details' => $response->result
                ])->with('redirect', '/admin/pos'); // Pass the redirect URL
            } elseif ($status === 'pending') {
                return view('paypal.pending', [
                    'message' => 'Payment is pending. Please check again later.',
                    'details' => $response->result
                ])->with('redirect', '/admin/pos'); // Pass the redirect URL
            } else {
                return view('paypal.cancle', [
                    'message' => 'Payment failed.',
                    'details' => $response->result
                ])->with('redirect', '/admin/pos'); // Pass the redirect URL
            }

        } catch (\Exception $e) {
            return view('paypal.cancle', [
                'message' => 'Payment failed: ' . $e->getMessage()
            ])->with('redirect', '/admin/pos'); // Pass the redirect URL
        }
    }
    // Cancel Payment screen
   // Cancel Payment screen
    public function cancel(Request $request)
    {
        // Get the token from the cancel request
        $token = $request->query('token');
        
        if (!$token) {
            return view('paypal.cancle', ['message' => 'Order ID missing in request.']);
        }

        // Create a failed payment record in the database
        $userId = Auth::id(); // Retrieve the currently logged-in user's ID

        // Set the status to "failed" since the payment was canceled
        Payment::create([
            'user_id' => $userId,  // Dynamically assigned user_id
            'amount' => 0,  // No payment amount when canceled
            'payment_method' => 'PayPal',
            'status' => 'failed',  // Set the status as 'failed'
            'paid_at' => now(),  // Set the timestamp for when the payment was canceled
        ]);

        // Return the canceled payment view with the appropriate message
        return view('paypal.cancle', [
            'message' => 'You cancelled the payment.',
        ])->with('redirect', '/admin/pos'); // Pass the redirect URL
    }


}
