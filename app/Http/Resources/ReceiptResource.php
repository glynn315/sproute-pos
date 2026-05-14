<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a plain receipt array (output of SaleService::buildReceipt) for transport.
 * The underlying resource ($this->resource) is the receipt array itself.
 */
class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return is_array($this->resource) ? $this->resource : [];
    }
}
