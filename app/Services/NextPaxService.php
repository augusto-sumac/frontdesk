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

    /**
     * Ativar propriedade na NextPax
     */
    public function activateProperty($propertyId)
    {
        try {
            // Para ativar uma propriedade, precisamos definir disponibilidade
            // Uma propriedade com disponibilidade > 0 é considerada ativa
            $data = [
                'data' => [
                    [
                        'fromDate' => date('Y-m-d'),
                        'untilDate' => date('Y-m-d', strtotime('+1 year')),
                        'quantity' => 1, // 1 unidade disponível
                        'restrictions' => [
                            'minStay' => 1,
                            'maxStay' => 30,
                            'departuresAllowed' => true,
                            'arrivalsAllowed' => true
                        ]
                    ]
                ]
            ];
            
            return $this->makeRequest('POST', "/ari/availability/{$propertyId}", $data);
        } catch (\Exception $e) {
            Log::error('Erro ao ativar propriedade na NextPax:', [
                'property_id' => $propertyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao ativar propriedade: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Atualizar preços da propriedade na NextPax
     */
    public function updatePropertyPricing($propertyId, $pricingData)
    {
        try {
            Log::info('Tentando atualizar preços na NextPax:', [
                'property_id' => $propertyId,
                'pricing_data' => $pricingData
            ]);

            // Construir payload para atualização de preços usando o endpoint correto
            $payload = [
                'currency' => $pricingData['pricing']['basePrice']['currency'],
                'pricingType' => 'default',
                'rates' => [
                    [
                        'fromDate' => date('Y-m-d'),
                        'untilDate' => date('Y-m-d', strtotime('+1 year')),
                        'persons' => 1, // Preço base para 1 pessoa
                        'minStay' => 1,
                        'maxStay' => 30,
                        'prices' => [
                            'nightlyPrice' => (int)($pricingData['pricing']['basePrice']['amount'] * 100), // Preço em centavos
                        ]
                    ]
                ]
            ];

            // Adicionar preços opcionais se existirem
            if (isset($pricingData['pricing']['weeklyRate']) && $pricingData['pricing']['weeklyRate'] > 0) {
                $payload['rates'][0]['prices']['weeklyPrice'] = (int)($pricingData['pricing']['weeklyRate'] * 100);
            }
            if (isset($pricingData['pricing']['monthlyRate']) && $pricingData['pricing']['monthlyRate'] > 0) {
                $payload['rates'][0]['prices']['monthlyPrice'] = (int)($pricingData['pricing']['monthlyRate'] * 100);
            }

            Log::info('Payload para NextPax:', [
                'endpoint' => "/ari/rates-periodic/{$propertyId}",
                'payload' => $payload
            ]);

            $response = $this->makeRequest('POST', "/ari/rates-periodic/{$propertyId}", $payload);
            
            Log::info('Resposta da NextPax:', [
                'property_id' => $propertyId,
                'response' => $response
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar preços na NextPax:', [
                'property_id' => $propertyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar preços: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Buscar dados completos da propriedade na NextPax
     */
    public function getPropertyComplete($propertyId)
    {
        try {
            $result = [
                'property' => null,
                'pricing' => null,
                'availability' => null,
                'status' => 'unknown'
            ];

            // Buscar dados básicos da propriedade
            try {
                $propertyResponse = $this->getProperty($propertyId);
                if (isset($propertyResponse['data'])) {
                    $result['property'] = $propertyResponse['data'];
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar dados da propriedade:', ['error' => $e->getMessage()]);
            }

            // Buscar dados de preços
            try {
                $pricingResponse = $this->getRates($propertyId);
                if (isset($pricingResponse['data'])) {
                    $result['pricing'] = $pricingResponse['data'];
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar preços da propriedade:', ['error' => $e->getMessage()]);
            }

            // Buscar dados de disponibilidade
            try {
                $availabilityResponse = $this->getAvailability($propertyId);
                if (isset($availabilityResponse['data'])) {
                    $result['availability'] = $availabilityResponse['data'];
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar disponibilidade da propriedade:', ['error' => $e->getMessage()]);
            }

            // Determinar status baseado na disponibilidade
            if (isset($result['availability']) && !empty($result['availability'])) {
                $hasAvailability = false;
                foreach ($result['availability'] as $avail) {
                    if (isset($avail['quantity']) && $avail['quantity'] > 0) {
                        $hasAvailability = true;
                        break;
                    }
                }
                $result['status'] = $hasAvailability ? 'active' : 'inactive';
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados completos da propriedade:', [
                'property_id' => $propertyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Erro ao buscar dados: ' . $e->getMessage()
            ];
        }
    }

    // Channel Management Methods
    
    /**
     * Get properties with channel configuration (uses same endpoint as getProperties)
     */
    public function getPropertiesWithChannels($propertyManager = null)
    {
        // Usar o mesmo método que já funciona
        return $this->getProperties($propertyManager);
    }
    
    /**
     * Get property channel status from NextPax
     */
    public function getPropertyChannelStatus($propertyId)
    {
        try {
            // Usar endpoint de channel management se disponível
            return $this->makeRequest('GET', "/channel-management/property/{$propertyId}");
        } catch (\Exception $e) {
            // Fallback para endpoint de propriedade normal
            try {
                $property = $this->getProperty($propertyId);
                if (isset($property['property'])) {
                    return [
                        'property' => $property['property'],
                        'channels' => $property['property']['channels'] ?? []
                    ];
                }
                return $property;
            } catch (\Exception $fallbackException) {
                Log::error('Erro ao buscar status do canal da propriedade:', [
                    'property_id' => $propertyId,
                    'error' => $e->getMessage(),
                    'fallback_error' => $fallbackException->getMessage()
                ]);
                
                return [
                    'error' => 'Erro ao buscar status: ' . $e->getMessage()
                ];
            }
        }
    }
    
    /**
     * Update property channel configuration
     */
    public function updatePropertyChannelConfig($propertyId, $channelData)
    {
        try {
            return $this->makeRequest('PUT', "/channel-management/property/{$propertyId}", $channelData);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configuração do canal:', [
                'property_id' => $propertyId,
                'channel_data' => $channelData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Erro ao atualizar configuração: ' . $e->getMessage()
            ];
        }
    }
} 