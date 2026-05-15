<?php

declare(strict_types=1);

use App\Controllers\CityRecordController;
use App\Controllers\LocalityController;
use App\Controllers\ParcelController;
use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

require dirname(__DIR__) . '/src/bootstrap.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$request = Request::fromGlobals();
$router = new Router();

$router->get('/health', static fn () => Response::json([
    'status' => 'success',
    'data' => [
        'service' => 'SBEG3603 PostGIS API',
        'date' => gmdate('c'),
    ],
]));

$localities = new LocalityController();
$router->get('/localities', [$localities, 'index']);
$router->post('/localities', [$localities, 'store']);
$router->get('/localities/{code}', [$localities, 'show']);
$router->put('/localities/{code}', [$localities, 'update']);
$router->patch('/localities/{code}', [$localities, 'update']);
$router->delete('/localities/{code}', [$localities, 'destroy']);

$parcels = new ParcelController();
$router->get('/parcels', [$parcels, 'index']);
$router->post('/parcels', [$parcels, 'store']);
$router->get('/parcels/{object_id}', [$parcels, 'show']);
$router->put('/parcels/{object_id}', [$parcels, 'update']);
$router->patch('/parcels/{object_id}', [$parcels, 'update']);
$router->delete('/parcels/{object_id}', [$parcels, 'destroy']);

$cityRecords = new CityRecordController();
$router->get('/city-records', [$cityRecords, 'index']);
$router->post('/city-records', [$cityRecords, 'store']);
$router->get('/city-records/{id}', [$cityRecords, 'show']);
$router->put('/city-records/{id}', [$cityRecords, 'update']);
$router->patch('/city-records/{id}', [$cityRecords, 'update']);
$router->delete('/city-records/{id}', [$cityRecords, 'destroy']);

try {
    $router->dispatch($request);
} catch (ApiException $exception) {
    Response::error($exception->getMessage(), $exception->getStatusCode(), $exception->getErrors());
} catch (Throwable $exception) {
    error_log((string) $exception);
    Response::error('Internal server error.', 500);
}

