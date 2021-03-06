<?php

namespace Hyqo\Snowflake\Test;

use Hyqo\Snowflake\Resolver\MemcachedSequenceResolver;
use Hyqo\Snowflake\Resolver\SequenceResolverInterface;
use Hyqo\Snowflake\Resolver\SharedSequenceResolver;
use Hyqo\Snowflake\Snowflake;
use PHPUnit\Framework\TestCase;

class SnowflakeTest extends TestCase
{
    public function test_local_sequence_resolver(): void
    {
        $snowflake = new Snowflake();

        $firstId = $snowflake->generate();
        $secondId = $snowflake->generate();

        $firstData = $snowflake->parse($firstId);
        $secondData = $snowflake->parse($secondId);

        $this->assertTrue($secondData['timestamp'] - $firstData['timestamp'] <= 1);
        $this->assertEquals(0, $firstData['sequence']);

        if ($secondData['timestamp'] === $firstData['timestamp']) {
            $this->assertEquals(1, $secondData['sequence']);
        } else {
            $this->assertEquals(0, $secondData['sequence']);
        }
    }

    public function test_shared_sequence_resolver(): void
    {
        $resolver = new SharedSequenceResolver();

        $snowflake = new Snowflake($resolver);

        $firstId = $snowflake->generate();
        $secondId = $snowflake->generate();

        $firstData = $snowflake->parse($firstId);
        $secondData = $snowflake->parse($secondId);

        $this->assertTrue($secondData['timestamp'] - $firstData['timestamp'] <= 1);
        $this->assertEquals(0, $firstData['sequence']);

        if ($secondData['timestamp'] === $firstData['timestamp']) {
            $this->assertEquals(1, $secondData['sequence']);
        } else {
            $this->assertEquals(0, $secondData['sequence']);
        }
    }

    public function test_memcached_sequence_resolver(): void
    {
        $address = sprintf('%s:11211', getenv('MEMCACHED_HOST') ?: 'memcached');
        $resolver = new MemcachedSequenceResolver($address);

        $snowflake = new Snowflake($resolver);

        $firstId = $snowflake->generate();
        $secondId = $snowflake->generate();

        $firstData = $snowflake->parse($firstId);
        $secondData = $snowflake->parse($secondId);

        $this->assertEquals(0, $firstData['sequence']);

        if ($secondData['timestamp'] === $firstData['timestamp']) {
            $this->assertEquals(1, $secondData['sequence']);
        } else {
            $this->assertEquals(0, $secondData['sequence']);
        }
    }

    public function test_generate_for_date_time(): void
    {
        $snowflake = new Snowflake();

        $firstId = $snowflake->generateForDateTime(new \DateTimeImmutable('2022-01-01'));
        $secondId = $snowflake->generateForDateTime(new \DateTimeImmutable('2022-01-01'));

        $firstData = $snowflake->parse($firstId);
        $secondData = $snowflake->parse($secondId);

        $this->assertEquals($firstData['timestamp'], $secondData['timestamp']);
        $this->assertEquals(0, $firstData['sequence']);
        $this->assertEquals(1, $secondData['sequence']);
    }

    public function test_sequence_resolver(): void
    {
        $resolver = new class() implements SequenceResolverInterface {
            public function sequence(int $time): int
            {
                return 1;
            }
        };

        $snowflake = new Snowflake($resolver);

        $firstId = $snowflake->generate();
        $secondId = $snowflake->generate();

        $firstData = $snowflake->parse($firstId);
        $secondData = $snowflake->parse($secondId);

        $this->assertTrue($secondData['timestamp'] - $firstData['timestamp'] <= 1);
        $this->assertEquals(1, $firstData['sequence']);
        $this->assertEquals(1, $secondData['sequence']);
    }
}
