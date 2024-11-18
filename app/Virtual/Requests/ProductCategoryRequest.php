<?php

namespace App\Virtual\Requests;

/**
 * @OA\Schema(
 *     title="Product Category Request",
 *     description="Product Category request body data",
 *     type="object",
 *     required={"category_name"}
 * )
 */
class ProductCategoryRequest
{
    /**
     * @OA\Property(
     *     title="category_name",
     *     description="Name of the category",
     *     example="Electronics"
     * )
     */
    public $category_name;
} 