<?php

namespace App\Virtual\Models;

/**
 * @OA\Schema(
 *     title="Product",
 *     description="Product model",
 *     @OA\Xml(name="Product")
 * )
 */
class Product
{
    /**
     * @OA\Property(
     *     title="ID",
     *     description="Product ID",
     *     format="int64",
     *     example=1
     * )
     */
    private $product_id;

    /**
     * @OA\Property(
     *     title="Category",
     *     description="Product Category",
     *     ref="#/components/schemas/ProductCategory"
     * )
     */
    private $category;

    /**
     * @OA\Property(
     *     title="Product Name",
     *     description="Name of the product",
     *     example="iPhone 15 Pro"
     * )
     */
    private $product_name;

    /**
     * @OA\Property(
     *     title="Picture",
     *     description="Product image URL",
     *     example="products/iphone15pro.jpg"
     * )
     */
    private $picture;

    /**
     * @OA\Property(
     *     title="Stock",
     *     description="Available stock",
     *     format="int32",
     *     example=50
     * )
     */
    private $stock;

    /**
     * @OA\Property(
     *     title="Price",
     *     description="Product price",
     *     format="float",
     *     example=999.99
     * )
     */
    private $price;

    /**
     * @OA\Property(
     *     title="Description",
     *     description="Product description",
     *     example="Latest iPhone with advanced features"
     * )
     */
    private $desc_product;

    /**
     * @OA\Property(
     *     title="Discount Type",
     *     description="Type of discount (percentage or fixed)",
     *     enum={"percentage", "fixed"},
     *     example="percentage"
     * )
     */
    private $discount_type;

    /**
     * @OA\Property(
     *     title="Discount Amount",
     *     description="Amount of discount",
     *     format="float",
     *     example=10
     * )
     */
    private $discount_amount;

    /**
     * @OA\Property(
     *     title="Discount Start Date",
     *     description="Start date of discount",
     *     format="datetime",
     *     example="2024-01-01T00:00:00.000000Z"
     * )
     */
    private $start_date_disc;

    /**
     * @OA\Property(
     *     title="Discount End Date",
     *     description="End date of discount",
     *     format="datetime",
     *     example="2024-02-01T00:00:00.000000Z"
     * )
     */
    private $end_date_disc;

    /**
     * @OA\Property(
     *     title="Created Date",
     *     description="Created at",
     *     format="datetime",
     *     example="2024-01-01T00:00:00.000000Z"
     * )
     */
    private $created_date;

    /**
     * @OA\Property(
     *     title="Created By",
     *     description="User who created the product",
     *     example="Admin User"
     * )
     */
    private $created_by;
} 