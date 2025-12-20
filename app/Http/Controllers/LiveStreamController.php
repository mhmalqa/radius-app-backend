<?php

namespace App\Http\Controllers;

use App\Http\Requests\LiveStream\CreateLiveStreamRequest;
use App\Http\Resources\LiveStreamResource;
use App\Models\AppUser;
use App\Models\LiveStream;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LiveStreamController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    /**
     * Get all active live streams.
     */
    public function index(Request $request): JsonResponse
    {
        // Get user from request (works even if route is not protected by auth middleware)
        // Try to get authenticated user from Sanctum guard
        $user = $request->user() ?? auth('sanctum')->user();
        $query = LiveStream::query();


        // Check if user is admin (role = 2)
        $isAdmin = $user && $user->role === 2;



        // If user is admin, show ALL streams (active, inactive, all access types, all times)
        if ($isAdmin) {
            // Admin sees all streams - no filtering at all
            // Skip all filters for admin
        } else {

            if ($user) {
                $hasActiveSubscription = $user->subscription && $user->subscription->isActive();


                // Check live_access - ensure it's properly cast to boolean
                // live_access = 1 (true) means user has live broadcast access
                $hasLiveAccess =$user->live_access === true;

                // Only show active streams for non-admin users
                $query->where('is_active', true);

                // Filter by access type based on user permissions
                if ($hasActiveSubscription) {
                    // User has active subscription - show streams based on live_access
                    if ($hasLiveAccess) {
                        // User has live_access (live_access = 1): show both all_subscribers AND live_subscribers_only

                        $query->where(function ($q) {
                            $q->where('access_type', 'all_subscribers')
                                ->orWhereNull('access_type')
                                ->orWhere('access_type', 'live_subscribers_only');
                        });
                    } else {
                        // User doesn't have live_access (live_access = 0): show only all_subscribers
                        $query->where(function ($q) {
                            $q->where('access_type', 'all_subscribers')
                                ->orWhereNull('access_type');
                        });
                    }
                } else {
                    // If user doesn't have active subscription, show nothing
                    $query->whereRaw('1 = 0');
                }

                // Apply time-based filtering (available scope) for all streams
                // This filters streams based on start_time and end_time
                $now = now();
                $query->where(function ($q) use ($now) {
                    $q->whereNull('start_time')
                        ->orWhere('start_time', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_time')
                        ->orWhere('end_time', '>=', $now);
                });
            } else {
                // For non-authenticated users, only show streams for all subscribers (or null) and active
                $query->where(function ($q) {
                    $q->where('access_type', 'all_subscribers')
                        ->orWhereNull('access_type');
                })
                ->where('is_active', true);
            }
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter featured
        if ($request->has('featured') && $request->boolean('featured')) {
            $query->featured();
        }

        $streams = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => LiveStreamResource::collection($streams->items()),
            'meta' => [
                'current_page' => $streams->currentPage(),
                'last_page' => $streams->lastPage(),
                'per_page' => $streams->perPage(),
                'total' => $streams->total(),
            ],
        ]);
    }

    /**
     * Get single live stream.
     */
    public function show(Request $request, LiveStream $liveStream): JsonResponse
    {
        $user = $request->user();

        // If user is admin, allow viewing inactive streams
        // Otherwise, check if stream is active and available
        if (!$user || !$user->isAdmin()) {
            if (!$liveStream->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'البث غير متاح',
                ], 404);
            }
        }
        $accessType = $liveStream->access_type ?? 'all_subscribers'; // Default to all_subscribers for old records

        // Check access based on access_type
        if ($accessType === 'live_subscribers_only') {
            // Only for live subscribers - need both live_access and active subscription
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول لمشاهدة هذا البث',
                ], 401);
            }

            $hasActiveSubscription = $user->subscription && $user->subscription->isActive();
            if (!$user->live_access || !$hasActiveSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لمشاهدة هذا البث. هذا البث متاح فقط لمشتركي البث المباشر',
                ], 403);
            }
        } else {
            // For all subscribers (or null) - need active subscription
            if ($user) {
                $hasActiveSubscription = $user->subscription && $user->subscription->isActive();
                if (!$hasActiveSubscription) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب أن يكون لديك اشتراك نشط لمشاهدة هذا البث',
                    ], 403);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول لمشاهدة هذا البث',
                ], 401);
            }
        }

        // Increment view count
        $liveStream->incrementViews();

        return response()->json([
            'success' => true,
            'data' => new LiveStreamResource($liveStream),
        ]);
    }

    /**
     * Get all live streams for admin (including inactive and all access types).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LiveStream::class);

        $query = LiveStream::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by access_type
        if ($request->has('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter featured
        if ($request->has('featured') && $request->boolean('featured')) {
            $query->featured();
        }

        $streams = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => LiveStreamResource::collection($streams->items()),
            'meta' => [
                'current_page' => $streams->currentPage(),
                'last_page' => $streams->lastPage(),
                'per_page' => $streams->perPage(),
                'total' => $streams->total(),
            ],
        ]);
    }

    /**
     * Create new live stream (admin only).
     */
    public function store(CreateLiveStreamRequest $request): JsonResponse
    {
        $this->authorize('create', LiveStream::class);

        $data = $request->validated();

        // رفع الصورة المصغرة إن وُجدت وتخزين مسارها في قاعدة البيانات
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('live_stream_thumbnails', 'public');
            $data['thumbnail'] = $path;
        }

        $liveStream = LiveStream::create($data);

        // Send notification if start_time is today
        if ($liveStream->start_time && $liveStream->start_time->isToday()) {
            $this->sendStreamNotification($liveStream, 'created');
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء البث بنجاح',
            'data' => new LiveStreamResource($liveStream),
        ], 201);
    }

    /**
     * Update live stream (admin only).
     */
    public function update(CreateLiveStreamRequest $request, LiveStream $liveStream): JsonResponse
    {
        $this->authorize('update', $liveStream);

        $wasActive = $liveStream->is_active;
        $oldStartTime = $liveStream->start_time;

        // Handle thumbnail upload BEFORE validation to ensure it's processed correctly
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            // Delete old thumbnail if exists
            if ($liveStream->thumbnail && Storage::disk('public')->exists($liveStream->thumbnail)) {
                Storage::disk('public')->delete($liveStream->thumbnail);
            }
            $thumbnailPath = $request->file('thumbnail')->store('live_stream_thumbnails', 'public');
        }

        // Get all request data first (for form-data compatibility)
        $allowedFields = [
            'title', 'description', 'stream_url', 'access_type', 'category',
            'stream_type', 'is_active', 'is_featured', 'start_time', 'end_time',
            'max_viewers', 'sort_order', 'quality_options'
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
                if ($field === 'is_active' || $field === 'is_featured') {
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

        // Add thumbnail path to data if a new file was uploaded
        if ($thumbnailPath) {
            $data['thumbnail'] = $thumbnailPath;
        }

        // Remove thumbnail from data if it's not a file (to avoid overwriting with null/empty string)
        if (isset($data['thumbnail']) && !$thumbnailPath && !$request->hasFile('thumbnail')) {
            unset($data['thumbnail']);
        }

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

        $liveStream->update($updateData);
        $liveStream->refresh();

        // Send notification if stream just became active or start_time is today
        if ((!$wasActive && $liveStream->is_active) ||
            ($liveStream->start_time && $liveStream->start_time->isToday() && $liveStream->start_time != $oldStartTime)) {
            $this->sendStreamNotification($liveStream, 'started');
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البث بنجاح',
            'data' => new LiveStreamResource($liveStream),
        ]);
    }

    /**
     * Send notification about live stream.
     */
    protected function sendStreamNotification(LiveStream $liveStream, string $type): void
    {
        $userIds = [];
        $accessType = $liveStream->access_type ?? 'all_subscribers'; // Default to all_subscribers for old records

        if ($accessType === 'live_subscribers_only') {
            // Get users with live_access AND active subscription
            $userIds = AppUser::where('is_active', true)
                ->where('live_access', true)
                ->whereHas('subscription', function ($query) {
                    $query->where('expiration_at', '>', now());
                })
                ->pluck('id')
                ->toArray();
        } else {
            // Get all users with active subscription
            $userIds = AppUser::where('is_active', true)
                ->whereHas('subscription', function ($query) {
                    $query->where('expiration_at', '>', now());
                })
                ->pluck('id')
                ->toArray();
        }

        if (empty($userIds)) {
            return;
        }

        $title = $type === 'created'
            ? "📺 بث مباشر جديد: {$liveStream->title}"
            : "🔴 البث المباشر بدأ: {$liveStream->title}";

        $body = $type === 'created' && $liveStream->description
            ? $liveStream->description
            : "شاهد الآن: {$liveStream->title}";

        $this->notificationService->createNotification([
            'title' => $title,
            'body' => $body,
            'type' => 'system',
            'priority' => 1,
            'action_url' => "/live-streams/{$liveStream->id}",
            'action_text' => 'شاهد الآن',
        ], $userIds, 'specific');
    }

    /**
     * Delete live stream (admin only).
     */
    public function destroy(LiveStream $liveStream): JsonResponse
    {
        $this->authorize('delete', $liveStream);

        $liveStream->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف البث بنجاح',
        ]);
    }
}

