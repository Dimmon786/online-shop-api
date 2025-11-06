<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . "/../vendor/autoload.php";

require __DIR__ . '/../src/db.php';


$app = AppFactory::create();

$app->setBasePath('/project/public');


$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/test-db', function (Request $request, Response $response) use ($pdo) {
    $stmt = $pdo->query("SELECT 1");
    $response->getBody()->write("DB OK");
    
    return $response;
});

$app->run();
?>