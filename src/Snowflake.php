<?php

namespace Hyqo\Snowflake;

class Snowflake
{
    public const SEQUENCE_LENGTH = 10;

    /**
     * @var SequenceResolverInterface
     */
    private $resolver;

    private $startTime;

    private $maxSequenceValue;

    public function __construct(SequenceResolverInterface $resolver)
    {
        $this->resolver = $resolver;
        $this->startTime = strtotime('2017-01-01 00:00:00') * 1000;

        $this->maxSequenceValue = (1 << self::SEQUENCE_LENGTH) - 1;
    }

    public function generate(): int
    {
        return $this->generateForTimestamp(floor(microtime(true) * 1000));
    }

    public function generateForDateTime(\DateTimeInterface $dateTime): int
    {
        return $this->generateForTimestamp($dateTime->format('U') * 1000);
    }

    protected function generateForTimestamp(int $timestamp): int
    {
        $timestamp -= $this->startTime;

        while (($sequence = $this->resolver->sequence($timestamp)) > $this->maxSequenceValue) {
            $timestamp++;
            echo sprintf("next ms: %d, %d\n", $timestamp, $sequence);
        }

        return ($timestamp << self::SEQUENCE_LENGTH) | $sequence;
    }

    public function parse(int $snowflake): array
    {
        $id = decbin($snowflake);

        $offset = -1 * self::SEQUENCE_LENGTH;

        $data = [
            'timestamp' => substr($id, 0, $offset),
            'sequence' => substr($id, $offset),
        ];

        return array_map(static function ($value) {
            return bindec($value);
        }, $data);
    }
}