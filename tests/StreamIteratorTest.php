<?php

declare(strict_types=1);

namespace FetchWithLimitIterator;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @phpstan-type SampleRow array<string, string>
 */
class StreamIteratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param FetchAllWithLimitInterface<SampleRow> $fetcher
     * @return StreamIterator<SampleRow>
     */
    private function makeInstance(
        FetchAllWithLimitInterface $fetcher,
        int $limit,
        int $offset
    ): StreamIterator {
        return new StreamIterator($fetcher, $limit, $offset);
    }

    public function testKeyInitSetOffset(): void
    {
        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $limit = 10;
        $offset = 7;
        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = $instance->key();

        $this->assertSame($offset, $result);
    }

    /**
     * @return array<array{0: bool, 1: array<SampleRow>, 2: string}>
     */
    public function dataProviderValid(): array
    {
        $dataset = [];
        /** @var array<SampleRow> */
        $fetchedData = [
            ['sample' => 'value0'],
            ['sample' => 'value1']
        ];

        $dataset[] = [
            true, $fetchedData, '2 rows'
        ];

        $dataset[] = [
            true, [$fetchedData[0]], '1 row'
        ];

        $dataset[] = [
            false, [], 'Empty'
        ];

        return $dataset;
    }

    /**
     * @dataProvider dataProviderValid
     * @param array<SampleRow> $fetchedData
     */
    public function testValid(bool $ext, array $fetchedData, string $msg): void
    {
        $limit = 10;
        $offset = 0;

        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, $offset)
            ->willReturn($fetchedData);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = $instance->valid();

        $this->assertSame($ext, $result, $msg);
    }

    public function testCurrentKey(): void
    {
        $limit = 10;

        /** @var array<SampleRow> */
        $fetchedData = [
            ['sample' => 'value0'],
            ['sample' => 'value1']
        ];
        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, 0)
            ->willReturn($fetchedData);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $offset = 0;
        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = $instance->current();

        $this->assertEquals($fetchedData[0], $result);
        $this->assertEquals($offset, $instance->key());
    }

    public function testCurrentEmptyData(): void
    {
        $limit = 10;

        /** @var array<SampleRow> */
        $fetchedData = [];

        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, 0)
            ->willReturn($fetchedData);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $offset = 0;
        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = $instance->current();

        $this->assertSame(null, $result);
    }

    public function testNextCurrent(): void
    {
        $limit = 10;
        $offset = 0;

        /** @var array<SampleRow> */
        $fetchedData = [
            ['sample' => 'value0'],
            ['sample' => 'value1']
        ];
        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, $offset)
            ->willReturn($fetchedData);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $offset = 0;
        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = $instance->current();

        $this->assertEquals($fetchedData[0], $result, '1st position');

        $instance->next();
        $result = $instance->current();
        $this->assertEquals($fetchedData[1], $result, '2nd position');
    }

    public function testNextKey(): void
    {
        $limit = 10;
        $offset = 0;

        /** @var array<SampleRow> */
        $fetchedData = [
            ['sample' => 'value0'],
            ['sample' => 'value1']
        ];
        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, $offset)
            ->willReturn($fetchedData);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $offset = 0;
        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = $instance->key();

        $this->assertEquals($offset, $result, '1st position');

        $instance->next();
        $result = $instance->key();
        $this->assertEquals($offset + 1, $result, '2nd position');
    }

    public function testRewind(): void
    {
        $limit = 10;
        $offset = 0;

        /** @var array<SampleRow> */
        $fetchedData = [
            ['sample' => 'value0'],
            ['sample' => 'value1']
        ];
        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, $offset)
            ->willReturn($fetchedData);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $offset = 0;
        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $instance->rewind();
        $result = $instance->current();
        $this->assertEquals($fetchedData[0], $result, '1st position');

        $instance->rewind();
        $result = $instance->current();
        $this->assertEquals($fetchedData[0], $result, 'no effective');

        $instance->next();
        $result = $instance->current();
        $this->assertEquals($fetchedData[1], $result, '2nd position');

        $instance->rewind();
        $result = $instance->current();
        $this->assertEquals($fetchedData[0], $result, 'rewind to 1st position');
    }

    /**
     * @return array<array{0: array<SampleRow>, 1: array<SampleRow>, 2: int, 3: int, 4: string}>
     */
    public function dataProviderIterateInRange(): array
    {
        $dataset = [];

        $limit = 3;
        $offset = 0;
        /** @var array<SampleRow> */
        $fetchedData = [
            ['sample' => 'value0'],
            ['sample' => 'value1'],
        ];

        $dataset[] = [
            [], [], $limit, $offset, 'Empty'
        ];

        $dataset[] = [
            $fetchedData, $fetchedData, $limit, $offset, '2 rows'
        ];

        $dataset[] = [
            [1 => $fetchedData[1]], [$fetchedData[1]], $limit, 1, 'offset starts 1'
        ];

        $dataset[] = [
            [], $fetchedData, 0, 0, 'limit zero'
        ];

        return $dataset;
    }

    /**
     * @dataProvider dataProviderIterateInRange
     * @param array<SampleRow> $ext
     * @param array<SampleRow> $fetchedData
     */
    public function testIterateInRange(
        array $ext,
        array $fetchedData,
        int $limit,
        int $offset,
        string $msg
    ): void {
        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, $offset)
            ->willReturn($fetchedData);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = iterator_to_array($instance);

        $this->assertSame($ext, $result, $msg);
    }

    public function testIteratePagingInRange(): void
    {
        $limit = 2;
        $offset = 0;
        /** @var array<array<SampleRow>> */
        $fetchedData = [];
        $fetchedData[] = [
            ['sample' => 'value0'],
            ['sample' => 'value1']
        ];
        $fetchedData[] = [
            ['sample' => 'value2'],
        ];

        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, $offset)
            ->willReturn($fetchedData[0]);
        $fetcherMock->fetchAllWithLimit($limit, $offset + $limit)
            ->willReturn($fetchedData[1]);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = iterator_to_array($instance);

        $ext = array_merge($fetchedData[0], $fetchedData[1]);
        $this->assertSame($ext, $result, 'not call 3 times');
    }

    public function testIteratePagingOutRange(): void
    {
        $limit = 2;
        $offset = 0;
        /** @var array<array<SampleRow>> */
        $fetchedData = [];
        $fetchedData[] = [
            ['sample' => 'value0'],
            ['sample' => 'value1']
        ];
        $fetchedData[] = [
            ['sample' => 'value2'],
            ['sample' => 'value3'],
        ];

        $fetchedData[] = [];

        $fetcherMock = $this->prophesize(FetchAllWithLimitInterface::class);
        $fetcherMock->fetchAllWithLimit($limit, $offset)
            ->willReturn($fetchedData[0]);
        $fetcherMock->fetchAllWithLimit($limit, $offset + $limit)
            ->willReturn($fetchedData[1]);
        $fetcherMock->fetchAllWithLimit($limit, $offset + $limit * 2)
            ->willReturn($fetchedData[2]);
        /** @var FetchAllWithLimitInterface<SampleRow> */
        $fetcher = $fetcherMock->reveal();

        $instance = $this->makeInstance($fetcher, $limit, $offset);
        $result = iterator_to_array($instance);

        $ext = array_merge($fetchedData[0], $fetchedData[1]);
        $this->assertSame($ext, $result, 'call 3rd page');
    }
}
