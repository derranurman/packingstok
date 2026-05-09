<?php

namespace App\Http\Controllers\Packing;

use App\Exceptions\PackingException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackingController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();
        $me = auth()->user();

        $myPackedToday = Order::where('packed_by_user_id', $me->id)
            ->where('packed_at', '>=', $today)
            ->count();

        $queue = Order::where('status', Order::STATUS_READY)->count();

        $recent = Order::where('packed_by_user_id', $me->id)
            ->latest('packed_at')
            ->with('items.product')
            ->limit(10)
            ->get();

        return view('packing.index', compact('myPackedToday', 'queue', 'recent'));
    }

    public function scan(Request $request, PackingService $service): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
        ]);

        try {
            $result = $service->scanResi($data['tracking_number'], $request->user());
        } catch (PackingException $e) {
            return response()->json([
                'success' => false,
                'code' => $e->code_key,
                'message' => $e->getMessage(),
            ], 422);
        }

        /** @var Order $order */
        $order = $result['order'];

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dipacking.',
            'order' => [
                'id' => $order->id,
                'tiktok_order_id' => $order->tiktok_order_id,
                'tracking_number' => $order->tracking_number,
                'buyer_name' => $order->buyer_name,
                'packed_at' => $order->packed_at?->format('d/m/Y H:i'),
            ],
            'changes' => $result['changes'],
            'warnings' => $result['warnings'],
        ]);
    }
}
