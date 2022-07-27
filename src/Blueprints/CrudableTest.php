<?php

namespace Mindz\LaravelCrudableTest\Blueprints;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Tests\TestCase;

class CrudableTest extends TestCase
{
    protected function getUser(): Authenticatable
    {
        return User::factory()->make();
    }

    protected function defaultHeaders(): array
    {
        return [];
    }

    protected function getStructureFor($item)
    {
        if ($item instanceof Collection && ($resourceClass = $this->getCollectionResource())) {
            $resource = new $resourceClass($item->first());
        } elseif ($resourceClass = $this->getResource()) {
            $resource = new $resourceClass($item);
        } else {
            $resource = new JsonResource($item instanceof Collection ? $item->first() : $item);
        }

        $structure = $resource ? array_keys($resource->toArray(request())) : null;

        return [
            'data' => $item instanceof Collection ? [$structure] : $structure
        ];
    }

    protected function getCollectionResource(): ?string
    {
        return null;
    }

    protected function getResource(): ?string
    {
        return null;
    }

    protected function withoutDataWrap(): array
    {
        return [];
    }
}
