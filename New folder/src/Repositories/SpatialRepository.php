<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Config;
use App\Core\Database;
use PDO;

abstract class SpatialRepository
{
    private ?PDO $db = null;

    protected function db(): PDO
    {
        if (!$this->db instanceof PDO) {
            $this->db = Database::connection();
        }

        return $this->db;
    }

    protected function geometryExpression(array $payload, array &$params, string $prefix = ''): ?string
    {
        $storageSrid = (int) Config::get('api.storage_srid', 29873);
        $outputSrid = (int) Config::get('api.output_srid', 4326);
        $wktKey = $prefix . 'geometry_wkt';
        $geoJsonKey = $prefix . 'geometry_geojson';

        if (array_key_exists('geometry_wkt', $payload)) {
            if ($payload['geometry_wkt'] === null || $payload['geometry_wkt'] === '') {
                return 'NULL';
            }

            $params[$wktKey] = $payload['geometry_wkt'];
            return "ST_Multi(ST_SetSRID(ST_GeomFromText(:{$wktKey}), {$storageSrid}))";
        }

        if (array_key_exists('geometry_geojson', $payload)) {
            if ($payload['geometry_geojson'] === null || $payload['geometry_geojson'] === '') {
                return 'NULL';
            }

            $params[$geoJsonKey] = is_array($payload['geometry_geojson'])
                ? json_encode($payload['geometry_geojson'], JSON_UNESCAPED_SLASHES)
                : $payload['geometry_geojson'];

            return "ST_Multi(ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(:{$geoJsonKey}), {$outputSrid}), {$storageSrid}))";
        }

        return null;
    }

    protected function feature(array $row, string $idKey): array
    {
        $geometry = null;
        if (array_key_exists('geometry', $row) && $row['geometry'] !== null) {
            $geometry = json_decode((string) $row['geometry'], true);
        }

        $longitude = $row['longitude'] ?? null;
        $latitude = $row['latitude'] ?? null;
        unset($row['geometry'], $row['longitude'], $row['latitude']);

        if ($longitude !== null && $latitude !== null) {
            $row['centroid'] = [
                'longitude' => (float) $longitude,
                'latitude' => (float) $latitude,
            ];
        }

        return [
            'type' => 'Feature',
            'id' => $row[$idKey] ?? null,
            'geometry' => $geometry,
            'properties' => $row,
        ];
    }

    protected function bindAll(\PDOStatement $statement, array $params): void
    {
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
    }
}
