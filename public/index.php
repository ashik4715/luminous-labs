<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Router;
use App\Controllers\WebhookController;
use App\Controllers\EventController;
use App\Database\Migrator;

// Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Run migrations
$migrator = new Migrator();
$migrator->migrate();

// Setup routes
$router = new Router();

// Ticket A: Payment Provider Webhook
$router->post('/webhooks/payment-provider', function () {
    $controller = new WebhookController();
    $controller->handlePaymentWebhook();
});

// Ticket C: Upcoming Events API
$router->get('/api/events/upcoming', function () {
    $controller = new EventController();
    $controller->getUpcoming();
});

// Health check
$router->get('/health', function () {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
});

// Dispatch request
$router->dispatch();
