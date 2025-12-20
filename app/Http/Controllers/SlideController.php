<?php

namespace App\Http\Controllers;

use App\Http\Requests\Slide\CreateSlideRequest;
use App\Http\Resources\SlideResource;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SlideController extends Controller
{
    /**
     * Get all active slides.
     */
    public function index(Request $request): JsonResponse
    {
        // Get user from request (works even if route is not protected by auth middleware)
        // Try to get authenticated user from Sanctum guard
        $user = $request->user() ?? auth('sanctum')->user();
        $query = Slide::query();

        // If user is admin, show all slides (active and inactive)
        // Otherwise, show only available (active) slides
        if ($user && $user->isAdmin()) {
            // Admin sees all slides
        } else {
            $query->available();
        }

        // Filter by target audience
        if ($request->has('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }

        $slides = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SlideResource::collection($slides),
        ]);
    }

    /**
     * Get all slides for admin (including inactive).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Slide::class);

        $query = Slide::query();

        // Filter by target audience
        if ($request->has('target_audience')) {
            $query->where('target_audience', $request->target_audience);
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $slides = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => SlideResource::collection($slides->items()),
            'meta' => [
                'current_page' => $slides->currentPage(),
                'last_page' => $slides->lastPage(),
                'per_page' => $slides->perPage(),
                'total' => $slides->total(),
            ],
        ]);
    }

    /**
     * Track slide click.
     */
    public function trackClick(Slide $slide): JsonResponse
    {
        $slide->incrementClicks();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل النقرة',
        ]);
    }

    /**
     * Create new slide (admin only).
     */
    public function store(CreateSlideRequest $request): JsonResponse
    {
        $this->authorize('create', Slide::class);

        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('slides', 'public');
        }

        // Handle mobile image upload
        if ($request->hasFile('image_mobile')) {
            $data['image_mobile'] = $request->file('image_mobile')->store('slides', 'public');
        }

        // Handle desktop image upload
        if ($request->hasFile('image_desktop')) {
            $data['image_desktop'] = $request->file('image_desktop')->store('slides', 'public');
        }

        // Remove image from data array (we stored it as image_path)
        unset($data['image']);

        $slide = Slide::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء السلايد بنجاح',
            'data' => new SlideResource($slide),
        ], 201);
    }

    /**
     * Update slide (admin only).
     */
    public function update(CreateSlideRequest $request, Slide $slide): JsonResponse
    {
        $this->authorize('update', $slide);

        // Handle image uploads BEFORE validation to ensure they're processed correctly
        $imagePath = null;
        $imageMobilePath = null;
        $imageDesktopPath = null;

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Delete old image if exists
            if ($slide->image_path && Storage::disk('public')->exists($slide->image_path)) {
                Storage::disk('public')->delete($slide->image_path);
            }
            $imagePath = $request->file('image')->store('slides', 'public');
        }

        if ($request->hasFile('image_mobile') && $request->file('image_mobile')->isValid()) {
            // Delete old image if exists
            if ($slide->image_mobile && Storage::disk('public')->exists($slide->image_mobile)) {
                Storage::disk('public')->delete($slide->image_mobile);
            }
            $imageMobilePath = $request->file('image_mobile')->store('slides', 'public');
        }

        if ($request->hasFile('image_desktop') && $request->file('image_desktop')->isValid()) {
            // Delete old image if exists
            if ($slide->image_desktop && Storage::disk('public')->exists($slide->image_desktop)) {
                Storage::disk('public')->delete($slide->image_desktop);
            }
            $imageDesktopPath = $request->file('image_desktop')->store('slides', 'public');
        }

        // Get all request data first (for form-data compatibility)
        $allowedFields = [
            'title', 'link_url', 'is_active', 'target_audience', 
            'sort_order', 'start_at', 'end_at'
        ];
        
        // Get data from request (works with both JSON and form-data)
        // Use all() to get all input data, then filter by allowed fields
        $allInput = $request->all();
        $requestData = [];
        
        foreach ($allowedFields as $field) {
            // Check if field exists in input (works better with form-data)
            if (array_key_exists($field, $allInput)) {
                $value = $request->input($field);
                
                // Convert string booleans to actual booleans
                if ($field === 'is_active') {
                    // Handle various boolean representations
                    if (is_string($value)) {
                        $value = in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
                    } elseif ($value === null) {
                        continue; // Skip null values for booleans
                    }
                    $requestData[$field] = (bool) $value;
                } elseif ($value !== null && $value !== '') {
                    $requestData[$field] = $value;
                }
            }
        }
        
        // Get validated data and merge (validated takes precedence for type safety)
        $validatedData = $request->validated();
        $data = array_merge($requestData, $validatedData);

        // Add image paths to data if new files were uploaded
        if ($imagePath) {
            $data['image_path'] = $imagePath;
        }

        if ($imageMobilePath) {
            $data['image_mobile'] = $imageMobilePath;
        }

        if ($imageDesktopPath) {
            $data['image_desktop'] = $imageDesktopPath;
        }

        // Remove image from data array (we stored it as image_path)
        unset($data['image']);

        // Only update with fields that have actual values (not empty strings)
        // But keep false, 0, and other valid falsy values
        $updateData = [];
        foreach ($data as $key => $value) {
            // Keep boolean false, integer 0, and string '0'
            if ($value === false || $value === 0 || $value === '0') {
                $updateData[$key] = $value;
            } elseif ($value !== null && $value !== '') {
                $updateData[$key] = $value;
            }
        }

        $slide->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث السلايد بنجاح',
            'data' => new SlideResource($slide),
        ]);
    }

    /**
     * Delete slide (admin only).
     */
    public function destroy(Slide $slide): JsonResponse
    {
        $this->authorize('delete', $slide);

        // Delete associated images
        if ($slide->image_path && Storage::disk('public')->exists($slide->image_path)) {
            Storage::disk('public')->delete($slide->image_path);
        }
        if ($slide->image_mobile && Storage::disk('public')->exists($slide->image_mobile)) {
            Storage::disk('public')->delete($slide->image_mobile);
        }
        if ($slide->image_desktop && Storage::disk('public')->exists($slide->image_desktop)) {
            Storage::disk('public')->delete($slide->image_desktop);
        }

        $slide->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف السلايد بنجاح',
        ]);
    }
}

