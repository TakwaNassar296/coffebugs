<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Models\Branch;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;

class OrderController extends Controller
{
    use ApiResponse;

  public function orders(Request $request)
{
    $branchId = Auth::user()->branch_id;

    $status = $request->query('status'); 

    $ordersQuery = Order::whereHas('branch', function($query) use ($branchId) {
        $query->where('id', $branchId); 
    });
    

   
    if ($status) {
        $ordersQuery->where('status', $status);
    }

    $orders = $ordersQuery->paginate(10);

    return $this->PaginationResponse(
        OrderResource::collection($orders),
        'Orders retrieved successfully.',
        200
    );
}


    public function changeStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|in:pending,under_receipt,under_review,in_preparation,prepared,shipped,arrived,completed,canceled,wasted',
            'cancelled_reason' => 'required_if:status,canceled,wasted|nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        $user = auth()->user();
      
        

        $order->update([
            'status' => $request->status,
            'cancelled_reason' => in_array($request->status, ['canceled', 'wasted']) 
                ? $request->cancelled_reason 
                : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => [
                'order_id' => $order->id,
                'invoice_id' => $order->invoice_id,
                'status' => $order->status,
                'cancelled_reason' => $order->cancelled_reason,
            ]
        ]);
    }

    public function show($id)
    {
        $branchId = Auth::user()->branch_id;
        $order = Order::where('id', $id)
            ->where('branch_id', $branchId)
            ->first();

        if (!$order) {
            return $this->errorResponse('Order Not Found', null, 404);
        }

        $qrUrl = url('api/orders/verify') . '?' . http_build_query([
            'token' => $order->qr_token,
            'order_id' => $order->id,
        ]);

        $qrCode = new QrCode(
            data: $qrUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new PngWriter;
        $qrDataUri = $writer->write($qrCode)->getDataUri();

        return $this->successResponse('Order retrieved successfully.', [
            'order' => new OrderResource($order),
            'qr_url' => $qrUrl,
            'qr_data_uri' => $qrDataUri,
        ]);
    }

}
