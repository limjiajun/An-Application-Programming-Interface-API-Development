<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;

class CityRecordRepository extends SpatialRepository
{
    public function list(array $filters, int $limit, int $offset, bool $includeGeometry): array
    {
        $params = ['limit' => $limit, 'offset' => $offset];
        $where = [];

        if (!empty($filters['locality_code'])) {
            $where[] = 'l.locality_code = :locality_code';
            $params['locality_code'] = strtoupper((string) $filters['locality_code']);
        }

        if (!empty($filters['zone_code'])) {
            $where[] = 'c.zone_code = :zone_code';
            $params['zone_code'] = strtoupper((string) $filters['zone_code']);
        }

        $geometry = $includeGeometry
            ? ', ST_AsGeoJSON(ST_Transform(l.geom, 4326)) AS geometry'
            : ', NULL AS geometry';

        $sql = 'SELECT c.id, l.locality_code, l.locality_name, c.residential_count,
                    c.commercial_count, c.vacant_land_count, c.arv_total, c.annual_rates,
                    c.outstanding, c.ratepayer_count, c.last_update, c.zone_code, c.remark,
                    c.created_at, c.updated_at,
                    ST_X(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS longitude,
                    ST_Y(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS latitude'
            . $geometry .
            ' FROM city_records c
              JOIN localities l ON l.id = c.locality_id ' .
            ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
            ' ORDER BY c.zone_code, l.locality_code LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        $this->bindAll($statement, $params);
        $statement->execute();

        return array_map(fn (array $row): array => $this->feature($row, 'id'), $statement->fetchAll());
    }

    public function find(int $id, bool $includeGeometry = true): ?array
    {
        $geometry = $includeGeometry
            ? ', ST_AsGeoJSON(ST_Transform(l.geom, 4326)) AS geometry'
            : ', NULL AS geometry';

        $statement = $this->db()->prepare(
            'SELECT c.id, l.locality_code, l.locality_name, c.residential_count,
                    c.commercial_count, c.vacant_land_count, c.arv_total, c.annual_rates,
                    c.outstanding, c.ratepayer_count, c.last_update, c.zone_code, c.remark,
                    c.created_at, c.updated_at,
                    ST_X(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS longitude,
                    ST_Y(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS latitude'
            . $geometry .
            ' FROM city_records c
              JOIN localities l ON l.id = c.locality_id
              WHERE c.id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->feature($row, 'id') : null;
    }

    public function create(array $payload): array
    {
        $localityId = $this->localityId($payload['locality_code']);
        $params = $this->params($payload);
        $params['locality_id'] = $localityId;

        unset($params['locality_code']);
        $columns = implode(', ', array_keys($params));
        $values = ':' . implode(', :', array_keys($params));

        $statement = $this->db()->prepare("INSERT INTO city_records ({$columns}) VALUES ({$values}) RETURNING id");
        $this->bindAll($statement, $params);
        $statement->execute();

        return $this->find((int) $statement->fetchColumn()) ?? throw new ApiException('City record was not created.', 500);
    }

    public function update(int $id, array $payload): array
    {
        $params = ['id' => $id];
        $sets = [];

        if (array_key_exists('locality_code', $payload)) {
            $sets[] = 'locality_id = :locality_id';
            $params['locality_id'] = $this->localityId($payload['locality_code']);
        }

        foreach ($this->params($payload) as $field => $value) {
            if ($field === 'locality_code') {
                continue;
            }

            $sets[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }

        if ($sets === []) {
            throw new ApiException('No valid fields supplied for update.', 422);
        }

        $statement = $this->db()->prepare(
            'UPDATE city_records SET ' . implode(', ', $sets) . ', updated_at = now()
             WHERE id = :id'
        );
        $this->bindAll($statement, $params);
        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new ApiException('City record not found.', 404);
        }

        return $this->find($id) ?? throw new ApiException('City record not found after update.', 404);
    }

    public function delete(int $id): void
    {
        $statement = $this->db()->prepare('DELETE FROM city_records WHERE id = :id');
        $statement->execute(['id' => $id]);

        if ($statement->rowCount() === 0) {
            throw new ApiException('City record not found.', 404);
        }
    }

    private function localityId(string $code): int
    {
        $statement = $this->db()->prepare('SELECT id FROM localities WHERE locality_code = :code');
        $statement->execute(['code' => strtoupper($code)]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new ApiException('Referenced locality_code does not exist.', 422);
        }

        return (int) $id;
    }

    private function params(array $payload): array
    {
        $fields = [
            'locality_code', 'residential_count', 'commercial_count', 'vacant_land_count',
            'arv_total', 'annual_rates', 'outstanding', 'ratepayer_count', 'last_update',
            'zone_code', 'remark',
        ];

        return array_intersect_key($payload, array_flip($fields));
    }
}
