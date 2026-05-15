<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\LocalityRepository;

class LocalityController
{
    public function __construct(private readonly LocalityRepository $repository = new LocalityRepository())
    {
    }

    public function index(Request $request): void
    {
        Response::success([
            'type' => 'FeatureCollection',
            'features' => $this->repository->list(
                $request->query,
                Validator::limit($request->query),
                Validator::offset($request->query),
                Validator::bool($request->query, 'include_geometry')
            ),
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $record = $this->repository->find($params['code']);
        if ($record === null) {
            throw new ApiException('Locality not found.', 404);
        }

        Response::success($record);
    }

    public function store(Request $request): void
    {
        Response::success($this->repository->create($this->payload($request->input(), true)), 201, 'Locality created.');
    }

    public function update(Request $request, array $params): void
    {
        Response::success($this->repository->update($params['code'], $this->payload($request->input(), false)), 200, 'Locality updated.');
    }

    public function destroy(Request $request, array $params): void
    {
        $this->repository->delete($params['code']);
        Response::success(null, 200, 'Locality deleted.');
    }

    private function payload(array $input, bool $create): array
    {
        $payload = [];

        if ($create || $this->has($input, ['locality_code', 'Locality_C'])) {
            $payload['locality_code'] = strtoupper(Validator::cleanString($this->value($input, 'locality_code', 'Locality_C'), 32, true));
        }

        if ($this->has($input, ['locality_name', 'Locality_N'])) {
            $payload['locality_name'] = Validator::cleanString($this->value($input, 'locality_name', 'Locality_N'), 80);
        }

        if ($this->has($input, ['road_name', 'Road_Name'])) {
            $payload['road_name'] = Validator::cleanString($this->value($input, 'road_name', 'Road_Name'), 250);
        }

        $this->addGeometry($input, $payload);
        return $payload;
    }

    private function value(array $input, string $clean, string $source): mixed
    {
        return $input[$clean] ?? $input[$source] ?? null;
    }

    private function has(array $input, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                return true;
            }
        }

        return false;
    }

    private function addGeometry(array $input, array &$payload): void
    {
        if (array_key_exists('geometry_wkt', $input)) {
            $payload['geometry_wkt'] = Validator::cleanString($input['geometry_wkt'], 200000);
        }

        if (array_key_exists('geometry_geojson', $input)) {
            $payload['geometry_geojson'] = $input['geometry_geojson'];
        }
    }
}

