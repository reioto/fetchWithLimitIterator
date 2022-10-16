<?php

declare(strict_types=1);

namespace FetchWithLimitIterator;

use Iterator;

/**
 * @template TValue
 * @implements Iterator<int, TValue>
 */
class StreamIterator implements Iterator
{
    /**
     * @var FetchAllWithLimitInterface<TValue>
     */
    private $fetcher;

    /**
     * @var int
     */
    private $initOffset;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var bool
     */
    private $isTerminate = false;

    /**
     * @param FetchAllWithLimitInterface<TValue> $fetcher
     */
    public function __construct(
        FetchAllWithLimitInterface $fetcher,
        int $limit,
        int $offset = 0
    ) {
        $this->fetcher = $fetcher;
        $this->limit = $limit;
        $this->initOffset = $offset;
        $this->offset = $offset;

        if ($limit === 0) {
            $this->isTerminate = true;
        }
    }

    /**
     * @return Iterator<int, TValue>
     */
    private function makeCurrentGenrator(int $limit, int $offset): Iterator
    {
        $iterable = $this->fetcher->fetchAllWithLimit($limit, $offset);
        foreach ($iterable as $row) {
            yield $row;
        }
    }

    /**
     * @var ?Iterator<int, TValue>
     */
    private $currentGenerator = null;

    private function getCurrentGenerator(): Iterator
    {
        if ($this->currentGenerator === null) {
            $this->currentGenerator = $this->makeCurrentGenrator($this->limit, $this->offset);
        }

        return $this->currentGenerator;
    }

    private function setCurrentGenerator(?Iterator $current): void
    {
        $this->currentGenerator = $current;
    }

    /**
     * @return ?TValue
     */
    public function current()
    {
        $current = $this->getCurrentGenerator();
        return $current->current();
    }

    /**
     * output current's offset
     */
    public function key(): int
    {
        return $this->offset;
    }

    public function next(): void
    {
        $current = $this->getCurrentGenerator();
        if ($current->valid() === false) {
            return;
        }
        $current->next();
        $this->offset++;

        if ($current->valid()) {
            return;
        }

        if ($this->offset % $this->limit === 0) {
            $this->setCurrentGenerator(null);
            $current = $this->getCurrentGenerator();
            if ($current->valid() === false) {
                $this->isTerminate = true;
            }
        } else {
            $this->isTerminate = true;
        }
    }

    public function rewind(): void
    {
        $this->offset = $this->initOffset;
        $this->setCurrentGenerator(null);
        if ($this->limit > 0) {
            $this->isTerminate = false;
        }
    }

    public function valid(): bool
    {
        if ($this->isTerminate) {
            return false;
        }
        return $this->getCurrentGenerator()
            ->valid();
    }
}
