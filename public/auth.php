<?php
use ReallySimpleJWT\Token;

$config = require_once __DIR__ . "/../src/config.php";

function createToken($userId) {
    global $config;

    $secret = $config["jwt_secret"];
    $issuer = $config["jwt_issuer"];
    $expiration = time() + $config["jwt_lifetime"];

    return $token = Token::create($userId, $secret, $expiration, $issuer);
    
};

function validateToken($token) {
    global $config;

     $secret = $config["jwt_secret"];
     return Token::validate($token, $secret);
};

function getTokenFromCookie() {
    if (!isset($_COOKIE["auth_token"])) {
        return null;
    }

    return $_COOKIE["auth_token"];
};

function requireAuth() {
    $token = getTokenFromCookie();

    if ($token === null) {
        return false;
    }

    return validateToken($token);
}
?>