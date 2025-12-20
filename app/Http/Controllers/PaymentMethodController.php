<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethod\CreatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PaymentMethodController extends Controller
{
    /**
     * Get all active payment methods.
     */
    public function index(): JsonResponse
    {
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PaymentMethodResource::collection($paymentMethods),
        ]);
    }

    /**
     * Create new payment method (admin only).
     */
    public function store(CreatePaymentMethodRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentMethod::class);

        $data = $request->validated();

        // Handle icon upload
        if ($request->hasFile('icon') && $request->file('icon')->isValid()) {
            $data['icon'] = $request->file('icon')->store('payment_methods', 'public');
        }

        // Handle QR code upload
        if ($request->hasFile('qr_code') && $request->file('qr_code')->isValid()) {
            $data['qr_code'] = $request->file('qr_code')->store('payment_methods', 'public');
        }

        $paymentMethod = PaymentMethod::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء طريقة الدفع بنجاح',
            'data' => new PaymentMethodResource($paymentMethod),
        ], 201);
    }

    /**
     * Update payment method (admin only).
     */
    public function update(CreatePaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->authorize('update', $paymentMethod);

        // Handle icon upload BEFORE validation
        $iconPath = null;
        if ($request->hasFile('icon') && $request->file('icon')->isValid()) {
            // Delete old icon if exists
            if ($paymentMethod->icon && Storage::disk('public')->exists($paymentMethod->icon)) {
                Storage::disk('public')->delete($paymentMethod->icon);
            }
            $iconPath = $request->file('icon')->store('payment_methods', 'public');
        }

        // Handle QR code upload BEFORE validation
        $qrCodePath = null;
        if ($request->hasFile('qr_code') && $request->file('qr_code')->isValid()) {
            // Delete old QR code if exists
            if ($paymentMethod->qr_code && Storage::disk('public')->exists($paymentMethod->qr_code)) {
                Storage::disk('public')->delete($paymentMethod->qr_code);
            }
            $qrCodePath = $request->file('qr_code')->store('payment_methods', 'public');
        }

        // Get all request data first (for form-data compatibility)
        $allowedFields = [
            'name', 'name_ar', 'code', 'is_active', 'instructions', 'sort_order'
        ];
        
        // Get data from request (works with both JSON and form-data)
        $allInput = $request->all();
        $requestData = [];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $allInput)) {
                $value = $request->input($field);
                
                // Convert string booleans to actual booleans
                if ($field === 'is_active') {
                    if (is_string($value)) {
                        $value = in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
                    } elseif ($value === null) {
                        continue;
                    }
                    $requestData[$field] = (bool) $value;
                } elseif ($value !== null && $value !== '') {
                    $requestData[$field] = $value;
                }
            }
        }
        
        // Get validated data and merge
        $validatedData = $request->validated();
        $data = array_merge($requestData, $validatedData);

        // Add image paths if new files were uploaded
        if ($iconPath) {
            $data['icon'] = $iconPath;
        }

        if ($qrCodePath) {
            $data['qr_code'] = $qrCodePath;
        }

        // Remove image fields from data if they're not files (to avoid overwriting with null/empty string)
        if (isset($data['icon']) && !$iconPath && !$request->hasFile('icon')) {
            unset($data['icon']);
        }

        if (isset($data['qr_code']) && !$qrCodePath && !$request->hasFile('qr_code')) {
            unset($data['qr_code']);
        }

        // Only update with fields that have actual values
        $updateData = [];
        foreach ($data as $key => $value) {
            if ($value === false || $value === 0 || $value === '0') {
                $updateData[$key] = $value;
            } elseif ($value !== null && $value !== '') {
                $updateData[$key] = $value;
            }
        }

        $paymentMethod->update($updateData);
        $paymentMethod->refresh();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث طريقة الدفع بنجاح',
            'data' => new PaymentMethodResource($paymentMethod),
        ]);
    }

    /**
     * Delete payment method (admin only).
     */
    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $this->authorize('delete', $paymentMethod);

        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف طريقة الدفع بنجاح',
        ]);
    }
}

