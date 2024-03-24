<?php

namespace App\Http\Controllers;

use App\Models\Waybill;
use Illuminate\Http\Request;

class WaybillFilterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function indexDelivered()
    {
        try {
            $waybills = Waybill::where('status', 'delivered')
                ->with(['manifests' => function ($query) {
                    $query->orderBy('date_manifest', 'desc')->take(1);
                }])
                ->get();

            if ($waybills->isEmpty()) {
                return response()->json(['message' => 'No delivered waybills found.'], 404);
            }

            return response()->json($waybills, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch delivered waybills: ' . $e->getMessage()], 500);
        }
    }

    public function indexOnProgress()
    {
        try {
            $waybills = Waybill::where('status', '!=', ['delivered', 'return', 'reject'])
                ->with(['manifests' => function ($query) {
                    $query->orderBy('date_manifest', 'desc')->take(1);
                }])
                ->get();

            if ($waybills->isEmpty()) {
                return response()->json(['message' => 'No waybills on progress found.'], 404);
            }

            return response()->json($waybills, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch waybills on progress: ' . $e->getMessage()], 500);
        }
    }

    public function indexReturnReject()
    {
        try {
            $waybills = Waybill::whereIn('status', ['return', 'reject'])
                ->with(['manifests' => function ($query) {
                    $query->orderBy('date_manifest', 'desc')->take(1);
                }])
                ->get();

            if ($waybills->isEmpty()) {
                return response()->json(['message' => 'No waybills with return or reject status found.'], 404);
            }

            return response()->json($waybills, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch waybills with return or reject status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */

}
