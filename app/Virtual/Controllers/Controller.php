<?php

namespace App\Virtual\Controllers;

/**
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     securityScheme="sanctum",
 *     description="Enter token in format (Bearer <token>)"
 * )
 */
class Controller
{
} 