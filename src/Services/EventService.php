<?php

declare(strict_types=1);

namespace App\Services;

class EventService
{
    private \App\Models\Event $eventModel;

    public function __construct(?\App\Models\Event $eventModel = null)
    {
        $this->eventModel = $eventModel ?? new \App\Models\Event();
    }

    public function getUpcoming(int $limit = 10): array
    {
        return $this->eventModel->findUpcoming($limit);
    }
}
