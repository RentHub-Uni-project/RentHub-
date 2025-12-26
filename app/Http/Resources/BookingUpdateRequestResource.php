<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingUpdateRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "requested_start_date" => $this->requested_start_date,
            "requested_end_date" => $this->requested_end_date,
            "requested_tenant_notes" => $this->requested_tenant_notes,
            "requested_number_of_guests" => $this->requested_number_of_guests,
            "status" => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),


            // when loaded
            "booking" => new BookingResource($this->whenLoaded("booking"))
        ];
    }
}
