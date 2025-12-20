<?php

namespace App\Http\Controllers;

use App\Http\Resources\DatabaseBackupResource;
use App\Models\DatabaseBackup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DatabaseBackupController extends Controller
{
    /**
     * Get all backups.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        $perPage = $request->get('per_page', 20);
        $type = $request->get('type'); // manual, automatic
        $status = $request->get('status'); // pending, completed, failed

        $query = DatabaseBackup::orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $backups = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DatabaseBackupResource::collection($backups->items()),
            'meta' => [
                'current_page' => $backups->currentPage(),
                'last_page' => $backups->lastPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
            ],
        ]);
    }

    /**
     * Create manual backup.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        try {
            $backup = $this->createBackup('manual');

            return response()->json([
                'success' => true,
                'message' => 'تم بدء إنشاء النسخة الاحتياطية',
                'data' => new DatabaseBackupResource($backup->refresh()),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create backup', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء النسخة الاحتياطية',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download backup (requires password confirmation).
     */
    public function download(Request $request, DatabaseBackup $backup): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        // Validate password
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Verify password
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        // Check if backup file exists
        if (!Storage::disk('local')->exists($backup->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'ملف النسخة الاحتياطية غير موجود',
            ], 404);
        }

        // Check if backup is completed
        if ($backup->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'النسخة الاحتياطية غير جاهزة للتحميل',
            ], 400);
        }

        // Return download URL (temporary signed URL)
        $downloadUrl = Storage::disk('local')->temporaryUrl(
            $backup->file_path,
            now()->addMinutes(5)
        );

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق من كلمة المرور بنجاح',
            'data' => [
                'download_url' => $downloadUrl,
                'filename' => $backup->filename,
                'expires_at' => now()->addMinutes(5)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Set automatic backup schedule.
     */
    public function setSchedule(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        $request->validate([
            'schedule' => ['required', 'string', 'in:daily,weekly,monthly'],
            'time' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'], // HH:MM format
        ]);

        try {
            // Save schedule to app_settings or config
            // For now, we'll use app_settings
            $scheduleKey = 'backup_schedule';
            $timeKey = 'backup_time';

            DB::table('app_settings')->updateOrInsert(
                ['key' => $scheduleKey],
                [
                    'value' => $request->schedule,
                    'type' => 'general_setting',
                    'label' => 'جدولة النسخ الاحتياطي',
                    'label_en' => 'Backup Schedule',
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );

            DB::table('app_settings')->updateOrInsert(
                ['key' => $timeKey],
                [
                    'value' => $request->time,
                    'type' => 'general_setting',
                    'label' => 'وقت النسخ الاحتياطي',
                    'label_en' => 'Backup Time',
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين جدولة النسخ الاحتياطي بنجاح',
                'data' => [
                    'schedule' => $request->schedule,
                    'time' => $request->time,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to set backup schedule', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تعيين الجدولة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get backup schedule.
     */
    public function getSchedule(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        $schedule = DB::table('app_settings')
            ->where('key', 'backup_schedule')
            ->value('value');

        $time = DB::table('app_settings')
            ->where('key', 'backup_time')
            ->value('value');

        return response()->json([
            'success' => true,
            'data' => [
                'schedule' => $schedule ?? null,
                'time' => $time ?? null,
            ],
        ]);
    }

    /**
     * Delete backup.
     */
    public function destroy(DatabaseBackup $backup): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AppUser::class);

        try {
            // Delete file
            if (Storage::disk('local')->exists($backup->file_path)) {
                Storage::disk('local')->delete($backup->file_path);
            }

            // Delete record
            $backup->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف النسخة الاحتياطية بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في حذف النسخة الاحتياطية',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create backup file.
     */
    private function createBackup(string $type): DatabaseBackup
    {
        $databaseName = DB::connection()->getDatabaseName();
        $filename = 'backup_' . $databaseName . '_' . date('Y-m-d_His') . '.sql';
        $backupDir = 'backups';
        $filePath = $backupDir . '/' . $filename;

        // Create backup record
        $backup = DatabaseBackup::create([
            'filename' => $filename,
            'file_path' => $filePath,
            'type' => $type,
            'status' => 'pending',
            'backup_date' => now(),
        ]);

        // Run backup command (synchronously for now, can be queued later)
        try {
            Artisan::call('db:backup', [
                '--backup-id' => $backup->id,
            ]);
            
            // Refresh backup to get updated status
            $backup->refresh();
        } catch (\Exception $e) {
            Log::error('Failed to run backup command', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $backup;
    }
}
