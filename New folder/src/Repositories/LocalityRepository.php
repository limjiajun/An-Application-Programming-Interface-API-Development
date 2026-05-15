<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;

class LocalityRepository extends SpatialRepository
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
            $where[] = 'EXISTS (
                SELECT 1 FROM city_records c
                WHERE c.locality_id = l.id AND c.zone_code = :zone_code
            )';
            $params['zone_code'] = strtoupper((string) $filters['zone_code']);
        }

        if (!empty($filters['search'])) {
            $where[] = '(l.locality_name ILIKE :search OR l.road_name ILIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $geometry = $includeGeometry
            ? ', ST_AsGeoJSON(ST_Transform(l.geom, 4326)) AS geometry'
            : ', NULL AS geometry';

        $sql = 'SELECT l.id, l.locality_code, l.locality_name, l.road_name,
                    l.created_at, l.updated_at,
                    ST_X(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS longitude,
                    ST_Y(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS latitude'
            . $geometry .
            ' FROM localities l ' .
            ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
            ' ORDER BY l.locality_code LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        $this->bindAll($statement, $params);
        $statement->execute();

        return array_map(fn (array $row): array => $this->feature($row, 'locality_code'), $statement->fetchAll());
    }

    public function find(string $code, bool $includeGeometry = true): ?array
    {
        $geometry = $includeGeometry
            ? ', ST_AsGeoJSON(ST_Transform(l.geom, 4326)) AS geometry'
            : ', NULL AS geometry';

        $statement = $this->db()->prepare(
            'SELECT l.id, l.locality_code, l.locality_name, l.road_name,
                    l.created_at, l.updated_at,
                    ST_X(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS longitude,
                    ST_Y(ST_PointOnSurface(ST_Transform(l.geom, 4326))) AS latitude'
            . $geometry .
            ' FROM localities l WHERE l.locality_code = :code'
        );
        $statement->execute(['code' => strtoupper($code)]);
        $row = $statement->fetch();

        return $row ? $this->feature($row, 'locality_code') : null;
    }

    public function create(array $payload): array
    {
        $params = [
            'locality_code' => $payload['locality_code'],
            'locality_name' => $payload['locality_name'] ?? null,
            'road_name' => $payload['road_name'] ?? null,
        ];
        $geom = $this->geometryExpression($payload, $params) ?? 'NULL';

        $sql = "INSERT INTO localities (locality_code, locality_name, road_name, geom)
                VALUES (:locality_code, :locality_name, :road_name, {$geom})";

        $statement = $this->db()->prepare($sql);
        $this->bindAll($statement, $params);
        $statement->execute();

        return $this->find($payload['locality_code']) ?? throw new ApiException('Locality was not created.', 500);
    }

    public function update(string $code, array $payload): array
    {
        $params = ['code' => strtoupper($code)];
        $sets = [];

        foreach (['locality_code', 'locality_name', 'road_name'] as $field) {
            if (array_key_exists($field, $payload)) {
                $sets[] = "{$field} = :{$field}";
                $params[$field] = $payload[$field];
            }
        }

        $geom = $this->geometryExpression($payload, $params);
        if ($geom !== null) {
            $sets[] = "geom = {$geom}";
        }

        if ($sets === []) {
            throw new ApiException('No valid fields supplied for update.', 422);
        }

        $sql = 'UPDATE localities SET ' . implode(', ', $sets) . ', updated_at = now()
                WHERE locality_code = :code';

        $statement = $this->db()->prepare($sql);
        $this->bindAll($statement, $params);
        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new ApiException('Locality not found.', 404);
        }

        return $this->find($payload['locality_code'] ?? $code) ?? throw new ApiException('Locality not found after update.', 404);
    }

    public function delete(string $code): void
    {
        $dependency = $this->db()->prepare(
            'SELECT count(*) FROM city_records c
             JOIN localities l ON l.id = c.locality_id
             WHERE l.locality_code = :code'
        );
        $dependency->execute(['code' => strtoupper($code)]);

        if ((int) $dependency->fetchColumn() > 0) {
            throw new ApiException('Locality cannot be deleted while city records reference it.', 409);
        }

        $statement = $this->db()->prepare('DELETE FROM localities WHERE locality_code = :code');
        $statement->execute(['code' => strtoupper($code)]);

        if ($statement->rowCount() === 0) {
            throw new ApiException('Locality not found.', 404);
        }
    }
}
