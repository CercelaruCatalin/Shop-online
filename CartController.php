<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PDO;

class CartController
{
    private PDO $db;
    private Twig $view;
    private array $token;

    public function __construct(PDO $db, Twig $view, array $token) {
        $this->db = $db;
        $this->view = $view;
        $this->token = $token;
    }

    private function isUserAuthenticated(): bool
    {
        return isset($_SESSION['userId']) && !empty($_SESSION['userId']);
    }
    
    private function getUserIdFromSession(): ?int
    {
        return $_SESSION['userId'] ?? null;
    }
    
    private function getUserStatus(int $userId): ?int
    {
        $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function createCartbyUser(Request $request, Response $response, $args)
    {
        if (!$this->isUserAuthenticated()) {
            $responseBody = json_encode(['error' => 'User is not authenticated!']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401)->getBody()->write($responseBody);
        }
    
        $userId = $this->getUserIdFromSession();
        if (!$userId) {
            $responseBody = json_encode(['error' => 'User session not found!']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401)->getBody()->write($responseBody);
        }
    
        $userStatus = $this->getUserStatus($userId);
        if ($userStatus != 1 && $userStatus != 2) {
            $responseBody = json_encode(['error' => 'You do not have permission to create a cart']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403)->getBody()->write($responseBody);
        }
    
        $username = $args['username'];
    
        // Check if a cart already exists for the user
        $stmt = $this->db->prepare('SELECT * FROM carts WHERE username = ?');
        $stmt->execute([$username]);
        $existingCart = $stmt->fetch();
    
        if ($existingCart) {
            $responseBody = json_encode(['error' => 'A shopping cart already exists for this user']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400)->getBody()->write($responseBody);
        }
    
        // Get current date
        $creationDate = date('Y-m-d');
        // Calculate expiration date (1 month after creation)
        $expirationDate = date('Y-m-d', strtotime($creationDate . ' +1 month'));
    
        // Insert new shopping cart into the database
        $stmt = $this->db->prepare('INSERT INTO carts (username, creation_date, expiration_date, total_price) VALUES (?, ?, ?, 0)');
        $stmt->execute([$username, $creationDate, $expirationDate]);
    
        $responseBody = json_encode(['message' => 'Shopping cart created successfully']);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201)->getBody()->write($responseBody);
    }
    
    public function addItemToShoppingCart(Request $request, Response $response, $args)
    {
        if (!$this->isUserAuthenticated()) {
            $responseBody = json_encode(['error' => 'User is not authenticated!']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401)->getBody()->write($responseBody);
        }
    
        $userId = $this->getUserIdFromSession();
        if (!$userId) {
            $responseBody = json_encode(['error' => 'User session not found!']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401)->getBody()->write($responseBody);
        }
    
        $username = $args['username'];
        $productId = $args['productId'];
    
        // Check if the user exists
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $userId = $stmt->fetchColumn();
    
        if (!$userId) {
            $responseBody = json_encode(['error' => 'User not found']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404)->getBody()->write($responseBody);
        }
    
        // Check if the user has a shopping cart
        $stmt = $this->db->prepare('SELECT id FROM carts WHERE username = ?');
        $stmt->execute([$username]);
        $cartId = $stmt->fetchColumn();
    
        // If user doesn't have a shopping cart, throw an error
        if (!$cartId) {
            $responseBody = json_encode(['error' => 'User does not have a shopping cart']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400)->getBody()->write($responseBody);
        }
    
        // Check if the product is already in the shopping cart
        $stmt = $this->db->prepare('SELECT id, quantity FROM products_carts WHERE product_id = ? AND cart_id = ?');
        $stmt->execute([$productId, $cartId]);
        $existingProduct = $stmt->fetch();
    
        if ($existingProduct) {
            // If the product exists in the cart, increase the quantity
            $newQuantity = $existingProduct['quantity'] + 1;
            $stmt = $this->db->prepare('UPDATE products_carts SET quantity = ? WHERE id = ?');
            $stmt->execute([$newQuantity, $existingProduct['id']]);
        } else {
            // If the product is not in the cart, add it with quantity 1
            $stmt = $this->db->prepare('INSERT INTO products_carts (cart_id, product_id, quantity) VALUES (?, ?, 1)');
            $stmt->execute([$cartId, $productId]);
        }
    
        $responseBody = json_encode(['message' => 'Product added to the shopping cart successfully']);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200)->getBody()->write($responseBody);
    }
    
    public function deleteItemFromShoppingCart(Request $request, Response $response, $args)
    {
        if (!$this->isUserAuthenticated()) {
            $responseBody = json_encode(['error' => 'User is not authenticated!']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401)->getBody()->write($responseBody);
        }
    
        $userId = $this->getUserIdFromSession();
        if (!$userId) {
            $responseBody = json_encode(['error' => 'User session not found!']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401)->getBody()->write($responseBody);
        }
    
        $username = $args['username'];
        $productId = $args['productId'];
    
        // Check if the user exists
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $userId = $stmt->fetchColumn();
    
        if (!$userId) {
            $responseBody = json_encode(['error' => 'User not found']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404)->getBody()->write($responseBody);
        }
    
        // Check if the user has a shopping cart
        $stmt = $this->db->prepare('SELECT id FROM carts WHERE username = ?');
        $stmt->execute([$username]);
        $cartId = $stmt->fetchColumn();
    
        // If user doesn't have a shopping cart, throw an error
        if (!$cartId) {
            $responseBody = json_encode(['error' => 'User does not have a shopping cart']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400)->getBody()->write($responseBody);
        }
    
            // Check if the product is in the shopping cart
    $stmt = $this->db->prepare('SELECT id FROM products_carts WHERE product_id = ? AND cart_id = ?');
    $stmt->execute([$productId, $cartId]);
    $existingProduct = $stmt->fetch();

    if (!$existingProduct) {
        // If the product is not in the cart, throw an error
        $responseBody = json_encode(['error' => 'Product is not in the shopping cart']);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404)->getBody()->write($responseBody);
    }

    // Delete the product from the shopping cart
    $stmt = $this->db->prepare('DELETE FROM products_carts WHERE id = ?');
    $stmt->execute([$existingProduct['id']]);

    $responseBody = json_encode(['message' => 'Product removed from the shopping cart successfully']);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200)->getBody()->write($responseBody);
}

public function getShoppingCart(Request $request, Response $response, $args): Response
{
    $username = $args['username'];

    // Check if the user has a shopping cart
    $stmt = $this->db->prepare('SELECT id FROM carts WHERE username = ?');
    $stmt->execute([$username]);
    $cartId = $stmt->fetchColumn();

    // If user doesn't have a shopping cart, return an empty cart
    if (!$cartId) {
        $responseBody = json_encode(['cartItems' => []]);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // Retrieve the cart items
    $stmt = $this->db->prepare('SELECT p.id as productId, p.name, pc.quantity, p.price as pricePerItem
                                FROM products_carts pc
                                JOIN products p ON pc.product_id = p.id
                                WHERE pc.cart_id = ?');
    $stmt->execute([$cartId]);
    $cartItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $responseBody = json_encode(['cartItems' => $cartItems]);
    $response->getBody()->write($responseBody);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
}

    
}
