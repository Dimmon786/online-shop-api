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

//Catgory CRUD Routes

// Category Get Rout
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

// Category Post Rout
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

    $sql = "INSERT INTO category (name, active) Values ('$name', $active)";
    $result = mysqli_query($connection, $sql);

    if ($result === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));
        return $response->withHeader(500)->withHeader("Content-Type", "application/json");
    }

    $response->getBody()->write(json_encode([
        "message" => "Category created",
        "received_name" => $name,
        "received_active" => $active
    ]));

    return $response->withStatus(201)->withHeader("Content-Type", "application/json");
});

// Category Put Rout
$app->put("/category/{category_id}", function (Request $request, Response $response, $args) {
    global $connection;

    $category = $args["category_id"];
    $result = mysqli_query($connection, "SELECT * FROM category WHERE category_id = $category");

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

    return $response->withStatus(200)->withHeader("Content-Type", "application/json");
});

// Category Delete Rout
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

// Product CRUD Routes

// Product Get Rout
$app->get("/product/{product_id}", function (Request $request, Response $response, $args) {
    global $connection;

    $product = $args["product_id"];

    $result = mysqli_query($connection, "SELECT * FROM product WHERE product_id = $product");

    if ($result === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    $productData = mysqli_fetch_assoc($result);

    $response->getBody()->write(json_encode($productData));
    return $response->withHeader("Content-Type", "application/json");
});


// Product Post Rout
$app->post("/product", function (Request $request, Response $response, $args) {
    global $connection;

    $data = $request->getParsedBody();
    $sku = $data["sku"] ?? null;
    $active = $data["active"] ?? null;
    $id_category = $data["id_category"] ?? null;
    $name = $data["name"] ?? null;
    $image = $data["image"] ?? null;
    $description = $data["description"] ?? null;
    $price = $data["price"] ?? null;
    $stock = $data["stock"] ?? null;

    if ($sku === null || $name === null || $id_category === null) {
        $response->getBody()->write(json_encode([
            "error" => "sku, name und id_category sind Pflichtfelder"
        ]));

        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    $check = mysqli_query($connection, "SELECT * FROM category WHERE category_id = $id_category");

    if ($check === false) {
        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    $row_count = mysqli_num_rows($check);

    if ($row_count == 0) {
        $response->getBody()->write(json_encode([
            "error" => "Category does not exist"
        ]));
        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    $sql = "INSERT INTO product (sku, active, id_category, name, image, description, price, stock) Values ('$sku', $active, $id_category, '$name', '$image', '$description', $price, $stock)";
    $result = mysqli_query($connection, $sql);

    if ($result === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));
        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    $response->getBody()->write(json_encode([
        "message" => "Product created",
        "sku" => $sku,
        "id_category" => $id_category
    ]));


    return $response->withStatus(201)->withHeader("Content-Type", "application/json");
});


// Product Put Rout
$app->put("/product/{product_id}", function (Request $request, Response $response, $args) {
    global $connection;

    $product_id = $args["product_id"];

    // Prüfen ob Produkt existiert
    $result = mysqli_query($connection, "SELECT * FROM product WHERE product_id = $product_id");

    if ($result === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    // Request Daten lesen
    $data = $request->getParsedBody();
    $sku = $data["sku"] ?? null;
    $active = $data["active"] ?? null;
    $id_category = $data["id_category"] ?? null;
    $name = $data["name"] ?? null;
    $image = $data["image"] ?? null;
    $description = $data["description"] ?? null;
    $price = $data["price"] ?? null;
    $stock = $data["stock"] ?? null;

    // FK Validierung
    $check = mysqli_query($connection, "SELECT * FROM category WHERE category_id = $id_category");

    if ($check === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($check);
    if ($row_count == 0) {
        $response->getBody()->write(json_encode([
            "error" => "Category does not exist"
        ]));
        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    // Update Query
    $sql = "UPDATE product SET
                sku = '$sku',
                active = $active,
                id_category = $id_category,
                name = '$name',
                image = '$image',
                description = '$description',
                price = $price,
                stock = $stock
            WHERE product_id = $product_id";

    $updateResult = mysqli_query($connection, $sql);

    if ($updateResult === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));
        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    // Erfolgsantwort
    $response->getBody()->write(json_encode([
        "message" => "Product updated",
        "product_id" => $product_id
    ]));

    return $response->withHeader("Content-Type", "application/json");
});


// Product Delete Rout
$app->delete("/product/{product_id}", function (Request $request, Response $response, $args) {
    global $connection;

    $product = $args["product_id"];

    $result = mysqli_query($connection, "SELECT * FROM product WHERE product_id = $product");

    if ($result === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    $delete = mysqli_query($connection, "DELETE FROM product WHERE product_id = $product");

    if ($delete === false) {
        return $response->withStatus(500);
    }

    $response->getBody()->write(json_encode([
        "message" => "PRoduct deleted",
        "product_id" => $product
    ]));

    return $response->withHeader("Content-Type", "application/json");
});

$app->run();
?>