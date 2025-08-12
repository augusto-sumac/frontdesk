<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NextPaxService
{
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $senderId;
    private $authUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.nextpax.supply_api_base', 'https://supply.sandbox.nextpax.app/api/v1');
        $this->clientId = config('services.nextpax.client_id');
        $this->clientSecret = config('services.nextpax.client_secret');
        $this->senderId = config('services.nextpax.sender_id');
        $this->authUrl = config('services.nextpax.auth_url');

        // Fallback inteligente para URL de OAuth2 baseado no server do Supply
        if (empty($this->authUrl) || str_starts_with((string) $this->authUrl, '/')) {
            $origin = $this->extractOriginFromBaseUrl($this->baseUrl);
            $isSandbox = str_contains($origin, 'sandbox');
            $realm = $isSandbox ? 'supply-api-sandbox' : 'supply-api-production';
            $this->authUrl = rtrim($origin, '/') . "/auth/realms/{$realm}/protocol/openid-connect/token";
            Log::info('NextPax auth URL resolved from supply_api_base', ['auth_url' => $this->authUrl]);
        }
    }

    private function extractOriginFromBaseUrl(string $baseUrl): string
    {
        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        return $scheme . '://' . $host . $port;
    }

    private function tokenCacheKey(): string
    {
        // Diferenciar por client_id para evitar colisões entre ambientes
        return 'nextpax_access_token_' . md5((string) $this->clientId);
    }

    private function getAccessToken()
    {
        $cacheKey = $this->tokenCacheKey();
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::asForm()->post($this->authUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            $token = $response->json('access_token');
            // Sandbox expira em 18000, production 3600 — usamos 3500 como seguro
            Cache::put($cacheKey, $token, 3500);
            return $token;
        }

        Log::error('NextPax OAuth error', [
            'status' => $response->status(),
            'body' => $response->body(),
            'url' => $this->authUrl,
            'client_id_present' => !empty($this->clientId),
        ]);

        throw new \Exception('Falha ao obter token de acesso NextPax (status ' . $response->status() . ')');
    }

    private function makeRequest($method, $endpoint, $data = null, bool $didRetry = false)
    {
        $token = $this->getAccessToken();
        
        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $url = $this->baseUrl . $endpoint;

        switch ($method) {
            case 'GET':
                $response = $request->get($url, $data);
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

        // Se 401, limpar cache de token e tentar uma vez novamente
        if ($response->status() === 401 && !$didRetry) {
            Cache::forget($this->tokenCacheKey());
            Log::warning('NextPax API 401 - retrying once with fresh token', [
                'endpoint' => $endpoint,
            ]);
            return $this->makeRequest($method, $endpoint, $data, true);
        }

        Log::error('NextPax API error', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $response->body(),
            'request_data' => $method === 'GET' ? $data : (is_array($data) ? array_intersect_key($data, array_flip(['companyName','general','contacts','ratesAndAvailabilitySettings'])) : null),
        ]);

        $message = 'Erro na API (status ' . $response->status() . ')';
        $body = trim((string) $response->body());
        if ($body !== '') {
            $message .= ': ' . $body;
        }

        throw new \Exception($message);
    }

    // Supply API Methods
    public function getProperties($propertyManager = null, $limit = 100, $offset = 0)
    {
        $params = [
            'limit' => $limit,
            'offset' => $offset,
        ];

        if ($propertyManager) {
            $params['propertyManager'] = $propertyManager;
        }

        return $this->makeRequest('GET', '/content/properties/index', $params);
    }

    public function getProperty($propertyId)
    {
        return $this->makeRequest('GET', "/content/properties/{$propertyId}");
    }

    public function createProperty($data)
    {
        return $this->makeRequest('POST', '/content/properties', $data);
    }

    public function updateProperty($propertyId, $data)
    {
        return $this->makeRequest('POST', "/content/properties/{$propertyId}", $data);
    }

    public function deleteProperty($propertyId)
    {
        return $this->makeRequest('DELETE', "/content/properties/{$propertyId}");
    }

    public function getPropertySubrooms($propertyId)
    {
        return $this->makeRequest('GET', "/content/properties/{$propertyId}/subrooms");
    }

    public function updatePropertySubrooms($propertyId, $data)
    {
        return $this->makeRequest('POST', "/content/properties/{$propertyId}/subrooms", $data);
    }

    public function getAvailability($propertyId)
    {
        return $this->makeRequest('GET', "/ari/availability/{$propertyId}");
    }

    public function updateAvailability($propertyId, $data)
    {
        return $this->makeRequest('POST', "/ari/availability/{$propertyId}", $data);
    }

    public function getRates($propertyId)
    {
        return $this->makeRequest('GET', "/ari/rates-periodic/{$propertyId}");
    }

    public function updateRates($propertyId, $data)
    {
        return $this->makeRequest('POST', "/ari/rates-periodic/{$propertyId}", $data);
    }

    public function getRatePlans($propertyId)
    {
        return $this->makeRequest('GET', "/ari/rate-plans/{$propertyId}");
    }

    public function updateRatePlans($propertyId, $data)
    {
        return $this->makeRequest('POST', "/ari/rate-plans/{$propertyId}", $data);
    }

    public function getPropertyImages(string $propertyId)
    {
        return $this->makeRequest('GET', "/content/properties/{$propertyId}/images");
    }

    public function updatePropertyImages(string $propertyId, array $imagesPayload)
    {
        // imagesPayload expected format per swagger: ['images' => [[ 'typeCode'=>..., 'url'=>..., 'caption'=>..., 'orderNumber'=>... ]]]
        return $this->makeRequest('POST', "/content/properties/{$propertyId}/images", $imagesPayload);
    }

    public function getMappingCodes(array $categories = []): array
    {
        $params = [];
        if (!empty($categories)) {
            $params['category'] = $categories; // array accepted per swagger
        }
        return $this->makeRequest('GET', '/constants/mapping-codes', $params);
    }

    // ===== Suppliers / Property Manager =====
    public function createPropertyManager(array $data)
    {
        Log::info('Creating Property Manager (NextPax)', [
            'companyName' => $data['companyName'] ?? null,
            'general_keys' => isset($data['general']) ? array_keys($data['general']) : [],
        ]);
        return $this->makeRequest('POST', '/suppliers/property-manager', $data);
    }

    public function getPropertyManager(string $propertyManagerCode)
    {
        return $this->makeRequest('GET', "/suppliers/property-manager/{$propertyManagerCode}");
    }

    public function updatePropertyManager(string $propertyManagerCode, array $data)
    {
        return $this->makeRequest('POST', "/suppliers/property-manager/{$propertyManagerCode}", $data);
    }
} 