<?php

namespace App\Virtual\Models;

/**
 * @OA\Schema(
 *     title="Transaction",
 *     description="Transaction model",
 *     @OA\Xml(name="Transaction")
 * )
 */
class Transaction
{
    /**
     * @OA\Property(
     *     title="ID",
     *     description="Transaction ID",
     *     format="int64",
     *     example=1
     * )
     */
    private $trans_id;

    /**
     * @OA\Property(
     *     title="User ID",
     *     description="ID of user who made the transaction",
     *     format="int64",
     *     example=1
     * )
     */
    private $user_id;

    /**
     * @OA\Property(
     *     title="Date",
     *     description="Transaction date",
     *     format="date",
     *     example="2024-01-01"
     * )
     */
    private $date;

    /**
     * @OA\Property(
     *     title="Total Price",
     *     description="Total transaction price",
     *     format="float",
     *     example=1499.99
     * )
     */
    private $total_price;

    /**
     * @OA\Property(
     *     title="Payment Status",
     *     description="Status of payment",
     *     enum={"pending", "paid", "failed", "refunded"},
     *     example="paid"
     * )
     */
    private $payment_status;

    /**
     * @OA\Property(
     *     title="Details",
     *     description="Transaction details",
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/TransactionDetail")
     * )
     */
    private $details;
} 