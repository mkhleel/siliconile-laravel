<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for sending push notifications via Pushover API.
 *
 * @see https://pushover.net/api
 */
class PushoverService
{
    private const API_ENDPOINT = 'https://api.pushover.net/1/messages.json';

    /**
     * Send a push notification via Pushover.
     *
     * @param  array{
     *     message: string,
     *     title?: string,
     *     priority?: int,
     *     url?: string,
     *     url_title?: string,
     *     device?: string,
     *     sound?: string,
     *     html?: bool
     * }  $data
     * @return array{status: int, request?: string, receipt?: string, errors?: array}
     */
    public function send(array $data): array
    {
        $token = config('services.pushover.token');
        $user = config('services.pushover.user');

        if (empty($token) || empty($user)) {
            Log::warning('Pushover credentials not configured');

            return [
                'status' => 0,
                'errors' => ['Pushover credentials not configured'],
            ];
        }

        try {
            $response = Http::asForm()->post(self::API_ENDPOINT, [
                'token' => $token,
                'user' => $user,
                'message' => $data['message'],
                'title' => $data['title'] ?? config('app.name'),
                'priority' => $data['priority'] ?? 0,
                'url' => $data['url'] ?? null,
                'url_title' => $data['url_title'] ?? null,
                'device' => $data['device'] ?? null,
                'sound' => $data['sound'] ?? null,
                'html' => isset($data['html']) && $data['html'] ? 1 : 0,
            ]);

            $result = $response->json();

            if (! $response->successful()) {
                Log::error('Pushover notification failed', [
                    'status' => $response->status(),
                    'response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Pushover notification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Send a high priority notification.
     */
    public function sendHighPriority(string $message, ?string $title = null, ?string $url = null): array
    {
        return $this->send([
            'message' => $message,
            'title' => $title,
            'priority' => 1,
            'url' => $url,
        ]);
    }

    /**
     * Send an emergency notification (requires acknowledgment).
     *
     * @param  array{retry?: int, expire?: int, callback?: string}  $emergencyOptions
     */
    public function sendEmergency(
        string $message,
        ?string $title = null,
        array $emergencyOptions = []
    ): array {
        return $this->send([
            'message' => $message,
            'title' => $title,
            'priority' => 2,
            'retry' => $emergencyOptions['retry'] ?? 30,
            'expire' => $emergencyOptions['expire'] ?? 3600,
            'callback' => $emergencyOptions['callback'] ?? null,
        ]);
    }

    /**
     * Test the Pushover connection.
     */
    public function testConnection(): array
    {
        return $this->send([
            'message' => 'Test notification from '.config('app.name'),
            'title' => 'Connection Test',
        ]);
    }

    /**
     * Check if Pushover is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.pushover.token')) && ! empty(config('services.pushover.user'));
    }
}
