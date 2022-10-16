# fetchWithLimitIterator

An iterator that treats FetchAll() like a stream

```php
use FetchWithLimitIterator\FetchAllWithLimitInterface;
use FetchWithLimitIterator\StreamIterator;

$fetcher = new class() implements FetchAllWithLimitInterface {
    public function fetchAllWithLimit(int $limit, int $offset): iterable
    {
        $sql = <<<SQL
        select * from sample_table limit :limit offset :offset
        SQL;
        $db = new PDO();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
};
$limit = 2;
$startOffset = 0;
$iterator = new StreamIterator($fetcher, $limit, $startOffset);

// Fetch All Data on table
$result = [];
$lastOffset = null;
foreach ($iterator as $offset => $line) {
    $result[] = $line;
    $lastOffset = $offset;
}

/**
 $result = [
    ['column' => 'value0'],
    ['column' => 'value1'],
    ['column' => 'value2']
 ];

 $lastOffset = 2
*/

// example: Limit for displaying
$limit = 2;
$startOffset = 0;
$iterator = new StreamIterator($fetcher, $limit, $startOffset);

$displayLimit = 2;
$iterator = new LimitIterator($iterator, $displayLimit, 0);

$result = [];
$lastOffset = null;
foreach ($iterator as $offset => $line) {
    $result[] = $line;
    $lastOffset = $offset;
}

/**
 $result = [
    ['column' => 'value0'],
    ['column' => 'value1']
 ];

 $lastOffset = 1
*/

```