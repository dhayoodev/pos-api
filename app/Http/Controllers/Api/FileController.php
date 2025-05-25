<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @OA\Tag(
 *     name="Files",
 *     description="API Endpoints for file operations"
 * )
 */
class FileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/files/{path}",
     *     summary="Get file by path",
     *     tags={"Files"},
     *     @OA\Parameter(
     *         name="path",
     *         in="path",
     *         description="File path relative to storage",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File stream",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="File not found"
     *     ),
     *     security={{}}
     * )
     */
    public function show(string $path): StreamedResponse
    {
        if (!Storage::disk('public')->exists($path)) {
            throw new NotFoundHttpException('File not found');
        }

        return Storage::disk('public')->response($path);
    }
}