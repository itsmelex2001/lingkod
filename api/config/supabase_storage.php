<?php
/**
 * api/config/supabase_storage.php
 * Uploads and deletes public avatar images in Supabase Storage ("avatars" bucket)
 * when SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY are configured (Vercel / cloud).
 */

if (!function_exists('supabaseStorageConfigured')) {
    function supabaseStorageConfigured(): bool
    {
        $url = trim((string) (getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? '')));
        $key = trim((string) (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '')));
        return $url !== '' && $key !== '' && function_exists('curl_init');
    }

    function supabaseStorageBucket(): string
    {
        $b = trim((string) (getenv('SUPABASE_STORAGE_BUCKET') ?: ($_ENV['SUPABASE_STORAGE_BUCKET'] ?? 'avatars')));
        return $b !== '' ? $b : 'avatars';
    }

    /**
     * Upload binary image bytes to Supabase Storage and return the public CDN URL.
     */
    function supabaseUploadAvatar(string $binary, string $objectName, string $mimeType): ?string
    {
        $baseUrl = rtrim((string) (getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? '')), '/');
        $key     = trim((string) (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '')));
        $bucket  = supabaseStorageBucket();

        if ($baseUrl === '' || $key === '') {
            return null;
        }

        $safeObject = ltrim(str_replace(['..', '\\'], '', $objectName), '/');
        $endpoint   = "{$baseUrl}/storage/v1/object/{$bucket}/{$safeObject}";

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $binary,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$key}",
                "apikey: {$key}",
                "Content-Type: {$mimeType}",
                'x-upsert: true',
            ],
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            return "{$baseUrl}/storage/v1/object/public/{$bucket}/{$safeObject}";
        }

        error_log("Supabase Storage upload failed (HTTP {$code}): {$err} {$resp}");
        return null;
    }

    /**
     * Delete an existing Supabase Storage avatar if the URL matches our bucket.
     */
    function supabaseDeleteAvatar(?string $publicUrl): void
    {
        if (empty($publicUrl)) {
            return;
        }
        $baseUrl = rtrim((string) (getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? '')), '/');
        $key     = trim((string) (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '')));
        $bucket  = supabaseStorageBucket();
        $prefix  = "{$baseUrl}/storage/v1/object/public/{$bucket}/";

        if ($baseUrl === '' || $key === '' || !str_starts_with($publicUrl, $prefix)) {
            return;
        }

        $objectPath = ltrim(substr($publicUrl, strlen($prefix)), '/');
        if ($objectPath === '') {
            return;
        }

        $ch = curl_init("{$baseUrl}/storage/v1/object/{$bucket}");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_POSTFIELDS     => json_encode(['prefixes' => [$objectPath]]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$key}",
                "apikey: {$key}",
                'Content-Type: application/json',
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
