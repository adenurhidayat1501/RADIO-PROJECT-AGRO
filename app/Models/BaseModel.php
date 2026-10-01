<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

abstract class BaseModel
{
    protected static string $collection = '';

    public static function getCollection(): Collection
    {
        if (empty(static::$collection)) {
            throw new \RuntimeException('Collection name must be defined in model ' . static::class);
        }

        return Database::getCollection(static::$collection);
    }

    public static function find(array $filter = [], array $options = []): array
    {
        $cursor = static::getCollection()->find($filter, $options);
        $results = [];
        foreach ($cursor as $doc) {
            $results[] = self::normalizeDoc((array) $doc);
        }
        return $results;
    }

    public static function findOne(array $filter = [], array $options = []): ?array
    {
        $doc = static::getCollection()->findOne($filter, $options);
        return $doc ? self::normalizeDoc((array) $doc) : null;
    }

    public static function findById(string|ObjectId $id): ?array
    {
        if (!Database::isValidObjectId($id)) {
            return null;
        }

        $objId = Database::toObjectId($id);
        return static::findOne(['_id' => $objId]);
    }

    public static function create(array $data): array
    {
        $now = new UTCDateTime();
        if (!isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        $result = static::getCollection()->insertOne($data);
        $data['_id'] = $result->getInsertedId();

        return self::normalizeDoc($data);
    }

    public static function update(string|ObjectId $id, array $data): bool
    {
        $objId = Database::toObjectId($id);
        $data['updated_at'] = new UTCDateTime();

        // Prevent updating _id
        unset($data['_id']);

        $result = static::getCollection()->updateOne(
            ['_id' => $objId],
            ['$set' => $data]
        );

        return $result->getMatchedCount() > 0;
    }

    public static function delete(string|ObjectId $id): bool
    {
        $objId = Database::toObjectId($id);
        $result = static::getCollection()->deleteOne(['_id' => $objId]);
        return $result->getDeletedCount() > 0;
    }

    public static function count(array $filter = []): int
    {
        return static::getCollection()->countDocuments($filter);
    }

    public static function paginate(array $filter = [], int $page = 1, int $perPage = 20, array $sort = ['created_at' => -1]): array
    {
        $page = max(1, $page);
        $total = static::count($filter);
        $totalPages = (int) ceil($total / $perPage);
        $skip = ($page - 1) * $perPage;

        $items = static::find($filter, [
            'sort' => $sort,
            'skip' => $skip,
            'limit' => $perPage,
        ]);

        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ];
    }

    /**
     * Converts BSON types into native PHP array formats for views and JSON APIs
     */
    public static function normalizeDoc(array $doc): array
    {
        foreach ($doc as $key => $value) {
            if ($value instanceof \stdClass) {
                $doc[$key] = self::normalizeDoc((array) $value);
            } elseif ($value instanceof UTCDateTime) {
                $doc[$key] = $value->toDateTime()->setTimezone(new \DateTimeZone(config('app.timezone', 'Asia/Jakarta')))->format('Y-m-d H:i:s');
                $doc[$key . '_raw'] = $value;
            } elseif ($value instanceof ObjectId) {
                $doc[$key] = (string) $value;
                $doc[$key . '_raw'] = $value;
            } elseif (is_array($value)) {
                $doc[$key] = self::normalizeDoc($value);
            }
        }

        return $doc;
    }
}
