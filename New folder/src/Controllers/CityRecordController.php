<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CityRecordRepository;

class CityRecordController
{
    public function __construct(private readonly CityRecordRepository $repository = new CityRecordRepository())
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
        $id = Validator::intOrNull($params['id'], 1);
        $record = $this->repository->find((int) $id);
        if ($record === null) {
            throw new ApiException('City record not found.', 404);
        }

        Response::success($record);
    }

    public function store(Request $request): void
    {
        Response::success($this->repository->create($this->payload($request->input(), true)), 201, 'City record created.');
    }

    public function update(Request $request, array $params): void
    {
        $id = Validator::intOrNull($params['id'], 1);
        Response::success($this->repository->update((int) $id, $this->payload($request->input(), false)), 200, 'City record updated.');
    }

    public function destroy(Request $request, array $params): void
    {
        $id = Validator::intOrNull($params['id'], 1);
        $this->repository->delete((int) $id);
        Response::success(null, 200, 'City record deleted.');
    }

    private function payload(array $input, bool $create): array
    {
        $payload = [];

        if ($create || $this->has($input, ['locality_code', 'Locality_C'])) {
            $payload['locality_code'] = strtoupper(Validator::cleanString($this->value($input, 'locality_code', 'Locality_C'), 32, true));
        }

        $intFields = [
            'residential_count' => 'Residential',
            'commercial_count' => 'Commercial',
            'vacant_land_count' => 'Vacant_Land',
            'ratepayer_count' => 'No.Of Ratepayer',
        ];

        foreach ($intFields as $clean => $source) {
            if ($this->has($input, [$clean, $source])) {
                $payload[$clean] = Validator::intOrNull($this->value($input, $clean, $source), 0);
            }
        }

        $moneyFields = [
            'arv_total' => 'ARV_Total',
            'annual_rates' => 'Annual Rates',
            'outstanding' => 'Outstanding',
        ];

        foreach ($moneyFields as $clean => $source) {
            if ($this->has($input, [$clean, $source])) {
                $payload[$clean] = Validator::decimalOrNull($this->value($input, $clean, $source));
            }
        }

        if ($this->has($input, ['last_update', 'Last_update'])) {
            $payload['last_update'] = Validator::dateOrNull($this->value($input, 'last_update', 'Last_update'));
        }

        if ($this->has($input, ['zone_code', 'Zone_Code'])) {
            $payload['zone_code'] = strtoupper(Validator::cleanString($this->value($input, 'zone_code', 'Zone_Code'), 20));
        }

        if ($this->has($input, ['remark', 'Remark'])) {
            $payload['remark'] = Validator::cleanString($this->value($input, 'remark', 'Remark'), 1000);
        }

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
}

