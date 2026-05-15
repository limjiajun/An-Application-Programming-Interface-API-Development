CREATE EXTENSION IF NOT EXISTS postgis;

CREATE TABLE IF NOT EXISTS localities (
    id bigserial PRIMARY KEY,
    locality_code varchar(32) NOT NULL UNIQUE,
    locality_name varchar(80),
    road_name text,
    geom geometry(MultiPolygon, 29873),
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_localities_geom
    ON localities USING gist (geom);

CREATE INDEX IF NOT EXISTS idx_localities_code
    ON localities (locality_code);

CREATE TABLE IF NOT EXISTS parcels (
    id bigserial PRIMARY KEY,
    object_id bigint NOT NULL UNIQUE,
    genamap_tag varchar(32),
    last_update date,
    division varchar(2),
    land_district varchar(3),
    block_section varchar(3),
    lot_no_label varchar(32),
    parent_upi varchar(32),
    land_category varchar(3),
    remarks text,
    shape_area numeric(19, 6),
    shape_length numeric(19, 6),
    source_link text,
    geom geometry(MultiPolygon, 29873),
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_parcels_geom
    ON parcels USING gist (geom);

CREATE INDEX IF NOT EXISTS idx_parcels_lookup
    ON parcels (division, land_district, block_section, lot_no_label);

CREATE INDEX IF NOT EXISTS idx_parcels_genamap_tag
    ON parcels (genamap_tag);

CREATE TABLE IF NOT EXISTS city_records (
    id bigserial PRIMARY KEY,
    locality_id bigint NOT NULL REFERENCES localities(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    residential_count integer CHECK (residential_count IS NULL OR residential_count >= 0),
    commercial_count integer CHECK (commercial_count IS NULL OR commercial_count >= 0),
    vacant_land_count integer CHECK (vacant_land_count IS NULL OR vacant_land_count >= 0),
    arv_total numeric(14, 2) CHECK (arv_total IS NULL OR arv_total >= 0),
    annual_rates numeric(14, 2) CHECK (annual_rates IS NULL OR annual_rates >= 0),
    outstanding numeric(14, 2) CHECK (outstanding IS NULL OR outstanding >= 0),
    ratepayer_count integer CHECK (ratepayer_count IS NULL OR ratepayer_count >= 0),
    last_update date,
    zone_code varchar(20),
    remark text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (locality_id, zone_code)
);

CREATE INDEX IF NOT EXISTS idx_city_records_zone
    ON city_records (zone_code);

CREATE OR REPLACE VIEW locality_city_summary AS
SELECT
    l.locality_code,
    l.locality_name,
    l.road_name,
    c.zone_code,
    c.residential_count,
    c.commercial_count,
    c.vacant_land_count,
    c.arv_total,
    c.annual_rates,
    c.outstanding,
    c.ratepayer_count,
    c.last_update,
    ST_AsGeoJSON(ST_Transform(l.geom, 4326))::json AS geometry
FROM localities l
LEFT JOIN city_records c ON c.locality_id = l.id;

