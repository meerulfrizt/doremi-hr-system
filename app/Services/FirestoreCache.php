<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * FirestoreCache — jimat Firestore reads dengan cache file
 * Data dikemaskini hanya sekali setiap CACHE_TTL minit
 */
class FirestoreCache
{
    // Cache 10 minit untuk presentation (tukar ikut keperluan)
    private const CACHE_TTL = 10 * 60;

    private static string $projectId = 'doremi-admin2';
    private static string $baseUrl   = 'https://firestore.googleapis.com/v1/projects/doremi-admin2/databases/(default)/documents';

    /**
     * Ambil semua dokumen dari collection — guna cache jika ada
     */
    public static function getCollection(string $collection, bool $forceRefresh = false): array
    {
        $cacheKey = "firestore_collection_{$collection}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Fetch dari Firestore dengan pagination (max 300 docs)
        $allDocs = [];
        $url     = self::$baseUrl . "/{$collection}?pageSize=300";
        $nextToken = null;

        do {
            $fetchUrl = $nextToken ? $url . "&pageToken={$nextToken}" : $url;
            $res      = Http::timeout(10)->get($fetchUrl)->json();
            $docs     = $res['documents'] ?? [];
            $allDocs  = array_merge($allDocs, $docs);
            $nextToken = $res['nextPageToken'] ?? null;
        } while ($nextToken);

        Cache::put($cacheKey, $allDocs, self::CACHE_TTL);
        return $allDocs;
    }

    /**
     * Ambil satu dokumen — guna cache
     */
    public static function getDocument(string $collection, string $docId, bool $forceRefresh = false): array
    {
        $cacheKey = "firestore_doc_{$collection}_{$docId}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $res = Http::timeout(10)->get(self::$baseUrl . "/{$collection}/{$docId}")->json();
        Cache::put($cacheKey, $res, self::CACHE_TTL);
        return $res;
    }

    /**
     * Buang cache untuk collection tertentu (contoh selepas update/approve)
     */
    public static function forget(string $collection): void
    {
        Cache::forget("firestore_collection_{$collection}");
    }

    /**
     * Buang semua firestore cache
     */
    public static function forgetAll(): void
    {
        $collections = ['users', 'leaves', 'overtime', 'flexi', 'attendances', 'assigned_tasks', 'notifications'];
        foreach ($collections as $col) {
            Cache::forget("firestore_collection_{$col}");
        }
    }

    /**
     * Buat URL Firestore lengkap
     */
    public static function url(string $path = ''): string
    {
        return self::$baseUrl . ($path ? "/{$path}" : '');
    }

    /**
     * Shortcut: ambil users sebagai array terformat
     */
    public static function getUsers(): array
    {
        $docs  = self::getCollection('users');
        $users = [];
        foreach ($docs as $doc) {
            $f       = $doc['fields'] ?? [];
            $id      = basename($doc['name']);
            $users[$id] = [
                'id'           => $id,
                'full_name'    => $f['full_name']['stringValue'] ?? 'N/A',
                'email'        => $f['email']['stringValue'] ?? 'N/A',
                'department'   => $f['department']['stringValue'] ?? 'General',
                'role'         => $f['role']['stringValue'] ?? 'staff',
                'al_balance'   => $f['al_balance']['integerValue'] ?? ($f['al_balance']['doubleValue'] ?? 0),
                'el_balance'   => $f['el_balance']['integerValue'] ?? ($f['el_balance']['doubleValue'] ?? 0),
                'mc_balance'   => $f['mc_balance']['integerValue'] ?? ($f['mc_balance']['doubleValue'] ?? 0),
                'ot_balance'   => $f['ot_balance']['doubleValue'] ?? ($f['ot_balance']['integerValue'] ?? 0),
            ];
        }
        return $users;
    }

    /**
     * Shortcut: count pending dari collection
     */
    public static function getPendingCount(array $collections = ['leaves', 'overtime', 'flexi']): int
    {
        $total = 0;
        foreach ($collections as $col) {
            $docs = self::getCollection($col);
            foreach ($docs as $doc) {
                $status = $doc['fields']['status']['stringValue'] ?? '';
                if (strtolower($status) === 'pending') $total++;
            }
        }
        return $total;
    }
}


