<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\ParcelRepository;

class ParcelController
{
    public function __construct(private readonly ParcelRepository $repository = new ParcelRepository())
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
        $objectId = Validator::intOrNull($params['object_id'], 1);
        $record = $this->repository->find((int) $objectId);
        if ($record === null) {
            throw new ApiException('Parcel not found.', 404);
        }

        Response::success($record);
    }

    public function store(Request $request): void
    {
        Response::success($this->repository->create($this->payload($request->input(), true)), 201, 'Parcel created.');
    }

    public function update(Request $request, array $params): void
    {
        $objectId = Validator::intOrNull($params['object_id'], 1);
        Response::success($this->repository->update((int) $objectId, $this->payload($request->input(), false)), 200, 'Parcel updated.');
    }

    public function destroy(Request $request, array $params): void
    {
        $objectId = Validator::intOrNull($params['object_id'], 1);
        $this->repository->delete((int) $objectId);
        Response::success(null, 200, 'Parcel deleted.');
    }

    private function payload(array $input, bool $create): array
    {
        $payload = [];

        if ($create || $this->has($input, ['object_id', 'OBJECT_ID'])) {
            $payload['object_id'] = Validator::intOrNull($this->value($input, 'object_id', 'OBJECT_ID'), 1);
        }

        $stringFields = [
            'genamap_tag' => ['GENAMAP_TA', 32],
            'division' => ['DIVISION', 2],
            'land_district' => ['LAND_DISTR', 3],
            'block_section' => ['BLOCK_SECT', 3],
            'lot_no_label' => ['LOT_NO_LAB', 32],
            'parent_upi' => ['PARENT_UPI', 32],
            'land_category' => ['LAND_CATEG', 3],
            'remarks' => ['REMARKS', 1000],
            'source_link' => ['Link', 2048],
        ];

        foreach ($stringFields as $clean => [$source, $max]) {
            if ($this->has($input, [$clean, $source])) {
                $payload[$clean] = Validator::cleanString($this->value($input, $clean, $source), $max);
            }
        }

        if ($this->has($input, ['last_update', 'LAST_UPDAT'])) {
            $payload['last_update'] = Validator::dateOrNull($this->value($input, 'last_update', 'LAST_UPDAT'));
        }

        if ($this->has($input, ['shape_area', 'SHAPE_AREA'])) {
            $payload['shape_area'] = Validator::decimalOrNull($this->value($input, 'shape_area', 'SHAPE_AREA'));
        }

        if ($this->has($input, ['shape_length', 'SHAPE_LEN'])) {
            $payload['shape_length'] = Validator::decimalOrNull($this->value($input, 'shape_length', 'SHAPE_LEN'));
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

