<?php
namespace App;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Online Shop API",
 *         version="1.0.0",
 *         description="REST API für Kategorien und Produkte (Slim + MySQL)"
 *     )
 * )
 */




use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use OpenApi\Annotations as OA;

require __DIR__ . "/../vendor/autoload.php";

require_once "../src/db.php";

require_once __DIR__ . "/auth.php";
global $config;

$app = AppFactory::create();
global $connection;

$app->setBasePath('/project/public');


$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);


$app->post('/login', function (Request $request, Response $response){
    global $config;

    $data = $request->getParsedBody();

    $userName = $data["userName"] ?? null;
    $password = $data["password"] ?? null;

    // Pflichtfelder prüfen
    if ($userName === null || $password === null) {
        $response->getBody()->write(json_encode([
            "error" => "Username und Passwort sind Pflichtfelder"
        ]));
        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    // Login prüfen
    if ($userName !== $config["auth_user"] || $password !== $config["auth_pass"]) {
        $response->getBody()->write(json_encode([
            "error" => "Ungültige Login-Daten"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }
    

        // Token erstellen
        $token = createToken(1);

        // Token im Cookie speichern
        setcookie(
            "auth_token",
            $token,
            time() + $config["jwt_lifetime"],
            "/",
            "",
            false,
            true
        );

         // Erfolg zurückgeben
        $response->getBody()->write(json_encode([
            "message" => "Login erfolgreich"
        ]));

        return $response->withHeader("Content-Type", "application/json");
});

/**
 * @OA\Get(
 *     path="/category/{category_id}",
 *     summary="Kategorie abrufen",
 *     description="Liest eine Kategorie anhand der Kategorie-ID aus.",
 *     tags={"Category"},
 *
 *     @OA\Parameter(
 *         name="category_id",
 *         in="path",
 *         required=true,
 *         description="Die ID der gewünschten Kategorie",
 *         @OA\Schema(type="integer")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Kategorie erfolgreich gefunden",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="category_id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Black Templars"),
 *             @OA\Property(property="active", type="integer", example=1)
 *         )
 *     ),
 *
 * *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ), 
 * 
 *     @OA\Response(response=404, description="Kategorie wurde nicht gefunden"
 *     ),
 *
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

//Catgory CRUD Routes

// Category Get Route, Holt eine Kategorie anhand der ID
$app->get('/category/{category_id}', function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;
    
    // Holt die Kategorie aus der Datenbank
    $result = mysqli_query($connection, "SELECT * FROM category WHERE category_id = " . $args["category_id"]);

    // Wenn die Datenbankabfrage fehlschlägt
    if($result === false) {
        return $response->withStatus(500);
    }
        // Prüft, ob kein Datensatz existiert
        $row_count = mysqli_num_rows($result);

        if ($row_count == 0) {
            return $response->withStatus(404);
        }
        // Daten auslesen
        $category = mysqli_fetch_assoc($result);

        // JSON zurückgeben
        $response->getBody()->write(json_encode($category));
        return $response->withHeader("Content-Type", "application/json");

});

/**
 * @OA\Post(
 *     path="/category",
 *     summary="Neue Kategorie erstellen",
 *     description="Erstellt eine neue Kategorie mit Name und Active-Status.",
 *     tags={"Category"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         description="Daten für die neue Kategorie",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"name", "active"},
 *             @OA\Property(property="name", type="string", example="Black Templars"),
 *             @OA\Property(property="active", type="integer", example=1)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Kategorie erfolgreich erstellt",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Category created"),
 *             @OA\Property(property="received_name", type="string", example="Black Templars"),
 *             @OA\Property(property="received_active", type="integer", example=1)
 *         )
 *     ),
 *
 *     @OA\Response(response=400, description="Fehlende oder ungültige Felder"
 *     ),
 *
 *  *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ),
 * 
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

// Category Post Route, Erstellt eine neue Kategorie
$app->post('/category', function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;

    //  Holt die JSON-Daten aus der Anfrage
    $data = $request->getParsedBody();
    $name =$data["name"] ?? null;
    $active = $data["active"] ?? null;

    // Pflichtfelder prüfen
    if($name === null || $active === null) {
        $response->getBody()->write(json_encode([
            "error" => "name und active sind Pflichtfelder"
        ]));
        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    // Kategorie speichern
    $sql = "INSERT INTO category (name, active) Values ('$name', $active)";
    $result = mysqli_query($connection, $sql);

    // Erfolgreiche Rückmeldung
    if ($result === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));
        return $response->withHeader(500)->withHeader("Content-Type", "application/json");
    }

    // Erfolgreiche Rückmeldung
    $response->getBody()->write(json_encode([
        "message" => "Category created",
        "received_name" => $name,
        "received_active" => $active
    ]));

    return $response->withStatus(201)->withHeader("Content-Type", "application/json");
});

/**
 * @OA\Put(
 *     path="/category/{category_id}",
 *     summary="Kategorie aktualisieren",
 *     description="Aktualisiert eine bestehende Kategorie anhand ihrer ID.",
 *     tags={"Category"},
 *
 *     @OA\Parameter(
 *         name="category_id",
 *         in="path",
 *         required=true,
 *         description="ID der zu aktualisierenden Kategorie",
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         description="Neue Werte für die Kategorie",
 *         @OA\JsonContent(
 *             type="object",
 *             required={"name", "active"},
 *             @OA\Property(property="name", type="string", example="Black Templars Updated"),
 *             @OA\Property(property="active", type="integer", example=0)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Kategorie erfolgreich aktualisiert",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Category updated"),
 *             @OA\Property(property="recieved_name", type="string", example="Black Templars Updated"),
 *             @OA\Property(property="recieved_active", type="integer", example=0)
 *         )
 *     ),
 *
 *  *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ),
 * 
 *     @OA\Response(response=404, description="Kategorie wurde nicht gefunden"
 *     ),
 *
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

// Category Put Route, Aktualisiert eine bestehende Kategorie
$app->put("/category/{category_id}", function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;

    // Holt die ID aus der URL
    $category = $args["category_id"];
    //  Prüft, ob die Kategorie existiert
    $result = mysqli_query($connection, "SELECT * FROM category WHERE category_id = $category");

    if ($result === false) {
        return $response->withStatus(500);
    } else {
        $row_count = mysqli_num_rows($result);

        if ($row_count === 0) {
            return $response->withStatus(404);
        }
    }

    // Holt die JSON-Daten aus der Anfrage
    $data = $request->getParsedBody();
    $name = $data["name"] ?? null;
    $active = $data["active"] ?? null;

    // Aktualisiert die Kategorie
    $sql = "UPDATE category
            SET name = '$name', active = $active
            WHERE category_id = $category";

    $result = mysqli_query($connection, $sql);    
    
    // Prüft auf SQL-Fehler
    if ($result === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));

        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    // Erfolgreiche Antwort
    $response->getBody()->write(json_encode([
        "message" => "Category updated",
        "recieved_name" => $name,
        "recieved_active" => $active
    ]));

    return $response->withStatus(200)->withHeader("Content-Type", "application/json");
});
/**
 * @OA\Delete(
 *     path="/category/{category_id}",
 *     summary="Kategorie löschen",
 *     description="Löscht eine bestehende Kategorie anhand ihrer ID.",
 *     tags={"Category"},
 *
 *     @OA\Parameter(
 *         name="category_id",
 *         in="path",
 *         required=true,
 *         description="ID der zu löschenden Kategorie",
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Kategorie erfolgreich gelöscht",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Category deleted"),
 *             @OA\Property(property="category_id", type="integer", example=3)
 *         )
 *     ),
 *
 *  *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ),
 * 
 *     @OA\Response(response=404, description="Kategorie wurde nicht gefunden"
 *     ),
 *
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

// Category Delete Route, Löscht eine Kategorie anhand der ID
$app->delete("/category/{category_id}", function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;

    // Holt die ID der Kategorie
    $category = $args["category_id"];

    // Prüft, ob sie existiert
    $result = mysqli_query($connection, "SELECT * FROM category WHERE category_id = $category");

    if ($result === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    // Löscht die Kategorie
    $delete =  mysqli_query($connection, "DELETE FROM category WHERE category_id = $category");

    if ($delete === false) {
        return $response->withStatus(500);
    }

    // Erfolgreiche Antwort
    $response->getBody()->write(json_encode([
        "message" => "Category deleted",
        "category_id" => $category
    ]));

    return $response->withHeader("Content-Type", "application/json");
});

/**
 * @OA\Get(
 *     path="/product/{product_id}",
 *     summary="Ein Produkt anhand der ID abrufen",
 *     description="Gibt die Daten eines Produkts zurück. Falls die ID nicht existiert, wird ein 404 zurückgegeben.",
 *     tags={"Product"},
 *
 *     @OA\Parameter(
 *         name="product_id",
 *         in="path",
 *         required=true,
 *         description="Die ID des Produkts",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Produkt erfolgreich gefunden",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="product_id", type="integer", example=1),
 *             @OA\Property(property="sku", type="string", example="BT01"),
 *             @OA\Property(property="active", type="integer", example=1),
 *             @OA\Property(property="id_category", type="integer", example=14),
 *             @OA\Property(property="name", type="string", example="Terminator Squad"),
 *             @OA\Property(property="image", type="string", example="terminator.jpg"),
 *             @OA\Property(property="description", type="string", example="Elite heavy infantry."),
 *             @OA\Property(property="price", type="number", format="float", example=99.90),
 *             @OA\Property(property="stock", type="integer", example=50)
 *         )
 *     ),
 *
 *  *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ),
 * 
 *     @OA\Response(response=404, description="Produkt wurde nicht gefunden"
 *     ),
 *
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

// Product CRUD Routes

// Product Get Rout, Holt ein Produkt anhand der ID
$app->get("/product/{product_id}", function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;

    // Holt die product_id aus der UR
    $product = $args["product_id"];

    // Prüft, ob das Produkt existiert
    $result = mysqli_query($connection, "SELECT * FROM product WHERE product_id = $product");

    // Fehler bei der SQL-Abfrage
    if ($result === false) {
        return $response->withStatus(500);
    }

    // Keine Daten gefunden, Produkt existiert nicht
    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    // Produkt-Daten auslesen
    $productData = mysqli_fetch_assoc($result);

    // Produkt als JSON zurückgeben
    $response->getBody()->write(json_encode($productData));
    return $response->withHeader("Content-Type", "application/json");
});

/**
 * @OA\Post(
 *     path="/product",
 *     summary="Produkt anlegen",
 *     description="Erstellt ein neues Produkt. 'sku', 'name' und 'id_category' sind Pflichtfelder.",
 *     tags={"Product"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="sku", type="string", example="BT01"),
 *             @OA\Property(property="active", type="integer", example=1),
 *             @OA\Property(property="id_category", type="integer", example=14),
 *             @OA\Property(property="name", type="string", example="Terminator Squad"),
 *             @OA\Property(property="image", type="string", example="terminator.jpg"),
 *             @OA\Property(property="description", type="string", example="Elite heavy infantry."),
 *             @OA\Property(property="price", type="number", format="float", example=99.90),
 *             @OA\Property(property="stock", type="integer", example=50)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Produkt erfolgreich erstellt",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Product created"),
 *             @OA\Property(property="sku", type="string", example="BT01"),
 *             @OA\Property(property="id_category", type="integer", example=14)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=400,
 *         description="Ungültige oder fehlende Daten (z. B. Pflichtfelder oder Category existiert nicht)",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Category does not exist")
 *         )
 *     ),
 *
 *  *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ),
 * 
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

// Product Post Rout, Erstellt ein neues Produkt
$app->post("/product", function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;

    // Holt JSON-Daten aus dem Request
    $data = $request->getParsedBody();
    $sku = $data["sku"] ?? null;
    $active = $data["active"] ?? null;
    $id_category = $data["id_category"] ?? null;
    $name = $data["name"] ?? null;
    $image = $data["image"] ?? null;
    $description = $data["description"] ?? null;
    $price = $data["price"] ?? null;
    $stock = $data["stock"] ?? null;

    // Pflichtfelder prüfen
    if ($sku === null || $name === null || $id_category === null) {
        $response->getBody()->write(json_encode([
            "error" => "sku, name und id_category sind Pflichtfelder"
        ]));

        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    // Prüft ob die Category existiert
    $check = mysqli_query($connection, "SELECT * FROM category WHERE category_id = $id_category");

    if ($check === false) {
        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    // Wenn keine Kategorie gefunden gibt Fehler
    $row_count = mysqli_num_rows($check);

    if ($row_count == 0) {
        $response->getBody()->write(json_encode([
            "error" => "Category does not exist"
        ]));
        return $response->withStatus(400)->withHeader("Content-Type", "application/json");
    }

    // Produkt speichern
    $sql = "INSERT INTO product (sku, active, id_category, name, image, description, price, stock) Values ('$sku', $active, $id_category, '$name', '$image', '$description', $price, $stock)";
    $result = mysqli_query($connection, $sql);

    // Fehler beim Schreiben in Datenbank
    if ($result === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));
        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    // Erfolgreiche Antwort
    $response->getBody()->write(json_encode([
        "message" => "Product created",
        "sku" => $sku,
        "id_category" => $id_category
    ]));


    return $response->withStatus(201)->withHeader("Content-Type", "application/json");
});

/**
 * @OA\Put(
 *     path="/product/{product_id}",
 *     summary="Ein Produkt aktualisieren",
 *     description="Aktualisiert ein existierendes Produkt anhand der ID. 
 *                  Falls das Produkt nicht existiert, wird ein 404-Fehler zurückgegeben.
 *                  Falls die zugehörige Kategorie nicht existiert, wird ein 400-Fehler zurückgegeben.",
 *     tags={"Product"},
 *
 *     @OA\Parameter(
 *         name="product_id",
 *         in="path",
 *         required=true,
 *         description="Die ID des zu aktualisierenden Produkts",
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"sku", "id_category", "name"},
 *             @OA\Property(property="sku", type="string", example="BT02-UPDATED"),
 *             @OA\Property(property="active", type="integer", example=1),
 *             @OA\Property(property="id_category", type="integer", example=14),
 *             @OA\Property(property="name", type="string", example="Primaris Intercessor – Updated"),
 *             @OA\Property(property="image", type="string", example="intercessor_new.jpg"),
 *             @OA\Property(property="description", type="string", example="Updated description."),
 *             @OA\Property(property="price", type="number", format="float", example=39.90),
 *             @OA\Property(property="stock", type="integer", example=20)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Produkt erfolgreich aktualisiert",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Product updated"),
 *             @OA\Property(property="product_id", type="integer", example=2)
 *         )
 *     ),
 *
 *     @OA\Response(response=400, description="Ungültige Eingabe oder Kategorie existiert nicht"
 *     ),
 *
 *  *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ),
 * 
 *     @OA\Response(response=404, description="Produkt wurde nicht gefunden"
 *     ),
 *
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

// Product Put Rout, Aktualisiert ein bestehendes Produkt
$app->put("/product/{product_id}", function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;

    // Holt ID des Produkts
    $product_id = $args["product_id"];

    // Prüft ob Produkt existiert
    $result = mysqli_query($connection, "SELECT * FROM product WHERE product_id = $product_id");

    if ($result === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    // Holt JSON-Daten aus dem Request
    $data = $request->getParsedBody();
    $sku = $data["sku"] ?? null;
    $active = $data["active"] ?? null;
    $id_category = $data["id_category"] ?? null;
    $name = $data["name"] ?? null;
    $image = $data["image"] ?? null;
    $description = $data["description"] ?? null;
    $price = $data["price"] ?? null;
    $stock = $data["stock"] ?? null;

    // Prüft ob die Kategorie existiert
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

    // Produkt aktualisieren
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

    // SQL Fehler
    if ($updateResult === false) {
        $response->getBody()->write(json_encode([
            "error" => mysqli_error($connection)
        ]));
        return $response->withStatus(500)->withHeader("Content-Type", "application/json");
    }

    // Erfolgsnachricht
    $response->getBody()->write(json_encode([
        "message" => "Product updated",
        "product_id" => $product_id
    ]));

    return $response->withHeader("Content-Type", "application/json");
});

/**
 * @OA\Delete(
 *     path="/product/{product_id}",
 *     summary="Ein Produkt löschen",
 *     description="Löscht ein Produkt anhand der ID. 
 *                  Falls das Produkt nicht existiert, wird ein 404-Fehler zurückgegeben.",
 *     tags={"Product"},
 *
 *     @OA\Parameter(
 *         name="product_id",
 *         in="path",
 *         required=true,
 *         description="Die ID des zu löschenden Produkts",
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Produkt erfolgreich gelöscht",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Product deleted"),
 *             @OA\Property(property="product_id", type="integer", example=2)
 *         )
 *     ),
 *
 *  *     @OA\Response(response=401, description="Unauthorized – Token fehlt oder ist ungültig"
 *     ),
 * 
 *     @OA\Response(response=404, description="Produkt wurde nicht gefunden"
 *     ),
 *
 *     @OA\Response(response=500, description="Interner Serverfehler"
 *     )
 * )
 */

// Product Delete Rout, Löscht ein Produkt anhand der ID
$app->delete("/product/{product_id}", function (Request $request, Response $response, $args) {

    if (!requireAuth()) {
        $response->getBody()->write(json_encode([
            "error" => "Unauthorized"
        ]));
        return $response->withStatus(401)->withHeader("Content-Type", "application/json");
    }

    global $connection;

    // Holt ID des Produkts
    $product = $args["product_id"];

    // Prüft ob das Produkt existiert
    $result = mysqli_query($connection, "SELECT * FROM product WHERE product_id = $product");

    if ($result === false) {
        return $response->withStatus(500);
    }

    $row_count = mysqli_num_rows($result);
    if ($row_count == 0) {
        return $response->withStatus(404);
    }

    // Löscht das Produkt
    $delete = mysqli_query($connection, "DELETE FROM product WHERE product_id = $product");

    if ($delete === false) {
        return $response->withStatus(500);
    }

    // Erfolgreiche Antwort
    $response->getBody()->write(json_encode([
        "message" => "PRoduct deleted",
        "product_id" => $product
    ]));

    return $response->withHeader("Content-Type", "application/json");
});

    $app->run();
?>