<?php

namespace App\Virtual\Models;

/**
 * @OA\Schema(
 *     title="ProductCategory",
 *     description="Product Category model",
 *     @OA\Xml(name="ProductCategory")
 * )
 */
class ProductCategory
{
    /**
     * @OA\Property(
     *     title="ID",
     *     description="Product Category ID",
     *     format="int64",
     *     example=1
     * )
     */
    private $product_category_id;

    /**
     * @OA\Property(
     *     title="Category Name",
     *     description="Name of the category",
     *     example="Electronics"
     * )
     */
    private $category_name;

    /**
     * @OA\Property(
     *     title="Created at",
     *     description="Created at",
     *     example="2024-01-01T00:00:00.000000Z",
     *     format="datetime"
     * )
     */
    private $created_at;

    /**
     * @OA\Property(
     *     title="Updated at",
     *     description="Updated at",
     *     example="2024-01-01T00:00:00.000000Z",
     *     format="datetime"
     * )
     */
    private $updated_at;
} 