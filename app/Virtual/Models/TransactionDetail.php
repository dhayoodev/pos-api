<?php

namespace App\Virtual\Models;

/**
 * @OA\Schema(
 *     title="TransactionDetail",
 *     description="Transaction Detail model",
 *     @OA\Xml(name="TransactionDetail")
 * )
 */
class TransactionDetail
{
    /**
     * @OA\Property(
     *     title="ID",
     *     description="Transaction Detail ID",
     *     format="int64",
     *     example=1
     * )
     */
    private $trans_detail_id;

    /**
     * @OA\Property(
     *     title="Product",
     *     description="Product information",
     *     ref="#/components/schemas/Product"
     * )
     */
    private $product;

    /**
     * @OA\Property(
     *     title="Quantity",
     *     description="Product quantity",
     *     format="int32",
     *     example=2
     * )
     */
    private $qty;

    /**
     * @OA\Property(
     *     title="Price",
     *     description="Product price at time of purchase",
     *     format="float",
     *     example=999.99
     * )
     */
    private $price;

    /**
     * @OA\Property(
     *     title="Subtotal",
     *     description="Subtotal for this item",
     *     format="float",
     *     example=1999.98
     * )
     */
    private $subtotal;
} 