<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class CreateRealBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:create-real 
                            {property_id : ID da propriedade}
                            {channel_id : ID do canal (AIR298, BOO142, etc.)}
                            {--guest-name=João Silva : Nome do hóspede}
                            {--guest-email=joao@teste.com : Email do hóspede}
                            {--guest-phone=+5511999999999 : Telefone do hóspede}
                            {--check-in=+7 days : Data de check-in}
                            {--check-out=+9 days : Data de check-out}
                            {--adults=2 : Número de adultos}
                            {--children=0 : Número de crianças}
                            {--amount=200.00 : Valor da reserva}
                            {--currency=BRL : Moeda}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria reserva real em um canal específico (Airbnb, Booking.com, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $propertyId = $this->argument('property_id');
        $channelId = $this->argument('channel_id');

        $this->info('🎯 Criando reserva real em canal específico...');
        $this->newLine();

        // Buscar propriedade
        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("❌ Propriedade {$propertyId} não encontrada.");
            return;
        }

        // Buscar canal
        $channel = Channel::where('channel_id', $channelId)->first();
        if (!$channel) {
            $this->error("❌ Canal {$channelId} não encontrado.");
            return;
        }

        // Verificar conexão
        $propertyChannel = $property->getChannelConnection($channelId);
        if (!$propertyChannel) {
            $this->error("❌ Propriedade não está conectada ao canal {$channelId}.");
            return;
        }

        if (!$propertyChannel->isActive()) {
            $this->error("❌ Conexão com o canal {$channelId} não está ativa.");
            return;
        }

        $this->line("🏠 Propriedade: {$property->name}");
        $this->line("📡 Canal: {$channel->name}");
        $this->line("🔗 ID no Canal: {$propertyChannel->channel_property_id}");
        $this->newLine();

        // Criar reserva
        $this->createBooking($property, $channel, $propertyChannel);
    }

    private function createBooking(Property $property, Channel $channel, PropertyChannel $propertyChannel): void
    {
        $bookingData = $this->prepareBookingData($property, $channel, $propertyChannel);

        $this->info('📝 Preparando dados da reserva...');
        $this->line('   📤 Dados:');
        $this->line('   ' . json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            $response = $this->sendBookingRequest($channel, $bookingData);

            if ($response['success']) {
                $this->info('   🎉 RESERVA CRIADA COM SUCESSO!');
                $this->line("   ID da Reserva: {$response['booking_id']}");
                $this->line("   Número: {$response['booking_number']}");
                $this->line("   Status: {$response['status']}");

                // Salvar no banco local
                $this->saveBookingToDatabase($property, $channel, $propertyChannel, $bookingData, $response);

            } else {
                $this->error('   ❌ Erro na criação da reserva:');
                $this->line('   ' . $response['error']);
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('✅ Processo de criação de reserva concluído!');
    }

    private function prepareBookingData(Property $property, Channel $channel, PropertyChannel $propertyChannel): array
    {
        $checkIn = date('Y-m-d', strtotime($this->option('check-in')));
        $checkOut = date('Y-m-d', strtotime($this->option('check-out')));
        $guestName = $this->option('guest-name');
        $guestEmail = $this->option('guest-email');
        $guestPhone = $this->option('guest-phone');
        $adults = (int) $this->option('adults');
        $children = (int) $this->option('children');
        $amount = (float) $this->option('amount');
        $currency = $this->option('currency');

        // Separar nome e sobrenome
        $nameParts = explode(' ', $guestName);
        $firstName = $nameParts[0];
        $lastName = implode(' ', array_slice($nameParts, 1));

        $baseData = [
            'bookingNumber' => 'REAL-' . time() . '-' . substr($channel->channel_id, 0, 3),
            'propertyManager' => $property->property_manager_code,
            'channelPartnerReference' => 'CHANNEL-' . time() . '-' . $channel->channel_id,
            'propertyId' => $property->channel_property_id,
            'supplierPropertyId' => $property->supplier_property_id,
            'channelId' => $channel->channel_id,
            'rateplanId' => 1,
            'remarks' => "Reserva criada via sistema para canal {$channel->name}",
            'period' => [
                'arrivalDate' => $checkIn,
                'departureDate' => $checkOut
            ],
            'occupancy' => [
                'adults' => $adults,
                'children' => $children,
                'babies' => 0,
                'pets' => 0
            ],
            'stayPrice' => [
                'amount' => $amount,
                'currency' => $currency
            ],
            'mainBooker' => [
                'surname' => $lastName,
                'letters' => substr($firstName, 0, 1),
                'titleCode' => 'male',
                'firstName' => $firstName,
                'countryCode' => 'BR',
                'language' => 'pt',
                'zipCode' => '01234-567',
                'houseNumber' => '123',
                'street' => 'Rua das Flores',
                'place' => 'São Paulo',
                'phoneNumber' => $guestPhone,
                'email' => $guestEmail,
                'dateOfBirth' => '1980-01-01'
            ],
            'payment' => [
                'type' => 'default'
            ]
        ];

        // Usar formato padrão da NextPax para todos os canais
        return $this->formatForNextPax($baseData);
    }

    private function formatForAirbnb(array $baseData, PropertyChannel $propertyChannel): array
    {
        return [
            'query' => 'airbnbBooking',
            'payload' => [
                'listing_id' => $propertyChannel->channel_property_id,
                'room_id' => $propertyChannel->channel_room_id,
                'check_in' => $baseData['period']['arrivalDate'],
                'check_out' => $baseData['period']['departureDate'],
                'guests' => $baseData['occupancy']['adults'] + $baseData['occupancy']['children'],
                'guest_details' => [
                    'first_name' => $baseData['mainBooker']['firstName'],
                    'last_name' => $baseData['mainBooker']['surname'],
                    'email' => $baseData['mainBooker']['email'],
                    'phone' => $baseData['mainBooker']['phoneNumber'],
                ],
                'total_price' => $baseData['stayPrice']['amount'],
                'currency' => $baseData['stayPrice']['currency'],
                'message' => $baseData['remarks'],
            ]
        ];
    }

    private function formatForBooking(array $baseData, PropertyChannel $propertyChannel): array
    {
        return [
            'query' => 'bookingComReservation',
            'payload' => [
                'hotel_id' => $propertyChannel->channel_property_id,
                'room_id' => $propertyChannel->channel_room_id,
                'checkin' => $baseData['period']['arrivalDate'],
                'checkout' => $baseData['period']['departureDate'],
                'adults' => $baseData['occupancy']['adults'],
                'children' => $baseData['occupancy']['children'],
                'guest_info' => [
                    'first_name' => $baseData['mainBooker']['firstName'],
                    'last_name' => $baseData['mainBooker']['surname'],
                    'email' => $baseData['mainBooker']['email'],
                    'phone' => $baseData['mainBooker']['phoneNumber'],
                ],
                'total_amount' => $baseData['stayPrice']['amount'],
                'currency' => $baseData['stayPrice']['currency'],
                'special_requests' => $baseData['remarks'],
            ]
        ];
    }

    private function formatForHomeAway(array $baseData, PropertyChannel $propertyChannel): array
    {
        return [
            'query' => 'homeAwayReservation',
            'payload' => [
                'property_id' => $propertyChannel->channel_property_id,
                'unit_id' => $propertyChannel->channel_room_id,
                'start_date' => $baseData['period']['arrivalDate'],
                'end_date' => $baseData['period']['departureDate'],
                'adults' => $baseData['occupancy']['adults'],
                'children' => $baseData['occupancy']['children'],
                'guest_details' => [
                    'first_name' => $baseData['mainBooker']['firstName'],
                    'last_name' => $baseData['mainBooker']['surname'],
                    'email' => $baseData['mainBooker']['email'],
                    'phone' => $baseData['mainBooker']['phoneNumber'],
                ],
                'total_price' => $baseData['stayPrice']['amount'],
                'currency' => $baseData['stayPrice']['currency'],
                'notes' => $baseData['remarks'],
            ]
        ];
    }

    private function formatForNextPax(array $baseData): array
    {
        return [
            'query' => 'propertyManagerBooking',
            'payload' => $baseData
        ];
    }

    private function sendBookingRequest(Channel $channel, array $bookingData): array
    {
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');

        $this->line("   📡 Enviando para: {$baseUrl}/bookings");

        $response = Http::withHeaders([
            'X-Api-Token' => $apiToken,
            'Content-Type' => 'application/json',
        ])->post($baseUrl . '/bookings', $bookingData);

        $this->line("   📡 Status HTTP: {$response->status()}");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'booking_id' => $data['data']['id'] ?? 'N/A',
                'booking_number' => $data['data']['bookingNumber'] ?? 'N/A',
                'status' => $data['data']['state'] ?? 'N/A',
                'response' => $data
            ];
        } else {
            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status()
            ];
        }
    }

    private function saveBookingToDatabase(Property $property, Channel $channel, PropertyChannel $propertyChannel, array $bookingData, array $response): void
    {
        $payload = $bookingData['payload'] ?? $bookingData;

        Booking::create([
            'nextpax_booking_id' => $response['booking_id'],
            'booking_number' => $response['booking_number'],
            'channel_partner_reference' => $payload['channelPartnerReference'] ?? null,
            'channel_id' => $channel->channel_id,
            'property_id' => $property->property_id,
            'supplier_property_id' => $property->supplier_property_id,
            'property_manager_code' => $property->property_manager_code,
            'guest_first_name' => $payload['mainBooker']['firstName'] ?? null,
            'guest_surname' => $payload['mainBooker']['surname'] ?? null,
            'guest_email' => $payload['mainBooker']['email'] ?? null,
            'guest_phone' => $payload['mainBooker']['phoneNumber'] ?? null,
            'guest_country_code' => $payload['mainBooker']['countryCode'] ?? null,
            'guest_language' => $payload['mainBooker']['language'] ?? null,
            'check_in_date' => $payload['period']['arrivalDate'] ?? null,
            'check_out_date' => $payload['period']['departureDate'] ?? null,
            'adults' => $payload['occupancy']['adults'] ?? 0,
            'children' => $payload['occupancy']['children'] ?? 0,
            'babies' => $payload['occupancy']['babies'] ?? 0,
            'pets' => $payload['occupancy']['pets'] ?? 0,
            'total_amount' => $payload['stayPrice']['amount'] ?? 0,
            'currency' => $payload['stayPrice']['currency'] ?? 'BRL',
            'payment_type' => $payload['payment']['type'] ?? 'default',
            'rate_plan_id' => $payload['rateplanId'] ?? null,
            'status' => $response['status'],
            'remarks' => $payload['remarks'] ?? null,
            'api_response' => $response['response'] ?? null,
            'api_payload' => $bookingData,
            'sync_status' => 'synced',
            'synced_at' => now(),
        ]);

        $this->line("   💾 Reserva salva no banco de dados local");
    }
}