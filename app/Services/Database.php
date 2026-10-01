<?php

declare(strict_types=1);

namespace App\Services;

use MongoDB\Client;
use MongoDB\Database as MongoDatabase;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use App\Helpers\Logger;

class Database
{
    private static ?Client $client = null;
    private static ?MongoDatabase $database = null;

    public static function getClient(): Client
    {
        if (self::$client === null) {
            $uri = config('database.uri', 'mongodb://127.0.0.1:27017');
            $options = config('database.options', []);

            try {
                self::$client = new Client($uri, $options);
            } catch (\Throwable $e) {
                Logger::error('MongoDB Connection Error: ' . $e->getMessage());
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
        }

        return self::$client;
    }

    public static function getDatabase(): MongoDatabase
    {
        if (self::$database === null) {
            $dbName = config('database.database', 'radio_platform');
            self::$database = self::getClient()->selectDatabase($dbName);
        }

        return self::$database;
    }

    public static function getCollection(string $name): Collection
    {
        return self::getDatabase()->selectCollection($name);
    }

    /**
     * Helper to safely convert string to ObjectId or return as is
     */
    public static function toObjectId(string|ObjectId $id): ObjectId
    {
        if ($id instanceof ObjectId) {
            return $id;
        }

        try {
            return new ObjectId($id);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Invalid MongoDB ObjectId string: '{$id}'");
        }
    }

    /**
     * Check if a string is a valid 24-character hexadecimal ObjectId
     */
    public static function isValidObjectId(mixed $id): bool
    {
        if ($id instanceof ObjectId) {
            return true;
        }

        if (!is_string($id) || strlen($id) !== 24) {
            return false;
        }

        return ctype_xdigit($id);
    }
}
