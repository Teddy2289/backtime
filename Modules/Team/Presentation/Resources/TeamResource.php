<?php

namespace Modules\Team\Presentation\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Presentation\Resources\UserResource;

class TeamResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'is_public' => $this->is_public,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'projects' => $this->whenLoaded('projects'),
            'members' => $this->whenLoaded('members'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}