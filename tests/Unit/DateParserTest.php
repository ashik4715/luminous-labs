<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\DateParser;

class DateParserTest extends TestCase
{
    private DateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DateParser();
    }

    public function testParsesIsoDateFormat(): void
    {
        $result = $this->parser->parse('2024-01-15');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testParsesEuropeanDateFormat(): void
    {
        $result = $this->parser->parse('15/01/2024');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testParsesUsDateFormat(): void
    {
        $result = $this->parser->parse('01/15/2024');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testParsesGermanDateFormat(): void
    {
        $result = $this->parser->parse('15.01.2024');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testParsesCommonDateFormat(): void
    {
        $result = $this->parser->parse('15-Jan-2024');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testParsesUsWrittenFormat(): void
    {
        $result = $this->parser->parse('Jan 15, 2024');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testParsesUkWrittenFormat(): void
    {
        $result = $this->parser->parse('15 Jan 2024');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testParsesCompactFormat(): void
    {
        $result = $this->parser->parse('20240115');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }

    public function testReturnsNullForInvalidDate(): void
    {
        $result = $this->parser->parse('not-a-date');
        $this->assertNull($result);
    }

    public function testReturnsNullForEmptyString(): void
    {
        $result = $this->parser->parse('');
        $this->assertNull($result);
    }

    public function testDetectsCorrectFormat(): void
    {
        $format = $this->parser->detectFormat('15/01/2024');
        $this->assertEquals('d/m/Y', $format);
    }

    public function testHandlesWhitespaceGracefully(): void
    {
        $result = $this->parser->parse('  2024-01-15  ');
        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result->format('Y-m-d'));
    }
}
