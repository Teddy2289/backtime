<?php

namespace Modules\TaskFiles\Presentation\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Modules\TaskFiles\Application\Services\TaskFileService;
use Modules\Task\Application\Services\TaskService;

class TaskFilesController extends Controller
{
    use ApiResponser;

    protected TaskFileService $fileService;
    protected TaskService $taskService;

    public function __construct(TaskFileService $fileService, TaskService $taskService)
    {
        $this->fileService = $fileService;
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of files.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['task_id', 'uploaded_by', 'type', 'extension', 'search', 'order_by', 'order_direction']);

            $files = $this->fileService->getPaginatedFiles($perPage, $filters);

            return $this->paginatedResponse($files, 'Files retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve files',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Upload files to a task.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|integer|exists:tasks,id',
                'files' => 'required|array|min:1|max:10',
                'files.*' => 'file|max:10240', // 10MB max
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $taskId = $request->input('task_id');
            $userId = auth()->id();
            $description = $request->input('description');

            // Vérifier les permissions
            if (!$this->fileService->canUserUploadToTask($userId, $taskId)) {
                return $this->unauthorizedResponse('You are not authorized to upload files to this task');
            }

            $uploadedFiles = new \Illuminate\Support\Collection();
            $errors = [];

            /** @var UploadedFile $file */
            foreach ($request->file('files') as $file) {
                // Valider chaque fichier
                $validationErrors = $this->fileService->validateFile($file);

                if (!empty($validationErrors)) {
                    $errors[$file->getClientOriginalName()] = $validationErrors;
                    continue;
                }

                try {
                    $uploadedFile = $this->fileService->uploadFile($taskId, $file, $userId, $description);
                    $uploadedFiles->push($uploadedFile);
                } catch (\Exception $e) {
                    $errors[$file->getClientOriginalName()] = ['Failed to upload file'];
                }
            }

            if ($uploadedFiles->isEmpty() && !empty($errors)) {
                return $this->errorResponse(
                    'Failed to upload files',
                    ['errors' => $errors],
                    400
                );
            }

            $response = [
                'uploaded_files' => $uploadedFiles,
                'total_uploaded' => $uploadedFiles->count(),
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
                $response['message'] = 'Some files failed to upload';

                return $this->successResponse(
                    $response,
                    'Files uploaded with some errors',
                    207 // 207 Multi-Status
                );
            }

            return $this->successResponse(
                $response,
                'Files uploaded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to upload files',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Display the specified file.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $file = $this->fileService->getFileById($id);

            if (!$file) {
                return $this->notFoundResponse('File not found');
            }

            return $this->successResponse(
                $file,
                'File retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve file',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update the specified file's description.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'description' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier les permissions
            if (!$this->fileService->canUserModifyFile(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to update this file');
            }

            $file = $this->fileService->updateFileDescription($id, $request->input('description'));

            if (!$file) {
                return $this->notFoundResponse('File not found');
            }

            return $this->successResponse(
                $file,
                'File description updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update file',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove the specified file.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Vérifier les permissions
            if (!$this->fileService->canUserModifyFile(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to delete this file');
            }

            $deleted = $this->fileService->deleteFile($id);

            if (!$deleted) {
                return $this->errorResponse('Failed to delete file', null, 500);
            }

            return $this->successResponse(
                null,
                'File deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete file',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove multiple files.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_ids' => 'required|array|min:1',
                'file_ids.*' => 'integer|exists:task_files,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $fileIds = $request->input('file_ids');

            // Vérifier les permissions pour chaque fichier
            foreach ($fileIds as $fileId) {
                if (!$this->fileService->canUserModifyFile(auth()->id(), $fileId)) {
                    return $this->unauthorizedResponse('You are not authorized to delete one or more files');
                }
            }

            $deleted = $this->fileService->deleteFiles($fileIds);

            if (!$deleted) {
                return $this->errorResponse('Failed to delete files', null, 500);
            }

            return $this->successResponse(
                null,
                'Files deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete files',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Download the specified file.
     */
    public function download(int $id): JsonResponse
    {
        try {
            $file = $this->fileService->getFileById($id);

            if (!$file) {
                return $this->notFoundResponse('File not found');
            }

            $download = $this->fileService->downloadFile($id);

            if (!$download) {
                return $this->errorResponse('File not found on server', null, 404);
            }

            // Note: Pour télécharger un fichier, vous devrez utiliser une route qui retourne
            // une réponse de téléchargement directement. Cette méthode retourne une réponse JSON
            // avec l'URL de téléchargement.

            return $this->successResponse(
                [
                    'download_url' => route('task.files.download', ['id' => $id]),
                    'file_name' => $file->file_name,
                    'file_size' => $file->formatted_size,
                ],
                'Download URL generated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to download file',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get files by task.
     */
    public function getByTask(int $taskId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['type', 'extension', 'search', 'order_by', 'order_direction']);

            $files = $this->fileService->getPaginatedFilesByTask($taskId, $perPage, $filters);

            return $this->paginatedResponse($files, 'Task files retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve task files',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get files by uploader.
     */
    public function getByUploader(int $userId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['task_id', 'type', 'search', 'order_by', 'order_direction']);

            $files = $this->fileService->getPaginatedFilesByUploader($userId, $perPage, $filters);

            return $this->paginatedResponse($files, 'Uploader files retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve uploader files',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get file statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $taskId = $request->get('task_id');
            $userId = $request->get('user_id');

            $stats = $this->fileService->getFileStatistics($taskId, $userId);

            return $this->successResponse(
                $stats,
                'File statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve file statistics',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get file preview URL.
     */
    public function preview(int $id): JsonResponse
    {
        try {
            $file = $this->fileService->getFileById($id);

            if (!$file) {
                return $this->notFoundResponse('File not found');
            }

            $previewUrl = $this->fileService->getPreviewUrl($id);

            if (!$previewUrl) {
                return $this->errorResponse('Preview not available for this file type', null, 400);
            }

            return $this->successResponse(
                [
                    'preview_url' => $previewUrl,
                    'file_name' => $file->file_name,
                    'is_image' => $file->isImage(),
                ],
                'Preview URL generated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to get preview URL',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Search files.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $filters = $request->only(['task_id', 'uploaded_by', 'type']);
            $files = $this->fileService->searchFiles($request->input('query'), $filters);

            return $this->paginatedResponse($files, 'Files search completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to search files',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'ok',
            'module' => 'TaskFile',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ], 'Task file module is healthy');
    }
}