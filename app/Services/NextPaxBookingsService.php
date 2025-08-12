<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NextPaxBookingsService
{
    private $baseUrl;
    private $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $this->apiToken = config('services.nextpax.token');
    }

    private function makeRequest($method, $endpoint, $data = null, $params = [])
    {
        $request = Http::withHeaders([
            'X-Api-Token' => $this->apiToken,
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
            default:
                throw new \Exception('Método HTTP não suportado');
        }

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro na API: ' . $response->body());
    }

    // Bookings API Methods
    public function getBookings($propertyManager, $onlyNew = false, $modifiedSince = null)
    {
        $params = [
            'propertyManager' => $propertyManager,
            'onlyNew' => $onlyNew,
        ];

        if ($modifiedSince) {
            $params['modifiedSince'] = $modifiedSince;
        }

        return $this->makeRequest('GET', '/bookings', null, $params);
    }

    public function getBooking($bookingId, $propertyManager, $showCancellationOptions = false)
    {
        $params = [
            'propertyManager' => $propertyManager,
        ];

        if ($showCancellationOptions) {
            $params['showCancellationOptions'] = $showCancellationOptions;
        }

        return $this->makeRequest('GET', "/bookings/{$bookingId}", null, $params);
    }

    public function createBooking($data)
    {
        return $this->makeRequest('POST', '/bookings', $data);
    }

    public function updateBooking($bookingId, $propertyManager, $data)
    {
        $params = ['propertyManager' => $propertyManager];
        return $this->makeRequest('PUT', "/bookings/{$bookingId}", $data, $params);
    }

    public function cancelBooking($bookingId, $data = [])
    {
        return $this->makeRequest('POST', "/cancellation/{$bookingId}", $data);
    }

    public function getBookingPaymentDetails($bookingId, $withCvc = false)
    {
        $params = [];
        if ($withCvc) {
            $params['withCvc'] = $withCvc;
        }

        return $this->makeRequest('GET', "/bookings/{$bookingId}/payment-details", null, $params);
    }

    /**
     * Retrieve supplier property manager payment provider setup
     */
    public function getSupplierPaymentProvider(string $propertyManager)
    {
        return $this->makeRequest('GET', "/supplier-payment-provider/{$propertyManager}");
    }

    /**
     * changeBookingState: Accept/Deny Request-to-Book
     */
    public function changeBookingState(string $propertyManagerCode, string $channelId, string $bookingId, string $state, ?string $reason = null, ?string $bookingNumber = null)
    {
        $payload = [
            'bookingId' => $bookingId,
            'state' => $state,
        ];
        if ($reason) {
            $payload['reason'] = $reason;
        }
        if ($bookingNumber) {
            $payload['bookingNumber'] = $bookingNumber;
        }

        $requestData = [
            'query' => 'changeBookingState',
            'channelId' => $channelId,
            'propertyManager' => $propertyManagerCode,
            'payload' => $payload,
        ];

        return $this->makeRequest('POST', '/change-booking-state', $requestData);
    }

    public function getBookingAlterations($propertyManager, $bookingId = null, $onlyNew = false, $modifiedSince = null)
    {
        $params = [
            'propertyManager' => $propertyManager,
        ];

        if ($bookingId) {
            $params['bookingId'] = $bookingId;
        }

        if ($onlyNew) {
            $params['onlyNew'] = $onlyNew;
        }

        if ($modifiedSince) {
            $params['modifiedSince'] = $modifiedSince;
        }

        return $this->makeRequest('GET', '/booking-alterations', null, $params);
    }

    public function updateAlteration($alterationId, $data)
    {
        $requestData = [
            'query' => 'propertyManagerAlteration',
            'payload' => $data,
        ];

        return $this->makeRequest('POST', "/booking-alterations/{$alterationId}", $requestData);
    }
} 