<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\Waybill;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WaybillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Mengambil data
            $waybills = Waybill::with(['manifests' => function ($query) {
                $query->orderBy('date_manifest', 'desc')->take(1);
            }])->get();

            //jika tidak ada data
            if ($waybills->isEmpty()) {
                return response()->json(['message' => 'Tidak ada data waybill yang tersedia.']);
            }

            //response data
            $waybill[] = [
                'message' => 'success',
                'data' => $waybills,
            ];

            return response()->json(compact('waybill'), 200);
        } catch (QueryException $e) {
            // Masalah di database
            return response()->json(['error' => 'Terjadi kesalahan saat memproses data.'], 500);
        } catch (\Exception $e) {
            // Tangani pengecualian umum lainnya
            return response()->json(['error' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'waybill_id' => 'required',
            'courier_code' => 'required',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
        try {
            $waybillId = $request->input('waybill_id');
            $courierCode = $request->input('courier_code');

            $baseURL = 'https://api.biteship.com';
            $apiKey = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiS29kZXJlc2kiLCJ1c2VySWQiOiI2NWY5YWIyZDI3MmNhODAwMTE1NGNlM2YiLCJpYXQiOjE3MTA5OTUwNjR9.pErJQ1rdJ0bhkf6q45YfGZgdNu7Ij9vh3dFZH3B8CCw';

            $client = new Client(['base_uri' => $baseURL]);

            $response = $client->get("/v1/trackings/{$waybillId}/couriers/{$courierCode}", [
                'headers' => [
                    'Authorization' => $apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['success']) && $data['success'] === false) {
                return response()->json(['error' => $data['message']], 500);
            }

            $waybill = Waybill::create([
                'waybill' => $data['waybill_id'],
                'courier' => $data['courier']['company'],
                'origin' => $data['origin']['contact_name'],
                'destination' => $data['destination']['contact_name'],
                'origin_address' => $data['origin']['address'],
                'destination_address' => $data['destination']['address'],
                'status' => $data['status'],
                'status_loop' => 'none'
            ]);

            foreach ($data['history'] as $history) {
                $updatedAt = Carbon::parse($history['updated_at']);

                Manifest::create([
                    'note' => $history['note'],
                    'status' => $history['status'],
                    'waybill_id' => $waybill->id,
                    'date_manifest' => $updatedAt
                ]);
            }

            return response()->json(['message' => 'Data waybill dan manifest berhasil disimpan.']);
        } catch (ClientException $e) {
            // Tangani kesalahan jika request ke API gagal
            $errorResponse = json_decode($e->getResponse()->getBody(), true);
            $errorMessage = isset($errorResponse['error']) ? $errorResponse['error'] : 'Unknown error occurred.';
            return response()->json(['error' => $errorMessage], 400);
        } catch (\Exception $e) {
            // Tangani kesalahan umum
            return response()->json(['error' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = Waybill::findOrFail($id)->with(['manifests' => function ($query) {
            $query->orderBy('date_manifest', 'desc');
        }])->get();;

        return response()->json(compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $waybill = Waybill::findOrFail($id)->with(['manifests' => function ($query) {
            $query->orderBy('date_manifest', 'desc');
        }])->first();;

        $countManifest = $waybill->manifests->count();
        // dd($countManifest);
        $waybillId = $waybill->waybill;
        $courierCode = $waybill->courier;
        // dd($waybillID, $courierCode);

        $baseURL = 'https://api.biteship.com';
        $apiKey = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiS29kZXJlc2kiLCJ1c2VySWQiOiI2NWY5YWIyZDI3MmNhODAwMTE1NGNlM2YiLCJpYXQiOjE3MTA5OTUwNjR9.pErJQ1rdJ0bhkf6q45YfGZgdNu7Ij9vh3dFZH3B8CCw';

        $client = new Client(['base_uri' => $baseURL]);

        $response = $client->get("/v1/trackings/{$waybillId}/couriers/{$courierCode}", [
            'headers' => [
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $countHistory = count($data['history']);
        // dd($countHistory);

        // if ($countManifest == $countHistory) {
        //     return response()->json(['message' => 'Data waybill dan manifest paling ter-update.'], 200);
        // }

        $newestHistory = null;
        $newestUpdatedAt = null;

        foreach ($data['history'] as $history) {
            $updatedAt = strtotime($history['updated_at']);
            if ($newestUpdatedAt === null || $updatedAt > $newestUpdatedAt) {
                $newestHistory = $history;
                $newestUpdatedAt = $updatedAt;
            }
        }

        if ($newestHistory) {
            $newestHistory['updated_at'] = date('Y-m-d H:i:s', strtotime($newestHistory['updated_at']));
        }

        // Buat response JSON
        $response = [
            "success" => true,
            "message" => "Terdapat update baru pada data waybill dan manifest.",
            "id" => $data['id'],
            "waybill_id" => $data['waybill_id'],
            "origin" => $data['origin'],
            "destination" => $data['destination'],
            "newest_history" => $newestHistory,
            "order_id" => $data['order_id'],
            "status" => $data['status']
        ];

        return response()->json(compact('response'));
    }

    public function updateManifest(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required',
            'status' => 'required',
            'date_manifest' => 'required|date',
            'waybill_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            $manifest = new Manifest(); // Create a new instance
            $manifest->waybill_id = $id;
            $manifest->note = $request->note;
            $manifest->status = $request->status;
            $manifest->date_manifest = $request->date_manifest;
            $manifest->save();

            return response()->json(['message' => 'Manifest created successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create manifest: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            //Data Waybill'
            'waybill' => 'required',
            'courier' => 'required',
            'origin' => 'required',
            'destination' => 'required',
            'origin_address' => 'required',
            'destination_address' => 'required',
            'status' => 'required',
            'user_id' => 'nullable',
            'status_loop' => 'required',

            //Data Manifest
            'manifests.*.note' => 'required',
            'manifests.*.status' => 'required',
            'manifests.*.date_manifest' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            // Lakukan pembaruan data waybill
            $waybill = Waybill::findOrFail($id);
            $waybill->update([
                'waybill' => $request->input('waybill'),
                'courier' => $request->input('courier'),
                'origin' => $request->input('origin'),
                'destination' => $request->input('destination'),
                'origin_address' => $request->input('origin_address'),
                'destination_address' => $request->input('destination_address'),
                'status' => $request->input('status'),
                'user_id' => $request->input('user_id'),
                'status_loop' => $request->input('status_loop'),
            ]);

            // Lakukan pembaruan data manifest
            if ($request->has('manifests')) {
                foreach ($request->input('manifests') as $manifestData) {
                    // Cek apakah manifest sudah ada, jika ya, lakukan update, jika tidak, lakukan create
                    if (isset($manifestData['id'])) {
                        $manifest = Manifest::findOrFail($manifestData['id']);
                        $manifest->update([
                            'note' => $manifestData['note'],
                            'status' => $manifestData['status'],
                            'date_manifest' => $manifestData['date_manifest'],
                        ]);
                    } else {
                        Manifest::create([
                            'note' => $manifestData['note'],
                            'status' => $manifestData['status'],
                            'date_manifest' => $manifestData['date_manifest'],
                            'waybill_id' => $id,
                        ]);
                    }
                }
            }

            return response()->json(['message' => 'Data berhasil diperbarui.'], 200);
        } catch (\Exception $e) {
            // Tangani kesalahan saat memperbarui data
            return response()->json(['error' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Cari waybill berdasarkan ID
            $waybill = Waybill::findOrFail($id);

            // Hapus semua manifest terkait dengan waybill ini
            $waybill->manifests()->delete();

            // Hapus waybill itu sendiri
            $waybill->delete();

            return response()->json(['message' => 'Data waybill berhasil dihapus.'], 200);
        } catch (\Exception $e) {
            // Tangani kesalahan saat menghapus data
            return response()->json(['error' => 'Terjadi kesalahan saat menghapus data waybill.'], 500);
        }
    }
}
