<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Util;

use RuntimeException;

/**
 * Simple HTTP client for fetching the REST API schema.
 */
final class HttpClient
{
    /**
     * Fetch the REST API schema from a WordPress site.
     *
     * @param string $baseUrl The base URL of the WordPress site
     * @param string|null $authToken Optional Bearer token for authentication
     * @return array<string, mixed> The decoded JSON schema
     * @throws RuntimeException If the request fails
     */
    public static function fetchSchema(string $baseUrl, ?string $authToken = null): array
    {
        $url = rtrim($baseUrl, '/') . '/wp-json';

        $context = self::createContext($authToken);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new RuntimeException(
                sprintf('Failed to fetch schema from %s: %s', $url, $error['message'] ?? 'Unknown error')
            );
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                sprintf('Failed to parse JSON response: %s', json_last_error_msg())
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException('Invalid schema response: expected array');
        }

        return $data;
    }

    /**
     * Fetch endpoint schema via OPTIONS request.
     *
     * @param string $url The full endpoint URL
     * @param string|null $authToken Optional Bearer token for authentication
     * @return array<string, mixed>|null The schema data or null if not available
     */
    public static function fetchEndpointSchema(string $url, ?string $authToken = null): ?array
    {
        $context = self::createContext($authToken, 'OPTIONS');
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return null;
        }

        return $data['schema'] ?? null;
    }

    /**
     * Create a stream context for the HTTP request.
     *
     * @param string|null $authToken Optional Bearer token
     * @param string $method HTTP method (GET, OPTIONS, etc.)
     * @return resource The stream context
     */
    private static function createContext(?string $authToken, string $method = 'GET'): mixed
    {
        $headers = [
            'Accept: application/json',
            'User-Agent: WooCommerce-RestApiDocs/1.0',
        ];

        if ($authToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $authToken;
        }

        return stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'timeout' => 30,
                'ignore_errors' => false,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
    }
}
