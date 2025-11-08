<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . "/../vendor/autoload.php";

require_once "../src/db.php";
global $connection;


$app = AppFactory::create();

$app->setBasePath('/project/public');


$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);



$app->get('/category/{category_id}', function (Request $request, Response $response, $args) {
    global $connection;

    $result = mysqli_query($connection, "SELECT * FROM category WHERE category_id = " . $args["category_id"]);

    if($result === false) {
        return $response->withStatus(500);
    } else {
        $row_count = mysqli_num_rows($result);

        if ($row_count == 0) {
            return $response->withStatus(404);
        }

        $category = mysqli_fetch_assoc($result);

        $response->getBody()->write(json_encode($category));
        return $response->withHeader("Content-Type", "application/json");
    }

});

$app->post('/category', function (Request $request, Response $response, $args) {
    global $connection;

    $data = $request->getParsedBody();
    $name =$data["name"] ?? null;
    $active = $data["active"] ?? null;

    if($name === null || $active === null) {
        $response->getBody()->write(json_encode([
            "error" => "name und active sind Pflichtfelder"
        ]));
        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    $sql = "INSERT INTO category (name, active) Values ('$name', '$active')";
    $result = mysqli_query($connection, $sql);

    if ($result === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));
        return $response->withHeader(500)->withHeader("Content-Type", "application/json");
    }

    $response->getBody()->write(json_encode([
        "message" => "Category created",
        "recieved_name" => $name,
        "recieved_active" => $active
    ]));

    return $response->withHeader("Content-Type", "application/json");
});

$app->put("/category/{category_id}", function (Request $request, Response $response, $args) {
    global $connection;

    $category = $args["category_id"];
    $result = mysqli_query($connection, "SELECT * FROM category WHERE category_id = " . $args["category_id"]);

    if ($result === false) {
        return $response->withStatus(500);
    } else {
        $row_count = mysqli_num_rows($result);

        if ($row_count === 0) {
            return $response->withStatus(404);
        }
    }

    $data = $request->getParsedBody();
    $name = $data["name"] ?? null;
    $active = $data["active"] ?? null;

    $sql = "UPDATE category
            SET name = '$name', active = $active
            WHERE category_id = $category";

    $result = mysqli_query($connection, $sql);    
    
    if ($result === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));

        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    $response->getBody()->write(json_encode([
        "message" => "Category updated",
        "recieved_name" => $name,
        "recieved_active" => $active
    ]));

    return $response->withHeader("Content-Type", "application/json");
});

$app->delete("/category/{category_id}", function (Request $request, Response $response, $args) {
    global $connection;

    $category = $args["category_id"];

    $result = mysqli_query($connection, "SELECT * FROM category WHERE category_id = $category");

    if ($result === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    $delete =  mysqli_query($connection, "DELETE FROM category WHERE category_id = $category");

    if ($delete === false) {
        return $response->withStatus(500);
    }

    $response->getBody()->write(json_encode([
        "message" => "Category deleted",
        "category_id" => $category
    ]));

    return $response->withHeader("Content-Type", "application/json");
});

$app->run();
?>