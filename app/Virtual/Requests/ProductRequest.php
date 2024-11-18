<?php

namespace App\Virtual\Requests;

/**
 * @OA\Schema(
 *     title="Product Request",
 *     description="Product request body data",
 *     type="object",
 *     required={"product_category_id", "product_name", "stock", "price"}
 * )
 */
class ProductRequest
{
    /**
     * @OA\Property(
     *     title="Product Category ID",
     *     description="ID of product category",
     *     format="int64",
     *     example=1
     * )
     */
    public $product_category_id;

    /**
     * @OA\Property(
     *     title="Product Name",
     *     description="Name of the product",
     *     example="iPhone 15 Pro"
     * )
     */
    public $product_name;

    /**
     * @OA\Property(
     *     title="Picture",
     *     description="Product image URL",
     *     example="products/iphone15pro.jpg"
     * )
     */
    public $picture;

    /**
     * @OA\Property(
     *     title="Stock",
     *     description="Available stock",
     *     format="int32",
     *     example=50
     * )
     */
    public $stock;

    /**
     * @OA\Property(
     *     title="Price",
     *     description="Product price",
     *     format="float",
     *     example=999.99
     * )
     */
    public $price;

    /**
     * @OA\Property(
     *     title="Description",
     *     description="Product description",
     *     example="Latest iPhone with advanced features"
     * )
     */
    public $desc_product;

    /**
     * @OA\Property(
     *     title="Discount Type",
     *     description="Type of discount",
     *     enum={"percentage", "fixed"},
     *     example="percentage"
     * )
     */
    public $discount_type;

    /**
     * @OA\Property(
     *     title="Discount Amount",
     *     description="Amount of discount",
     *     format="float",
     *     example=10
     * )
     */
    public $discount_amount;

    /**
     * @OA\Property(
     *     title="Discount Start Date",
     *     description="Start date of discount",
     *     format="datetime",
     *     example="2024-01-01T00:00:00.000000Z"
     * )
     */
    public $start_date_disc;

    /**
     * @OA\Property(
     *     title="Discount End Date",
     *     description="End date of discount",
     *     format="datetime",
     *     example="2024-02-01T00:00:00.000000Z"
     * )
     */
    public $end_date_disc;
} 