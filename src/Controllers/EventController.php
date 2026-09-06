<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EventService;

class EventController
{
    private EventService $eventService;

    public function __construct(?EventService $eventService = null)
    {
        $this->eventService = $eventService ?? new EventService();
    }

    public function getUpcoming(): void
    {
        $events = $this->eventService->getUpcoming(10);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $events,
            'count' => count($events),
        ]);
    }
}
