-- Run this after database/schema.sql.
-- This file combines psql commands and SQL cleaning statements.
--
-- 1) Create the staging schema:
CREATE SCHEMA IF NOT EXISTS staging;

-- 2) Import shapefiles from PowerShell or Command Prompt, then return to this file.
--    These commands require PostGIS command-line tools.
--
-- shp2pgsql -d -I -s 29873 -W UTF-8 "C:/Users/ACER/OneDrive/Desktop/@123/DBKU_Locality.shp" staging.dbku_locality_raw | psql -U postgres -d sbe3603_assignment1
-- shp2pgsql -d -I -s 29873 -W UTF-8 "C:/Users/ACER/OneDrive/Desktop/@123/KCH_DCDB_Parcel_Polygon_70.shp" staging.kch_dcdb_parcel_polygon_70_raw | psql -U postgres -d sbe3603_assignment1

-- 3) Import the CSV into a raw text staging table.
DROP TABLE IF EXISTS staging.city_raw;
CREATE TABLE staging.city_raw (
    "Locality_C" text,
    "Residential" text,
    "Commercial" text,
    "Vacant_Land" text,
    "ARV_Total" text,
    "Annual Rates" text,
    "Outstanding" text,
    "No.Of Ratepayer" text,
    "Last_update" text,
    "Zone_Code" text,
    "Remark" text
);

\copy staging.city_raw FROM 'C:/Users/ACER/OneDrive/Desktop/@123/MK7_MK8 - city.csv' WITH (FORMAT csv, HEADER true, ENCODING 'UTF8');

-- 4) Load cleaned locality polygons.
INSERT INTO localities (locality_code, locality_name, road_name, geom)
SELECT DISTINCT ON (upper(trim(locality_c)))
    upper(trim(locality_c)) AS locality_code,
    nullif(trim(locality_n), '') AS locality_name,
    nullif(trim(road_name), '') AS road_name,
    ST_Multi(ST_CollectionExtract(ST_MakeValid(geom), 3))::geometry(MultiPolygon, 29873) AS geom
FROM staging.dbku_locality_raw
WHERE nullif(trim(locality_c), '') IS NOT NULL
ON CONFLICT (locality_code) DO UPDATE
SET locality_name = EXCLUDED.locality_name,
    road_name = EXCLUDED.road_name,
    geom = EXCLUDED.geom,
    updated_at = now();

-- 5) Ensure every CSV locality code exists, even if a code is missing from the shapefile.
INSERT INTO localities (locality_code)
SELECT DISTINCT upper(trim("Locality_C"))
FROM staging.city_raw
WHERE nullif(trim("Locality_C"), '') IS NOT NULL
ON CONFLICT (locality_code) DO NOTHING;

-- 6) Load cleaned parcel polygons.
INSERT INTO parcels (
    object_id, genamap_tag, last_update, division, land_district, block_section,
    lot_no_label, parent_upi, land_category, remarks, shape_area, shape_length,
    source_link, geom
)
SELECT
    object_id::bigint,
    nullif(trim(genamap_ta), '') AS genamap_tag,
    CASE
        WHEN last_updat IS NULL THEN NULL
        WHEN last_updat::text ~ '^[0-9]{8}$' THEN to_date(last_updat::text, 'YYYYMMDD')
        WHEN last_updat::text ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN last_updat::date
        ELSE NULL
    END AS last_update,
    nullif(trim(division), '') AS division,
    nullif(trim(land_distr), '') AS land_district,
    nullif(trim(block_sect), '') AS block_section,
    nullif(trim(lot_no_lab), '') AS lot_no_label,
    nullif(trim(parent_upi), '') AS parent_upi,
    nullif(trim(land_categ), '') AS land_category,
    nullif(trim(remarks), '') AS remarks,
    shape_area::numeric(19, 6),
    shape_len::numeric(19, 6),
    nullif(trim(link), '') AS source_link,
    ST_Multi(ST_CollectionExtract(ST_MakeValid(geom), 3))::geometry(MultiPolygon, 29873) AS geom
FROM staging.kch_dcdb_parcel_polygon_70_raw
WHERE object_id IS NOT NULL
ON CONFLICT (object_id) DO UPDATE
SET genamap_tag = EXCLUDED.genamap_tag,
    last_update = EXCLUDED.last_update,
    division = EXCLUDED.division,
    land_district = EXCLUDED.land_district,
    block_section = EXCLUDED.block_section,
    lot_no_label = EXCLUDED.lot_no_label,
    parent_upi = EXCLUDED.parent_upi,
    land_category = EXCLUDED.land_category,
    remarks = EXCLUDED.remarks,
    shape_area = EXCLUDED.shape_area,
    shape_length = EXCLUDED.shape_length,
    source_link = EXCLUDED.source_link,
    geom = EXCLUDED.geom,
    updated_at = now();

-- 7) Load cleaned city records.
INSERT INTO city_records (
    locality_id, residential_count, commercial_count, vacant_land_count,
    arv_total, annual_rates, outstanding, ratepayer_count,
    last_update, zone_code, remark
)
SELECT
    l.id AS locality_id,
    nullif(regexp_replace(r."Residential", '[^0-9-]', '', 'g'), '')::integer,
    nullif(regexp_replace(r."Commercial", '[^0-9-]', '', 'g'), '')::integer,
    nullif(regexp_replace(r."Vacant_Land", '[^0-9-]', '', 'g'), '')::integer,
    nullif(regexp_replace(r."ARV_Total", '[^0-9.-]', '', 'g'), '')::numeric(14, 2),
    nullif(regexp_replace(r."Annual Rates", '[^0-9.-]', '', 'g'), '')::numeric(14, 2),
    nullif(regexp_replace(r."Outstanding", '[^0-9.-]', '', 'g'), '')::numeric(14, 2),
    nullif(regexp_replace(r."No.Of Ratepayer", '[^0-9-]', '', 'g'), '')::integer,
    CASE
        WHEN r."Last_update" ~ '^[0-9]{2}/[0-9]{2}/[0-9]{4}$' THEN to_date(r."Last_update", 'DD/MM/YYYY')
        WHEN r."Last_update" ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN r."Last_update"::date
        ELSE NULL
    END AS last_update,
    upper(nullif(trim(r."Zone_Code"), '')) AS zone_code,
    nullif(trim(r."Remark"), '') AS remark
FROM staging.city_raw r
JOIN localities l ON l.locality_code = upper(trim(r."Locality_C"))
WHERE nullif(trim(r."Locality_C"), '') IS NOT NULL
ON CONFLICT (locality_id, zone_code) DO UPDATE
SET residential_count = EXCLUDED.residential_count,
    commercial_count = EXCLUDED.commercial_count,
    vacant_land_count = EXCLUDED.vacant_land_count,
    arv_total = EXCLUDED.arv_total,
    annual_rates = EXCLUDED.annual_rates,
    outstanding = EXCLUDED.outstanding,
    ratepayer_count = EXCLUDED.ratepayer_count,
    last_update = EXCLUDED.last_update,
    remark = EXCLUDED.remark,
    updated_at = now();

-- 8) Validation checks for the assignment report.
SELECT 'localities' AS table_name, count(*) AS records FROM localities
UNION ALL
SELECT 'parcels', count(*) FROM parcels
UNION ALL
SELECT 'city_records', count(*) FROM city_records;

SELECT
    count(*) FILTER (WHERE geom IS NULL) AS localities_without_geometry,
    count(*) FILTER (WHERE locality_code IS NULL OR locality_code = '') AS missing_codes
FROM localities;

SELECT
    min(last_update) AS earliest_update,
    max(last_update) AS latest_update,
    count(*) FILTER (WHERE arv_total IS NULL) AS missing_arv_total,
    count(*) FILTER (WHERE annual_rates IS NULL) AS missing_annual_rates,
    count(*) FILTER (WHERE outstanding IS NULL) AS missing_outstanding
FROM city_records;
