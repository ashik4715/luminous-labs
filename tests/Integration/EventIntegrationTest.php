<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Database\Database;
use App\Database\Migrator;
use App\Models\Event;

class EventIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['DATABASE_PATH'] = ':memory:';
        Database::reset();
        
        $db = Database::getConnection();
        $migrator = new Migrator($db);
        $migrator->migrate();
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    public function testReturnsUpcomingEvents(): void
    {
        $eventModel = new Event();
        
        // Create test events
        $eventModel->create([
            'title' => 'Future Event 1',
            'event_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
        ]);
        $eventModel->create([
            'title' => 'Future Event 2',
            'event_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
        ]);

        $events = $eventModel->findUpcoming(10);

        $this->assertCount(2, $events);
        $this->assertEquals('Future Event 1', $events[0]['title']);
    }

    public function testLimitsResultsToTen(): void
    {
        $eventModel = new Event();
        
        // Create 15 events
        for ($i = 1; $i <= 15; $i++) {
            $eventModel->create([
                'title' => "Event {$i}",
                'event_date' => date('Y-m-d H:i:s', strtotime("+{$i} days")),
            ]);
        }

        $events = $eventModel->findUpcoming(10);

        $this->assertCount(10, $events);
    }

    public function testExcludesPastEvents(): void
    {
        $eventModel = new Event();
        
        // Create past event
        $eventModel->create([
            'title' => 'Past Event',
            'event_date' => date('Y-m-d H:i:s', strtotime('-1 week')),
        ]);
        
        // Create future event
        $eventModel->create([
            'title' => 'Future Event',
            'event_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
        ]);

        $events = $eventModel->findUpcoming(10);

        $this->assertCount(1, $events);
        $this->assertEquals('Future Event', $events[0]['title']);
    }

    public function testOrdersEventsByDate(): void
    {
        $eventModel = new Event();
        
        // Create events out of order
        $eventModel->create([
            'title' => 'Later Event',
            'event_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
        ]);
        $eventModel->create([
            'title' => 'Earlier Event',
            'event_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
        ]);

        $events = $eventModel->findUpcoming(10);

        $this->assertEquals('Earlier Event', $events[0]['title']);
        $this->assertEquals('Later Event', $events[1]['title']);
    }
}
