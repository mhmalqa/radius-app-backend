<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppSetting\UpdateAppSettingRequest;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    /**
     * Get all app settings (public - for all users).
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type'); // social_link, general_setting

        $query = AppSetting::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('key');

        if ($type) {
            $query->where('type', $type);
        }

        $settings = $query->get();

        return response()->json([
            'success' => true,
            'data' => AppSettingResource::collection($settings),
        ]);
    }

    /**
     * Get all app settings for admin (including inactive).
     */
    public function all(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AppSetting::class);

        $type = $request->query('type');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;

        $query = AppSetting::orderBy('sort_order')
            ->orderBy('key');

        if ($type) {
            $query->where('type', $type);
        }

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        $settings = $query->get();

        return response()->json([
            'success' => true,
            'data' => AppSettingResource::collection($settings),
        ]);
    }

    /**
     * Get single app setting.
     */
    public function show(Request $request, AppSetting $appSetting): JsonResponse
    {
        // Only show active settings to non-admin users
        if (!$request->user()->isAdmin() && !$appSetting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الإعداد غير متاح',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AppSettingResource($appSetting),
        ]);
    }

    /**
     * Get setting by key (public route).
     */
    public function getByKey(Request $request, string $key): JsonResponse
    {
        $setting = AppSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'الإعداد غير موجود',
            ], 404);
        }

        // Only show active settings to non-admin users
        $user = $request->user();
        if ((!$user || !$user->isAdmin()) && !$setting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الإعداد غير متاح',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AppSettingResource($setting),
        ]);
    }

    /**
     * Update app setting (Admin only).
     */
    public function update(UpdateAppSettingRequest $request, AppSetting $appSetting): JsonResponse
    {
        $this->authorize('update', $appSetting);

        $appSetting->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعداد بنجاح',
            'data' => new AppSettingResource($appSetting->fresh()),
        ]);
    }

    /**
     * Update or create multiple settings (Admin only).
     */
    public function updateMultiple(Request $request): JsonResponse
    {
        $this->authorize('create', AppSetting::class);

        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['nullable', 'string', 'max:1000'],
            'settings.*.type' => ['nullable', 'string', 'in:social_link,general_setting'],
            'settings.*.label' => ['nullable', 'string', 'max:255'],
            'settings.*.label_en' => ['nullable', 'string', 'max:255'],
            'settings.*.description' => ['nullable', 'string', 'max:500'],
            'settings.*.is_active' => ['nullable', 'boolean'],
            'settings.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $updated = [];
        foreach ($request->settings as $settingData) {
            $setting = AppSetting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => $settingData['value'] ?? null,
                    'type' => $settingData['type'] ?? 'general_setting',
                    'label' => $settingData['label'] ?? null,
                    'label_en' => $settingData['label_en'] ?? null,
                    'description' => $settingData['description'] ?? null,
                    'is_active' => $settingData['is_active'] ?? true,
                    'sort_order' => $settingData['sort_order'] ?? 0,
                ]
            );
            $updated[] = $setting;
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعدادات بنجاح',
            'data' => AppSettingResource::collection($updated),
        ]);
    }

    /**
     * Delete app setting (Admin only).
     */
    public function destroy(AppSetting $appSetting): JsonResponse
    {
        $this->authorize('delete', $appSetting);

        $appSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإعداد بنجاح',
        ]);
    }
}
