# SBEG3603 Assignment 1 - PHP PostGIS API

This project is a backend foundation for a later Web GIS application. It imports the supplied parcel, locality, and city CSV data into PostgreSQL/PostGIS, cleans the problem fields, and exposes secure JSON/GeoJSON-ready CRUD endpoints using PHP PDO prepared statements.

## Dataset Summary

Source data is expected in:

`C:\Users\ACER\OneDrive\Desktop\@123`

| Dataset | Main Fields | Geometry |
| --- | --- | --- |
| `DBKU_Locality.shp` | `Locality_C`, `Locality_N`, `Road_Name` | Polygon |
| `KCH_DCDB_Parcel_Polygon_70.shp` | `OBJECT_ID`, `GENAMAP_TA`, `LAST_UPDAT`, `DIVISION`, `LAND_DISTR`, `BLOCK_SECT`, `LOT_NO_LAB`, `PARENT_UPI`, `LAND_CATEG`, `REMARKS`, `SHAPE_AREA`, `SHAPE_LEN`, `Link` | Polygon |
| `MK7_MK8 - city.csv` | locality code, property counts, RM currency fields, ratepayer count, date, zone code | Non-spatial |

The shapefile projection is Timbalai 1948 / RSO Borneo meters. The SQL uses SRID `29873` for storage and transforms output to WGS84 `4326` for GeoJSON responses.

## Folder Structure

```text
config/
  config.example.php       Database settings template
database/
  schema.sql               Final normalized PostGIS schema
  import.sql               Staging and cleaning import workflow
  sample_requests.http     Manual API test examples
docs/
  AI_PROMPTS.md            AI-assisted prompts used for the assignment report
public/
  index.php                API front controller
src/
  ...                      Router, validation, controllers, repositories
```

## Database Setup

1. Create a PostgreSQL database, for example `sbe3603_assignment1`.
2. Run the schema:

```powershell
psql -U postgres -d sbe3603_assignment1 -f database/schema.sql
```

3. Import source files using the workflow in `database/import.sql`. The shapefile steps require the PostGIS command-line tool `shp2pgsql`.

## PHP Configuration

Copy the example config:

```powershell
Copy-Item config\config.example.php config\config.php
```

Edit `config/config.php` with your PostgreSQL username, password, host, port, and database name.

Make sure XAMPP/PHP has PostgreSQL support enabled: `pdo_pgsql` and `pgsql`. See `docs/SETUP_CHECKLIST.md` if the API returns a PDO driver error.

With XAMPP, place or symlink this folder under `htdocs`, then open:

```text
http://localhost/sbe3603-assignment1/public/index.php/health
```

Clean URLs also work if Apache rewrite is enabled:

```text
http://localhost/sbe3603-assignment1/public/health
```

## Main API Endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/health` | Check API status |
| `GET` | `/localities` | Read localities, filter by `locality_code`, `zone_code`, `search` |
| `POST` | `/localities` | Create a locality |
| `GET` | `/localities/{code}` | Read one locality as a GeoJSON Feature |
| `PUT/PATCH` | `/localities/{code}` | Update a locality |
| `DELETE` | `/localities/{code}` | Delete a locality after dependency checks |
| `GET` | `/parcels` | Read parcels, filter by `object_id`, `division`, `land_district`, `block_section`, `lot_no_label`, `genamap_tag` |
| `POST` | `/parcels` | Create a parcel |
| `GET` | `/parcels/{object_id}` | Read one parcel as a GeoJSON Feature |
| `PUT/PATCH` | `/parcels/{object_id}` | Update a parcel |
| `DELETE` | `/parcels/{object_id}` | Delete a parcel |
| `GET` | `/city-records` | Read cleaned city records, filter by `locality_code`, `zone_code` |
| `POST` | `/city-records` | Create a city record |
| `GET` | `/city-records/{id}` | Read one city record |
| `PUT/PATCH` | `/city-records/{id}` | Update a city record |
| `DELETE` | `/city-records/{id}` | Delete a city record |

List endpoints accept `limit`, `offset`, and `include_geometry=true`. Single spatial records return geometry by default.

## Example Spatial Response

```json
{
  "status": "success",
  "data": {
    "type": "Feature",
    "id": "A01",
    "geometry": {
      "type": "MultiPolygon",
      "coordinates": []
    },
    "properties": {
      "locality_code": "A01",
      "locality_name": "Example",
      "centroid": {
        "longitude": 110.345,
        "latitude": 1.556
      }
    }
  }
}
```

## Security and Error Handling

- PDO prepared statements are used for all database access.
- JSON request bodies are validated before insert/update.
- Dates are normalized from `YYYY-MM-DD`, `DD/MM/YYYY`, or `YYYYMMDD`.
- RM currency strings such as `RM20,788.90` are cleaned to numeric values.
- Delete requests check related records before removing localities.
- Responses use consistent `status`, `data`, `message`, and `errors` fields.
- Unexpected exceptions return structured JSON without exposing database credentials.

## Testing

Use `database/sample_requests.http` with a REST client, Postman, or VS Code REST Client. The file includes create, read, update, delete, filter, and GeoJSON tests.
