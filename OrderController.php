<?php
declare(strict_types = 1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\SessionMiddleware;
use Slim\Views\Twig;
use PDO;

class OrderController {
    private PDO $db;
    private Twig $view;
    private array $token;

    public function __construct(PDO $db, Twig $view, array $token) {
        $this->db = $db;
        $this->view = $view;
        $this->token = $token;
    }

    public function getOrders(Request $request, Response $response, $args) {
        try {
            // Prepare and execute the query to fetch orders and their items
            $stmt = $this->db->query('
                SELECT 
                    o.id as order_id, 
                    o.name, 
                    o.phone, 
                    o.email, 
                    o.method, 
                    o.address, 
                    o.placed_on, 
                    o.payment_status, 
                    o.status, 
                    o.total_price, 
                    oi.product_id, 
                    oi.quantity,
                    oi.price_per_item 
                FROM orders o 
                LEFT JOIN orders_items oi 
                ON o.id = oi.order_id
            ');
            $rows = $stmt->fetchAll();
            
            // Organize the results by orders
            $orders = [];
            foreach ($rows as $row) {
                $orderId = $row['order_id'];
                if (!isset($orders[$orderId])) {
                    $orders[$orderId] = [
                        'id' => $orderId,
                        'name' => $row['name'],
                        'phone' => $row['phone'],
                        'email' => $row['email'],
                        'method' => $row['method'],
                        'address' => $row['address'],
                        'placed_on' => $row['placed_on'],
                        'payment_status' => $row['payment_status'],
                        'status' => $row['status'],
                        'total_price' => $row['total_price'],
                        'items' => []
                    ];
                }
                if ($row['product_id'] !== null) {
                    $orders[$orderId]['items'][] = [
                        'product_id' => $row['product_id'],
                        'quantity' => $row['quantity'],
                        'price_per_item' => $row['price_per_item']
                    ];
                }
            }
    
            $responseBody = json_encode(['orders' => array_values($orders)]);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
        } catch (\PDOException $e) {
            // Handle any potential database errors
            $responseBody = json_encode(['error' => 'Error retrieving orders: ' . $e->getMessage()]);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    

    public function getByUserId(Request $request, Response $response, $args) {
        $user_id = $args['user_id'] ?? null;
    
        if (!$user_id) {
            $responseBody = json_encode(['error' => 'Invalid user ID']);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    o.id as order_id,
                    o.name, 
                    o.phone, 
                    o.email, 
                    o.method, 
                    o.address, 
                    o.placed_on, 
                    o.payment_status, 
                    o.status, 
                    o.total_price, 
                    oi.product_id, 
                    oi.quantity, 
                    oi.price_per_item 
                FROM orders o 
                LEFT JOIN orders_items oi ON o.id = oi.order_id 
                WHERE o.user_id = ?
            ');
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll();
    
            if (!$rows) {
                $responseBody = json_encode(['error' => 'No orders found for the given user ID']);
                $response->getBody()->write($responseBody);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
    
            $orders = [];
            foreach ($rows as $row) {
                $orderId = $row['order_id'];
                if (!isset($orders[$orderId])) {
                    $orders[$orderId] = [
                        'id' => $orderId,
                        'name' => $row['name'],
                        'phone' => $row['phone'],
                        'email' => $row['email'],
                        'method' => $row['method'],
                        'address' => $row['address'],
                        'placed_on' => $row['placed_on'],
                        'payment_status' => $row['payment_status'],
                        'status' => $row['status'],
                        'total_price' => $row['total_price'],
                        'items' => []
                    ];
                }
                if ($row['product_id'] !== null) {
                    $orders[$orderId]['items'][] = [
                        'product_id' => $row['product_id'],
                        'quantity' => $row['quantity'],
                        'price_per_item' => $row['price_per_item']
                    ];
                }
            }
    
            $responseBody = json_encode(['orders' => array_values($orders)]);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
        } catch (\PDOException $e) {
            $responseBody = json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createOrder(Request $request, Response $response, $args) {
        if ($this->token['userId'] == NULL) {
            $responseBody = json_encode(['error' => 'User is not authenticated!']);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    
        $data = $request->getParsedBody();
        
        $userId = $data['user_id'] ?? null;
        $name = $data['name'] ?? null;
        $phone = $data['phone'] ?? null;
        $email = $data['email'] ?? null;
        $method = $data['method'] ?? null;
        $address = $data['address'] ?? null;
        $paymentStatus = $data['payment_status'] ?? null;
        $status = $data['status'] ?? null;
        $productIds = isset($data['productIds']) ? explode(',', $data['productIds']) : [];
        $quantities = isset($data['quantities']) ? explode(',', $data['quantities']) : [];
    
        if (!$userId || !$name || !$phone || !$email || !$method || !$address || !$paymentStatus || !$status) {
            $responseBody = json_encode(['error' => 'Missing required data']);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        if (empty($productIds) || empty($quantities)) {
            $responseBody = json_encode(['error' => 'Product IDs and quantities are required']);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        try {
            $stmt = $this->db->prepare('INSERT INTO orders (user_id, name, phone, email, method, address, payment_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $name, $phone, $email, $method, $address, $paymentStatus, $status]);
    
            $orderId = $this->db->lastInsertId();
    
            foreach ($productIds as $key => $productId) {
                $quantity = $quantities[$key] ?? 1;
                $pricePerItemStmt = $this->db->prepare('SELECT price FROM products WHERE id = ?');
                $pricePerItemStmt->execute([$productId]);
                $pricePerItem = $pricePerItemStmt->fetchColumn();
    
                $insertStmt = $this->db->prepare('INSERT INTO orders_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)');
                $insertStmt->execute([$orderId, $productId, $quantity, $pricePerItem]);
            }
    
            $getItem = $this->db->prepare('SELECT price_per_item FROM orders_items WHERE order_id = ?');
            $getItem->execute([$orderId]);
            $prices = $getItem->fetchAll(PDO::FETCH_COLUMN);
            $totalPrice = array_sum($prices);
    
            $updateStmt = $this->db->prepare('UPDATE orders SET total_price = ? WHERE id = ?');
            $updateStmt->execute([$totalPrice, $orderId]);
    
            $responseBody = json_encode(['message' => 'Order created successfully']);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    
        } catch (\PDOException $e) {
            $responseBody = json_encode(['error' => 'Error creating order: ' . $e->getMessage()]);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    
    

    public function updateOrder(Request $request, Response $response, $args) {
    $orderId = $args['orderId'] ?? null;

    if (!$orderId) {
        $responseBody = json_encode(['error' => 'Order ID is missing!']);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $data = $request->getParsedBody();

    try {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $responseBody = json_encode(['error' => 'Order not found']);
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $name = $data['name'] ?? $order['name'];
        $phone = $data['phone'] ?? $order['phone'];
        $email = $data['email'] ?? $order['email'];
        $method = $data['method'] ?? $order['method'];
        $address = $data['address'] ?? $order['address'];
        $placedOn = $data['placed_on'] ?? $order['placed_on'];
        $paymentStatus = $data['payment_status'] ?? $order['payment_status'];
        $totalPrice = $data['total_price'] ?? $order['total_price'];
        $status = $data['status'] ?? $order['status'];

        $stmt = $this->db->prepare('UPDATE orders SET name = ?, phone = ?, email = ?, method = ?, address = ?, placed_on = ?, payment_status = ?, total_price = ?, status = ? WHERE id = ?');
        $stmt->execute([$name, $phone, $email, $method, $address, $placedOn, $paymentStatus, $totalPrice, $status, $orderId]);

        $responseBody = json_encode(['message' => 'Order updated successfully']);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    } catch (\PDOException $e) {
        $responseBody = json_encode(['error' => 'Error updating order: ' . $e->getMessage()]);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}

public function deleteOrder(Request $request, Response $response, $args) {
    $orderId = $args['orderId'] ?? null;

    if ($orderId === null) {
        $responseBody = json_encode(['error' => 'Order ID is missing!']);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        $stmt_products = $this->db->prepare('DELETE FROM orders_items WHERE order_id = ?');
        $stmt_products->execute([$orderId]);

        $stmt = $this->db->prepare('DELETE FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);

        $responseBody = json_encode(['message' => 'Order deleted successfully']);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    } catch (\PDOException $e) {
        $responseBody = json_encode(['error' => 'Error deleting order: ' . $e->getMessage()]);
        $response->getBody()->write($responseBody);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}


    public function checkAuthorization(Request $request, Response $response) {
        $userToken = $this->token['userId']; // geting the user id from the token
        // if the id is null return unauthorized twig template and error
        if ($userToken != NULL) {
            //
            
            // if user is logged in check for the status (1 - user, 2 - admin)
            $stmt = $this->db->prepare('SELECT userStatus FROM users WHERE id = ?');
            $stmt->execute([$userToken]); 

            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            // if no user status was found, return unauthorized twig template and error
            if (!$existingUser) {
               // return $this->view->render($response->withStatus(401), './user/unauthorized_user.twig', ['message' => 'Error: Unauthorized user']);
                return FALSE;
            }

            // if the user satatus is 1, return unauthorized tiwg template and error
            if ($existingUser['userStatus'] == 1) {
                //return $this->view->render($response->withStatus(401), './user/unauthorized_user.twig', ['message' => 'Error: Unauthorized user']);
                return FALSE;
            
            }
        } else {
            return FALSE;
        }

        return TRUE;
    }
    
}

?>