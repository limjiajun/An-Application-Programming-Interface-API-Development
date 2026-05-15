<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;

class ParcelRepository extends SpatialRepository
{
    public function list(array $filters, int $limit, int $offset, bool $includeGeometry): array
    {
        $params = ['limit' => $limit, 'offset' => $offset];
        $where = [];

        foreach (['object_id', 'division', 'land_district', 'block_section', 'lot_no_label', 'genamap_tag'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = "p.{$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }

        $geometry = $includeGeometry
            ? ', ST_AsGeoJSON(ST_Transform(p.geom, 4326)) AS geometry'
            : ', NULL AS geometry';

        $sql = 'SELECT p.id, p.object_id, p.genamap_tag, p.last_update, p.division,
                    p.land_district, p.block_section, p.lot_no_label, p.parent_upi,
                    p.land_category, p.remarks, p.shape_area, p.shape_length, p.source_link,
                    p.created_at, p.updated_at,
                    ST_X(ST_PointOnSurface(ST_Transform(p.geom, 4326))) AS longitude,
                    ST_Y(ST_PointOnSurface(ST_Transform(p.geom, 4326))) AS latitude'
            . $geometry .
            ' FROM parcels p ' .
            ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
            ' ORDER BY p.object_id LIMIT :limit OFFSET :offset';

        $statement = $this->db()->prepare($sql);
        $this->bindAll($statement, $params);
        $statement->execute();

        return array_map(fn (array $row): array => $this->feature($row, 'object_id'), $statement->fetchAll());
    }

    public function find(int $objectId, bool $includeGeometry = true): ?array
    {
        $geometry = $includeGeometry
            ? ', ST_AsGeoJSON(ST_Transform(p.geom, 4326)) AS geometry'
            : ', NULL AS geometry';

        $statement = $this->db()->prepare(
            'SELECT p.id, p.object_id, p.genamap_tag, p.last_update, p.division,
                    p.land_district, p.block_section, p.lot_no_label, p.parent_upi,
                    p.land_category, p.remarks, p.shape_area, p.shape_length, p.source_link,
                    p.created_at, p.updated_at,
                    ST_X(ST_PointOnSurface(ST_Transform(p.geom, 4326))) AS longitude,
                    ST_Y(ST_PointOnSurface(ST_Transform(p.geom, 4326))) AS latitude'
            . $geometry .
            ' FROM parcels p WHERE p.object_id = :object_id'
        );
        $statement->execute(['object_id' => $objectId]);
        $row = $statement->fetch();

        return $row ? $this->feature($row, 'object_id') : null;
    }

    public function create(array $payload): array
    {
        $fields = [
            'object_id', 'genamap_tag', 'last_update', 'division', 'land_district',
            'block_section', 'lot_no_label', 'parent_upi', 'land_category', 'remarks',
            'shape_area', 'shape_length', 'source_link',
        ];
        $params = array_intersect_key($payload, array_flip($fields));
        $geom = $this->geometryExpression($payload, $params) ?? 'NULL';

        $columns = implode(', ', array_keys($params)) . ', geom';
        $values = ':' . implode(', :', array_keys($params)) . ", {$geom}";
        $statement = $this->db()->prepare("INSERT INTO parcels ({$columns}) VALUES ({$values})");
        $this->bindAll($statement, $params);
        $statement->execute();

        return $this->find((int) $payload['object_id']) ?? throw new ApiException('Parcel was not created.', 500);
    }

    public function update(int $objectId, array $payload): array
    {
        $params = ['object_id_lookup' => $objectId];
        $sets = [];
        $fields = [
            'object_id', 'genamap_tag', 'last_update', 'division', 'land_district',
            'block_section', 'lot_no_label', 'parent_upi', 'land_category', 'remarks',
            'shape_area', 'shape_length', 'source_link',
        ];

        foreach ($fields as $field) {
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

        $statement = $this->db()->prepare(
            'UPDATE parcels SET ' . implode(', ', $sets) . ', updated_at = now()
             WHERE object_id = :object_id_lookup'
        );
        $this->bindAll($statement, $params);
        $statement->execute();

        if ($statement->rowCount() === 0) {
            throw new ApiException('Parcel not found.', 404);
        }

        return $this->find((int) ($payload['object_id'] ?? $objectId)) ?? throw new ApiException('Parcel not found after update.', 404);
    }

    public function delete(int $objectId): void
    {
        $statement = $this->db()->prepare('DELETE FROM parcels WHERE object_id = :object_id');
        $statement->execute(['object_id' => $objectId]);

        if ($statement->rowCount() === 0) {
            throw new ApiException('Parcel not found.', 404);
        }
    }
}
