<?php

namespace BrainAppeal\CampusEventsConnector\Tests\Unit\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\RawDataToTcaNormalizer;
use PHPUnit\Framework\TestCase;

class RawDataToTcaNormalizerTest extends TestCase
{
    public function testDecodeEscapedUtf8FiltersMultiByte4(): void
    {
        $importConfiguration = $this->createMock(ImportTableConfigurationModel::class);
        $subject = new RawDataToTcaNormalizer($importConfiguration);

        $reflection = new \ReflectionClass(RawDataToTcaNormalizer::class);
        $method = $reflection->getMethod('decodeEscapedUtf8');
        $method->setAccessible(true);

        // Standard string
        self::assertEquals('Hello', $method->invoke($subject, 'Hello', true));

        // Octal escaped string (e.g., "ä" is \303\244)
        self::assertEquals('ä', $method->invoke($subject, '\303\244', true));

        // 4-byte character (Emoji: 😂 is \360\237\230\202 or \x1F602)
        $emoji = "\u{1F602}";
        self::assertEquals('', $method->invoke($subject, $emoji, true), '4-byte emoji should be filtered when $filterMultiByte4 is true');
        self::assertEquals($emoji, $method->invoke($subject, $emoji, false), '4-byte emoji should NOT be filtered when $filterMultiByte4 is false');

        // Mixed string
        $mixed = 'Hello ' . $emoji . ' World';
        self::assertEquals('Hello  World', $method->invoke($subject, $mixed, true));
    }
}
