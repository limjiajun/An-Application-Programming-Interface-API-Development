# API Test Plan

Use `database/sample_requests.http` for manual execution. Record screenshots or copied JSON responses for the assignment report.

| Test | Request | Expected Result |
| --- | --- | --- |
| Health check | `GET /health` | `status: success` |
| Read all localities | `GET /localities?limit=10` | FeatureCollection with up to 10 features |
| Read single locality | `GET /localities/A01` | One GeoJSON Feature with geometry and centroid |
| Filter localities | `GET /localities?zone_code=MK7` | Only localities linked to MK7 records |
| Read parcels by land fields | `GET /parcels?division=01&land_district=012` | Matching parcel FeatureCollection |
| Read city records by zone | `GET /city-records?zone_code=MK7&include_geometry=true` | City attributes with locality geometry |
| Create locality | `POST /localities` | `201` and created Feature |
| Update locality | `PATCH /localities/{code}` | Updated JSON response |
| Protected locality delete | `DELETE /localities/A01` | `409` if city records still reference the locality |
| Delete test locality | `DELETE /localities/TEST01` | Deleted message |
| Invalid date | `POST /city-records` with `last_update: "31-12-2025"` | `422` validation error |
| Invalid JSON | Send malformed JSON body | `400` invalid JSON error |
| SQL injection check | Use a filter like `?zone_code=MK7' OR '1'='1` | No injected rows because prepared statements bind input |

