
<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/database.php';

$app = Slim\Factory\AppFactory::create();
$app->addBodyParsingMiddleware();

// Cargar rutas desde endpoints.php
$routes = require __DIR__ . '/../app/Endpoints/endpoints.php';
$routes($app);

$app->run();