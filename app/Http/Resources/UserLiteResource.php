<?php

namespace App\Http\Resources;
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'email'  => $this->email,
            'status' => $this->status,

           // 'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),

            // أو primary_role إذا بتحب minimal:
            'role' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->first()),
        ];
    }
}

