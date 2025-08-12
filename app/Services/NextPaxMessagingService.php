<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NextPaxMessagingService
{
    private $baseUrl;
    private $accessToken;
    private $clientId;
    private $clientSecret;
    private $authUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.nextpax.messaging_api_base', 'https://messaging.sandbox.nextpax.app/supply');
        $this->clientId = config('services.nextpax.client_id');
        $this->clientSecret = config('services.nextpax.client_secret');
        $this->authUrl = config('services.nextpax.auth_url');
        $this->accessToken = null; // Será obtido via OAuth2
    }

    private function getAccessToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::asForm()->post($this->authUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            $this->accessToken = $response->json('access_token');
            return $this->accessToken;
        }

        throw new \Exception('Falha ao obter token de acesso: ' . $response->body());
    }

    private function makeRequest($method, $endpoint, $data = null, $params = [])
    {
        $token = $this->getAccessToken();
        
        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ]);

        $url = $this->baseUrl . $endpoint;

        switch ($method) {
            case 'GET':
                $response = $request->get($url, $params);
                break;
            case 'POST':
                $response = $request->post($url, $data);
                break;
            case 'PUT':
                $response = $request->put($url, $data);
                break;
            case 'DELETE':
                $response = $request->delete($url);
                break;
            default:
                throw new \Exception('Método HTTP não suportado');
        }

        if ($response->successful()) {
            return $response->json();
        }

        // Log detailed error information
        \Log::error('NextPax Messaging API Error', [
            'url' => $url,
            'method' => $method,
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
        ]);

        throw new \Exception('Erro na API: Status ' . $response->status() . ' - ' . $response->body());
    }

    // Messaging API Methods
    public function getThreads($hasNewMessages = null, $channelId = null, $channelPartnerReference = null, $fromTimestamp = null, $untilTimestamp = null, $offset = 0, $limit = 100, $orderBy = 'time_desc', $status = null)
    {
        $params = [
            'offset' => $offset,
            'limit' => $limit,
            'orderBy' => $orderBy,
        ];

        if ($hasNewMessages !== null) {
            $params['hasNewMessages'] = $hasNewMessages;
        }

        if ($channelId) {
            $params['channelId'] = $channelId;
        }

        if ($channelPartnerReference) {
            $params['channelPartnerReference'] = $channelPartnerReference;
        }

        if ($fromTimestamp) {
            $params['fromTimestamp'] = $fromTimestamp;
        }

        if ($untilTimestamp) {
            $params['untilTimestamp'] = $untilTimestamp;
        }

        if ($status) {
            $params['status'] = $status;
        }

        return $this->makeRequest('GET', '/threads', null, $params);
    }

    public function getThread($threadId)
    {
        return $this->makeRequest('GET', "/threads/{$threadId}");
    }

    public function createThread($data)
    {
        return $this->makeRequest('POST', '/threads', $data);
    }

    public function getMessages($fromTimestamp, $onlyNew = false, $untilTimestamp = null, $offset = 0, $limit = 100)
    {
        $params = [
            'fromTimestamp' => $fromTimestamp,
            'onlyNew' => $onlyNew,
            'offset' => $offset,
            'limit' => $limit,
        ];

        if ($untilTimestamp) {
            $params['untilTimestamp'] = $untilTimestamp;
        }

        return $this->makeRequest('GET', '/messages', null, $params);
    }

    public function getThreadMessages($threadId)
    {
        return $this->makeRequest('GET', "/messages/{$threadId}");
    }

    public function getMessage($messageId)
    {
        return $this->makeRequest('GET', "/message/{$messageId}");
    }

    public function sendMessage($data)
    {
        return $this->makeRequest('POST', '/messages', $data);
    }

    public function updateMessage($messageId, $data)
    {
        return $this->makeRequest('POST', "/message/{$messageId}", $data);
    }

    public function getMessagingSettings($propertyManagerCode)
    {
        return $this->makeRequest('GET', "/settings/{$propertyManagerCode}");
    }

    public function updateMessagingSettings($propertyManagerCode, $data)
    {
        return $this->makeRequest('POST', "/settings/{$propertyManagerCode}", $data);
    }

    public function getSettingsDefinitions($channelId = null)
    {
        $params = [];
        if ($channelId) {
            $params['channelId'] = $channelId;
        }

        return $this->makeRequest('GET', '/settings-definitions', null, $params);
    }

    public function getScheduledMessages($propertyManagerCode)
    {
        return $this->makeRequest('GET', "/scheduled-message/{$propertyManagerCode}");
    }

    public function createScheduledMessage($propertyManagerCode, $data)
    {
        return $this->makeRequest('POST', "/scheduled-message/{$propertyManagerCode}", $data);
    }

    public function deleteScheduledMessage($propertyManagerCode, $scheduledMessageId)
    {
        return $this->makeRequest('DELETE', "/scheduled-message/{$propertyManagerCode}/{$scheduledMessageId}");
    }

    public function testAuthentication()
    {
        try {
            $token = $this->getAccessToken();
            return [
                'success' => true,
                'token' => $token
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 