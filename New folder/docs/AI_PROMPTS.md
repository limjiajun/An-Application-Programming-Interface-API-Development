# AI-Assisted Prompts Used

These prompts can be included in the assignment report to show how AI was used for code generation, conversion, refinement, and debugging.

## Prompt 1 - Schema Discovery

> Inspect the supplied DBF/CSV fields for `DBKU_Locality`, `KCH_DCDB_Parcel_Polygon_70`, and `MK7_MK8 - city.csv`. Identify fields that need type correction, especially currency, numeric counts, and dates. Propose a normalized PostgreSQL/PostGIS schema.

## Prompt 2 - Data Cleaning

> Generate PostgreSQL SQL statements to clean RM currency strings into numeric values, convert `DD/MM/YYYY` and `YYYYMMDD` values into date columns, convert blank strings into NULL, and load shapefile polygons into PostGIS with SRID 29873.

## Prompt 3 - API Design

> Design PHP API endpoints for localities, parcels, and city records. Include full CRUD operations, filters by locality code, zone code, parcel identifier, and parcel administrative fields. Responses for spatial tables should be GeoJSON-like.

## Prompt 4 - Secure PHP Implementation

> Generate a PHP PDO backend using prepared statements, JSON request parsing, validation, structured JSON errors, and separate controller/repository classes. Avoid direct SQL string interpolation from user input.

## Prompt 5 - Refinement and Debugging

> Review the PHP API for security and assignment requirements. Improve validation for integer, decimal, currency, and date fields. Add safe delete checks and sample HTTP requests for testing.

## Prompt 6 - Testing Evidence

> Create manual API test cases for health check, read all, read one, filtered read, create, update, delete, invalid JSON, invalid date, and protected delete. Format them for a REST client.

