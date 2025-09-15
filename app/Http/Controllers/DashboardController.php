<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NextPaxService;
use App\Services\NextPaxBookingsService;
use App\Services\NextPaxMessagingService;
use App\Models\User;
use App\Models\Property;
use App\Models\Booking;

class DashboardController extends Controller
{
    private $nextPaxService;
    private $bookingsService;
    private $messagingService;

    public function __construct(
        NextPaxService $nextPaxService,
        NextPaxBookingsService $bookingsService,
        NextPaxMessagingService $messagingService
    ) {
        $this->nextPaxService = $nextPaxService;
        $this->bookingsService = $bookingsService;
        $this->messagingService = $messagingService;
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return view('dashboard', [
                    'error' => 'Usuário não possui código de gerenciador de propriedades configurado',
                    'user' => $user
                ]);
            }

            // Buscar dados reais da base de dados local
            $bookings = $this->getRecentBookingsFromDatabase($propertyManagerCode);
            $messages = $this->getRecentMessagesFromDatabase($propertyManagerCode);
            $financialReport = $this->getFinancialReportFromDatabase($propertyManagerCode);

            return view('dashboard', compact(
                'user',
                'bookings',
                'messages',
                'financialReport'
            ));

        } catch (\Exception $e) {
            return view('dashboard', [
                'error' => 'Erro ao carregar dados: ' . $e->getMessage(),
                'user' => Auth::user()
            ]);
        }
    }

    // ===== RESERVAS CRUD =====
    public function bookings()
    {
        try {
            $user = Auth::user();
            $propertyId = $user->property_manager_code;

            if (!$propertyId) {
                return view('bookings.index', ['error' => 'Usuário não possui propriedade associada']);
            }

            // Fetch bookings from database (with API data)
            $bookings = Booking::where('property_manager_code', $propertyId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();

            // Load properties from database (with supplierPropertyId)
            $properties = Property::where('channel_type', 'nextpax')
                ->where('property_manager_code', $propertyId)
                ->select(
                    'id as localId', 
                    'property_id as id', 
                    'name', 
                    'currency as baseCurrency', 
                    'supplier_property_id as supplierPropertyId',
                    'base_price as basePrice',
                    'max_occupancy',
                    'max_adults',
                    'max_children',
                    'bedrooms',
                    'bathrooms',
                    'check_in_from',
                    'check_in_until',
                    'check_out_from',
                    'check_out_until'
                )
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray();

            return view('bookings.index', compact('bookings', 'properties'));

        } catch (\Exception $e) {
            return view('bookings.index', ['error' => 'Erro ao carregar reservas: ' . $e->getMessage()]);
        }
    }

    public function showBooking($bookingId)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            // Get booking from database
            $booking = Booking::where('id', $bookingId)
                ->where('property_manager_code', $propertyManagerCode)
                ->first();

            if (!$booking) {
                return response()->json(['error' => 'Reserva não encontrada'], 404);
            }

            // Try to get payment details from API if available
            $paymentDetails = [];
            if ($booking->nextpax_booking_id) {
                try {
                    $paymentResponse = $this->bookingsService->getBookingPaymentDetails($booking->nextpax_booking_id);
                    $paymentDetails = $paymentResponse['data'] ?? $paymentResponse ?? [];
                } catch (\Exception $e) {
                    // Payment details not available, continue with empty data
                }
            }

            return view('bookings.show', compact('booking', 'paymentDetails'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao carregar reserva: ' . $e->getMessage()], 500);
        }
    }

    public function createBooking(Request $request)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            $data = $request->validate([
                'guestFirstName' => 'required|string',
                'guestSurname' => 'required|string',
                'guestEmail' => 'required|email',
                'checkIn' => 'required|date',
                'checkOut' => 'required|date|after:checkIn',
                'adults' => 'required|integer|min:1',
                'children' => 'required|integer|min:0',
                'totalPrice' => 'required|numeric|min:0',
                'currency' => 'sometimes|string|size:3',
                'paymentType' => 'sometimes|string|in:default,creditcard',
                'roomType' => 'nullable|string',
                'propertyId' => 'required|string',
                'remarks' => 'nullable|string'
            ]);

            $bookingNumber = (string) random_int(1000000, 9999999);
            $channelPartnerReference = (string) \Illuminate\Support\Str::uuid();
            $channelId = 'AIR298';

            // Get property from database to get supplierPropertyId
            $property = null;
            
            // First try to find by NextPax UUID (property_id)
            if (strlen($data['propertyId']) === 36 && strpos($data['propertyId'], '-') !== false) {
                // Looks like a UUID, search by property_id
                $property = Property::where('property_id', $data['propertyId'])->first();
                
                if (!$property) {
                    return response()->json(['error' => 'Propriedade não encontrada na base de dados. UUID: ' . $data['propertyId']], 404);
                }
            } else {
                // Try by local ID first, then by supplier_property_id
                $property = Property::where('id', $data['propertyId'])->first();
                
                if (!$property) {
                    $property = Property::where('supplier_property_id', $data['propertyId'])->first();
                }
                    
                if (!$property) {
                    return response()->json(['error' => 'Propriedade não encontrada na base de dados. ID fornecido: ' . $data['propertyId']], 404);
                }
            }

            // Send both propertyId and supplierPropertyId as required by the API
            $payload = [
                'query' => 'propertyManagerBooking',
                'payload' => [
                    'bookingNumber' => $bookingNumber,
                    'propertyManager' => $propertyManagerCode,
                    'channelPartnerReference' => $channelPartnerReference,
                    'channelId' => $channelId,
                    'propertyId' => $property->property_id, // UUID do NextPax da propriedade encontrada
                    'supplierPropertyId' => $property->supplier_property_id, // Our internal ID
                    'period' => [
                        'arrivalDate' => date('Y-m-d', strtotime($data['checkIn'])),
                        'departureDate' => date('Y-m-d', strtotime($data['checkOut'])),
                    ],
                    'occupancy' => [
                        'adults' => (int) $data['adults'],
                        'children' => (int) $data['children'],
                        'babies' => 0,
                        'pets' => 0,
                    ],
                    'stayPrice' => [
                        'amount' => (float) ($property->base_price ?? $data['totalPrice']),
                        'currency' => $property->currency ?? ($data['currency'] ?? 'BRL'),
                    ],
                    'mainBooker' => [
                        'surname' => $data['guestSurname'],
                        'letters' => substr($data['guestFirstName'], 0, 1),
                        'firstName' => $data['guestFirstName'],
                        'countryCode' => 'BR',
                        'language' => 'pt',
                        'zipCode' => '00000000',
                        'houseNumber' => '1',
                        'street' => 'Rua Exemplo',
                        'place' => 'São Paulo',
                        'phoneNumber' => '0000000000',
                        'email' => $data['guestEmail'],
                        'dateOfBirth' => '1980-01-01',
                        'titleCode' => 'male',
                    ],
                    'payment' => [
                        'type' => $data['paymentType'] ?? 'default',
                    ],
                ],
            ];

            // Add optional fields
            if (!empty($data['remarks'])) {
                $payload['payload']['remarks'] = $data['remarks'];
            }

            // Try to create booking via API first
            $apiSuccess = false;
            $nextPaxBookingId = null;
            $apiError = null;
            $apiResponse = null;

            try {
                $apiResponse = $this->bookingsService->createBooking($payload);
                
                if (isset($apiResponse['result']) && $apiResponse['result'] === 'success') {
                    $apiSuccess = true;
                    $nextPaxBookingId = $apiResponse['data']['bookingId'] ?? null;
                } else {
                    $apiError = 'API returned failure: ' . json_encode($apiResponse);
                }
            } catch (\Exception $e) {
                $apiError = 'API exception: ' . $e->getMessage();
            }

            // Save booking to database regardless of API success
            $dbBooking = new Booking();
            $dbBooking->nextpax_booking_id = $nextPaxBookingId;
            $dbBooking->booking_number = $data['bookingNumber'] ?? $bookingNumber;
            $dbBooking->channel_partner_reference = $channelPartnerReference;
            $dbBooking->channel_id = $channelId;
            $dbBooking->property_id = $property->property_id; // UUID do NextPax da propriedade
            $dbBooking->supplier_property_id = $property->supplier_property_id;
            $dbBooking->property_manager_code = $propertyManagerCode;
            $dbBooking->guest_first_name = $data['guestFirstName'];
            $dbBooking->guest_surname = $data['guestSurname'];
            $dbBooking->guest_email = $data['guestEmail'];
            $dbBooking->guest_country_code = 'BR';
            $dbBooking->guest_language = 'pt';
            $dbBooking->check_in_date = $data['checkIn'];
            $dbBooking->check_out_date = $data['checkOut'];
            $dbBooking->adults = $data['adults'];
            $dbBooking->children = $data['children'];
            $dbBooking->babies = 0;
            $dbBooking->pets = 0;
            $dbBooking->total_amount = $data['totalPrice'];
            $dbBooking->currency = $data['currency'] ?? $property->currency ?? 'BRL';
            $dbBooking->payment_type = $data['paymentType'] ?? 'default';
            $dbBooking->room_type = $data['roomType'] ?? null;
            $dbBooking->remarks = $data['remarks'] ?? null;
            $dbBooking->api_response = $apiResponse;
            $dbBooking->api_payload = $payload;
            
            // Set sync status based on API response
            if ($apiSuccess) {
                $dbBooking->status = 'pending';
                $dbBooking->sync_status = 'synced';
                $dbBooking->synced_at = now();
            } else {
                $dbBooking->status = 'pending_sync';
                $dbBooking->sync_status = 'pending';
                $dbBooking->sync_error = $apiError;
            }
            
            $dbBooking->last_sync_attempt = now();
            $dbBooking->save();

            if ($apiSuccess) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Reserva criada com sucesso e sincronizada com NextPax',
                    'booking' => $apiResponse,
                    'localBookingId' => $dbBooking->id
                ]);
            } else {
                return response()->json([
                    'success' => true, 
                    'message' => 'Reserva salva localmente. Sincronização com NextPax falhou e será tentada posteriormente.',
                    'localBookingId' => $dbBooking->id,
                    'apiError' => $apiError,
                    'syncStatus' => 'pending'
                ]);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar reserva: ' . $e->getMessage()], 500);
        }
    }

    public function updateBooking(Request $request, $bookingId)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            $bookingData = $request->validate([
                'arrivalDate' => 'sometimes|date',
                'departureDate' => 'sometimes|date|after:arrivalDate',
                'guestName' => 'sometimes|string',
                'guestEmail' => 'sometimes|email',
                'guestPhone' => 'sometimes|string',
                'adults' => 'sometimes|integer|min:1',
                'children' => 'sometimes|integer|min:0',
                'amount' => 'sometimes|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            $booking = $this->bookingsService->updateBooking($bookingId, $propertyManagerCode, $bookingData);

            return response()->json(['success' => true, 'booking' => $booking]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar reserva: ' . $e->getMessage()], 500);
        }
    }

    public function cancelBooking($bookingId)
    {
        try {
            $user = Auth::user();
            $propertyId = $user->property_manager_code;

            if (!$propertyId) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            $payload = [
                'type' => 'owner',
                'reason' => 'Cancelled by property manager',
                'subReason' => '',
                'messageToGuest' => 'Your reservation has been cancelled by the host.'
            ];

            $result = $this->bookingsService->cancelBooking($bookingId, $payload);

            return response()->json(['success' => true, 'message' => 'Reserva cancelada com sucesso']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao cancelar reserva: ' . $e->getMessage()], 500);
        }
    }

    public function acceptBooking(Request $request)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            $data = $request->validate([
                'bookingId' => 'required|string',
                'channelId' => 'required|string'
            ]);

            $result = $this->bookingsService->changeBookingState(
                $propertyManagerCode,
                $data['channelId'],
                $data['bookingId'],
                'reservation'
            );

            return response()->json(['success' => true, 'message' => 'Reserva aceita com sucesso']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao aceitar reserva: ' . $e->getMessage()], 500);
        }
    }

    public function rejectBooking(Request $request)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            $data = $request->validate([
                'bookingId' => 'required|string',
                'channelId' => 'required|string',
                'reason' => 'required|string'
            ]);

            $result = $this->bookingsService->changeBookingState(
                $propertyManagerCode,
                $data['channelId'],
                $data['bookingId'],
                'cancelled',
                $data['reason']
            );

            return response()->json(['success' => true, 'message' => 'Reserva recusada com sucesso']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao recusar reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sync pending bookings with NextPax API
     */
    public function syncPendingBookings()
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            // Get pending bookings for this property manager
            $pendingBookings = Booking::where('property_manager_code', $propertyManagerCode)
                ->whereIn('sync_status', ['pending', 'failed'])
                ->limit(10)
                ->get();

            if ($pendingBookings->isEmpty()) {
                return response()->json(['success' => true, 'message' => 'Nenhuma reserva pendente para sincronizar']);
            }

            $successCount = 0;
            $failureCount = 0;

            foreach ($pendingBookings as $booking) {
                try {
                    // Update sync status to syncing
                    $booking->update([
                        'sync_status' => 'syncing',
                        'last_sync_attempt' => now(),
                    ]);

                    // Prepare payload
                    $payload = $this->buildSyncPayload($booking);
                    
                    // Attempt to create booking via API
                    $response = $this->bookingsService->createBooking($payload);
                    
                    if (isset($response['result']) && $response['result'] === 'success') {
                        $nextPaxId = $response['data']['bookingId'] ?? null;
                        
                        // Update booking with success
                        $booking->update([
                            'nextpax_booking_id' => $nextPaxId,
                            'sync_status' => 'synced',
                            'sync_error' => null,
                            'synced_at' => now(),
                            'api_response' => $response,
                        ]);
                        
                        $successCount++;
                    } else {
                        $error = 'API returned failure: ' . json_encode($response);
                        
                        // Update booking with failure
                        $booking->update([
                            'sync_status' => 'failed',
                            'sync_error' => $error,
                        ]);
                        
                        $failureCount++;
                    }
                    
                } catch (\Exception $e) {
                    $error = 'API exception: ' . $e->getMessage();
                    
                    // Update booking with error
                    $booking->update([
                        'sync_status' => 'failed',
                        'sync_error' => $error,
                    ]);
                    
                    $failureCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sincronização concluída: {$successCount} sucessos, {$failureCount} falhas",
                'successCount' => $successCount,
                'failureCount' => $failureCount,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao sincronizar reservas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Build API payload for sync from stored booking data
     */
    private function buildSyncPayload(Booking $booking)
    {
        return [
            'query' => 'propertyManagerBooking',
            'payload' => [
                'bookingNumber' => $booking->booking_number,
                'propertyManager' => $booking->property_manager_code,
                'channelPartnerReference' => $booking->channel_partner_reference,
                'channelId' => $booking->channel_id,
                'propertyId' => $booking->property_id,
                'supplierPropertyId' => $booking->supplier_property_id,
                'period' => [
                    'arrivalDate' => $booking->check_in_date->format('Y-m-d'),
                    'departureDate' => $booking->check_out_date->format('Y-m-d'),
                ],
                'occupancy' => [
                    'adults' => $booking->adults,
                    'children' => $booking->children,
                    'babies' => $booking->babies,
                    'pets' => $booking->pets,
                ],
                'stayPrice' => [
                    'amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                ],
                'mainBooker' => [
                    'surname' => $booking->guest_surname,
                    'letters' => substr($booking->guest_first_name, 0, 1),
                    'firstName' => $booking->guest_first_name,
                    'countryCode' => $booking->guest_country_code,
                    'language' => $booking->guest_language,
                    'zipCode' => '00000000',
                    'houseNumber' => '1',
                    'street' => 'Rua Exemplo',
                    'place' => 'São Paulo',
                    'phoneNumber' => $booking->guest_phone ?? '0000000000',
                    'email' => $booking->guest_email,
                    'dateOfBirth' => '1980-01-01',
                    'titleCode' => 'male',
                ],
                'payment' => [
                    'type' => $booking->payment_type,
                ],
                'remarks' => $booking->remarks,
            ],
        ];
    }

    public function bookingPropertyContext(string $propertyId)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;
            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui PM configurado'], 400);
            }

            // Get property from database - try multiple ways
            $property = null;
            
            // First try by local ID
            if (is_numeric($propertyId)) {
                $property = Property::where('id', $propertyId)->first();
            }
            
            // If not found, try by NextPax UUID
            if (!$property && strlen($propertyId) === 36 && strpos($propertyId, '-') !== false) {
                $property = Property::where('property_id', $propertyId)->first();
            }
            
            // If still not found, try by supplier_property_id
            if (!$property) {
                $property = Property::where('supplier_property_id', $propertyId)->first();
            }
            
            if (!$property) {
                return response()->json(['error' => 'Propriedade não encontrada'], 404);
            }
            
            $supplierPropertyId = $property->supplier_property_id;
            $baseCurrency = $property->currency ?? 'BRL';
            $basePrice = $property->base_price ?? null;

            // PSP info
            $psp = $this->bookingsService->getSupplierPaymentProvider($propertyManagerCode);
            $providerId = $psp['data']['providerId'] ?? null;

            return response()->json([
                'success' => true,
                'data' => [
                    'supplierPropertyId' => $supplierPropertyId,
                    'baseCurrency' => $baseCurrency,
                    'basePrice' => $basePrice,
                    'checkInFrom' => $property->check_in_from ?? null,
                    'checkInUntil' => $property->check_in_until ?? null,
                    'checkOutFrom' => $property->check_out_from ?? null,
                    'checkOutUntil' => $property->check_out_until ?? null,
                    'paymentProvider' => $providerId,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ===== PROPRIEDADES CRUD =====
    public function properties()
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return view('properties.index', ['error' => 'Configure seu Código do Gerenciador (NextPax) no perfil para listar propriedades.']);
            }

            $response = $this->nextPaxService->getProperties($propertyManagerCode);
            $properties = $response['data'] ?? $response ?? [];

            return view('properties.index', compact('properties'));

        } catch (\Exception $e) {
            // Em caso de erro, mostrar tela vazia com mensagem
            return view('properties.index', [
                'properties' => [],
                'error' => 'Não foi possível carregar as propriedades agora. Tente novamente mais tarde.'
            ]);
        }
    }

    public function showProperty($propertyId)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui código de gerenciador configurado'], 400);
            }

            // Buscar detalhes da propriedade diretamente na API
            $property = $this->nextPaxService->getProperty($propertyId);
            $subrooms = $this->nextPaxService->getPropertySubrooms($propertyId);
            $availability = $this->nextPaxService->getAvailability($propertyId);
            $rates = $this->nextPaxService->getRates($propertyId);

            return view('properties.show', compact('property', 'subrooms', 'availability', 'rates'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao carregar propriedade: ' . $e->getMessage()], 500);
        }
    }

    public function createProperty(Request $request)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Configure seu Código do Gerenciador (NextPax) no perfil antes de criar propriedades.'], 400);
            }

            $data = $request->all();

            // Montagem de payload conforme NextPax
            $payload = [
                'supplierPropertyId' => $data['supplierPropertyId'] ?? ('su-' . substr(md5(uniqid('', true)), 0, 12)),
                'propertyManager' => $propertyManagerCode,
                'general' => [
                    'minOccupancy' => (int) ($data['general']['minOccupancy'] ?? 1),
                    'address' => [
                        'apt' => $data['general']['address']['apt'] ?? '',
                        'city' => $data['general']['address']['city'] ?? '',
                        'countryCode' => $data['general']['address']['countryCode'] ?? 'BR',
                        'street' => $data['general']['address']['street'] ?? '',
                        'postalCode' => preg_replace('/\s+/', '', (string) ($data['general']['address']['postalCode'] ?? '')),
                        'state' => $data['general']['address']['state'] ?? 'BR_SP',
                        'region' => $data['general']['address']['region'] ?? 'BR_SP_1',
                    ],
                    'checkInOutTimes' => [
                        'checkInFrom' => $data['general']['checkInOutTimes']['checkInFrom'] ?? '14:00',
                        'checkInUntil' => $data['general']['checkInOutTimes']['checkInUntil'] ?? '22:00',
                        'checkOutFrom' => $data['general']['checkInOutTimes']['checkOutFrom'] ?? '08:00',
                        'checkOutUntil' => $data['general']['checkInOutTimes']['checkOutUntil'] ?? '11:00',
                    ],
                    'classification' => $data['general']['classification'] ?? 'single-unit',
                    'baseCurrency' => $data['general']['baseCurrency'] ?? 'BRL',
                    'typeCode' => $data['general']['typeCode'] ?? 'APP',
                    
                    'name' => $data['general']['name'] ?? '',
                    'maxAdults' => (int) ($data['general']['maxAdults'] ?? 2),
                    'maxOccupancy' => (int) ($data['general']['maxOccupancy'] ?? 2),
                ],
            ];

            if (isset($data['general']['geoLocation']['latitude'], $data['general']['geoLocation']['longitude'])) {
                $payload['general']['geoLocation'] = [
                    'latitude' => (float) $data['general']['geoLocation']['latitude'],
                    'longitude' => (float) $data['general']['geoLocation']['longitude'],
                ];
            }

            if (($payload['general']['classification'] ?? 'single-unit') === 'unit-type') {
                $payload['parentId'] = $data['parentId'] ?? $data['parent_id'] ?? null;
                if (empty($payload['parentId'])) {
                    return response()->json(['error' => 'parentId é obrigatório quando classification = unit-type'], 422);
                }
                // numberOfUnits só é permitido em unit-type
                if (isset($data['general']['numberOfUnits'])) {
                    $payload['general']['numberOfUnits'] = (int) $data['general']['numberOfUnits'];
                } elseif (isset($data['number_of_units'])) {
                    $payload['general']['numberOfUnits'] = (int) $data['number_of_units'];
                }
            }

            $property = $this->nextPaxService->createProperty($payload);

            // Abrir disponibilidade padrão (ativar propriedade)
            try {
                $fromDate = date('Y-m-d');
                $untilDate = date('Y-m-d', strtotime('+365 days'));
                $quantity = 1;
                if (($payload['general']['classification'] ?? 'single-unit') === 'unit-type' && isset($payload['general']['numberOfUnits'])) {
                    $quantity = max(1, (int) $payload['general']['numberOfUnits']);
                }

                $availabilityPayload = [
                    'data' => [[
                        'fromDate' => $fromDate,
                        'untilDate' => $untilDate,
                        'quantity' => $quantity,
                        'restrictions' => [
                            'arrivalsAllowed' => true,
                            'departuresAllowed' => true
                        ]
                    ]]
                ];

                $this->nextPaxService->updateAvailability($property['propertyId'] ?? $property['data']['propertyId'] ?? '', $availabilityPayload);
            } catch (\Throwable $e) {
                // Não bloquear criação por falha de disponibilidade; logar se necessário
            }

            return response()->json(['success' => true, 'property' => $property]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar propriedade: ' . $e->getMessage()], 500);
        }
    }

    public function updateProperty(Request $request, $propertyId)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Configure seu Código do Gerenciador (NextPax) no perfil.'], 400);
            }

            $data = $request->all();
            $payload = [];

            // Verificar propriedade pertence ao PM do usuário
            $existing = $this->nextPaxService->getProperty($propertyId);
            if (($existing['propertyManager'] ?? null) !== $propertyManagerCode) {
                return response()->json(['error' => 'Acesso negado: propriedade não pertence ao seu tenant'], 403);
            }

            if (isset($data['general'])) {
                $payload['general'] = [];
                if (isset($data['general']['minOccupancy'])) $payload['general']['minOccupancy'] = (int) $data['general']['minOccupancy'];
                if (isset($data['general']['address'])) {
                    $addr = $data['general']['address'];
                    $payload['general']['address'] = [
                        'apt' => $addr['apt'] ?? '',
                        'city' => $addr['city'] ?? '',
                        'countryCode' => $addr['countryCode'] ?? 'BR',
                        'street' => $addr['street'] ?? '',
                        'postalCode' => isset($addr['postalCode']) ? preg_replace('/\s+/', '', (string) $addr['postalCode']) : '',
                        'state' => $addr['state'] ?? 'BR_SP',
                        'region' => $addr['region'] ?? 'BR_SP_1',
                    ];
                }
                if (isset($data['general']['checkInOutTimes'])) $payload['general']['checkInOutTimes'] = $data['general']['checkInOutTimes'];
                if (isset($data['general']['classification'])) $payload['general']['classification'] = $data['general']['classification'];
                if (isset($data['general']['baseCurrency'])) $payload['general']['baseCurrency'] = $data['general']['baseCurrency'];
                if (isset($data['general']['typeCode'])) $payload['general']['typeCode'] = $data['general']['typeCode'];
                if (isset($data['general']['numberOfUnits'])) $payload['general']['numberOfUnits'] = (int) $data['general']['numberOfUnits'];
                if (isset($data['general']['geoLocation'])) $payload['general']['geoLocation'] = $data['general']['geoLocation'];
                if (isset($data['general']['name'])) $payload['general']['name'] = $data['general']['name'];
                if (isset($data['general']['maxAdults'])) $payload['general']['maxAdults'] = (int) $data['general']['maxAdults'];
                if (isset($data['general']['maxOccupancy'])) $payload['general']['maxOccupancy'] = (int) $data['general']['maxOccupancy'];
            }

            if (empty($payload)) {
                return response()->json(['error' => 'Nenhuma alteração enviada'], 400);
            }

            $property = $this->nextPaxService->updateProperty($propertyId, $payload);

            return response()->json(['success' => true, 'property' => $property]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar propriedade: ' . $e->getMessage()], 500);
        }
    }

    public function deleteProperty($propertyId)
    {
        try {
            $user = Auth::user();
            $userPropertyId = $user->property_manager_code;

            if ($userPropertyId !== $propertyId) {
                return response()->json(['error' => 'Acesso negado'], 403);
            }

            $result = $this->nextPaxService->deleteProperty($propertyId);

            // Remover property_manager_code do usuário
            $user->update(['property_manager_code' => null]);

            return response()->json(['success' => true, 'message' => 'Propriedade deletada com sucesso']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao deletar propriedade: ' . $e->getMessage()], 500);
        }
    }

    // ===== QUARTOS CRUD =====
    public function subrooms($propertyId)
    {
        try {
            $user = Auth::user();
            $userPropertyId = $user->property_manager_code;

            if ($userPropertyId !== $propertyId) {
                return response()->json(['error' => 'Acesso negado'], 403);
            }

            $subrooms = $this->nextPaxService->getPropertySubrooms($propertyId);
            return view('properties.subrooms', compact('subrooms', 'propertyId'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao carregar quartos: ' . $e->getMessage()], 500);
        }
    }

    public function createSubroom(Request $request, $propertyId)
    {
        try {
            $user = Auth::user();
            $userPropertyId = $user->property_manager_code;

            if ($userPropertyId !== $propertyId) {
                return response()->json(['error' => 'Acesso negado'], 403);
            }

            $subroomData = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string',
                'description' => 'nullable|string',
                'capacity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0'
            ]);

            $subroom = $this->nextPaxService->updatePropertySubrooms($propertyId, $subroomData);

            return response()->json(['success' => true, 'subroom' => $subroom]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar quarto: ' . $e->getMessage()], 500);
        }
    }

    // ===== MENSAGENS CRUD =====
    public function messages()
    {
        try {
            $user = Auth::user();
            $propertyId = $user->property_manager_code;

            if (!$propertyId) {
                return view('messages.index', ['error' => 'Usuário não possui propriedade associada']);
            }

            $threads = $this->messagingService->getThreads($propertyId);
            return view('messages.index', compact('threads'));

        } catch (\Exception $e) {
            return view('messages.index', ['error' => 'Erro ao carregar mensagens: ' . $e->getMessage()]);
        }
    }

    public function showThread($threadId)
    {
        try {
            $user = Auth::user();
            $propertyId = $user->property_manager_code;

            if (!$propertyId) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            $thread = $this->messagingService->getThread($threadId);
            $messages = $this->messagingService->getThreadMessages($threadId);

            return view('messages.thread', compact('thread', 'messages'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao carregar conversa: ' . $e->getMessage()], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $user = Auth::user();
            $propertyId = $user->property_manager_code;

            if (!$propertyId) {
                return response()->json(['error' => 'Usuário não possui propriedade associada'], 400);
            }

            $messageData = $request->validate([
                'threadId' => 'required|string',
                'body' => 'required|string',
                'type' => 'sometimes|string|in:text,image,file'
            ]);

            $message = $this->messagingService->sendMessage($messageData['threadId'], $messageData);

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao enviar mensagem: ' . $e->getMessage()], 500);
        }
    }

    // ===== CALENDÁRIO =====
    public function calendar()
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return view('calendar.index', ['error' => 'Usuário não possui propriedade associada']);
            }

            // Buscar propriedades do usuário
            $properties = Property::where('channel_type', 'nextpax')
                ->where('property_manager_code', $propertyManagerCode)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            // Buscar reservas reais do banco de dados
            $bookings = Booking::where('property_manager_code', $propertyManagerCode)
                ->with('property')
                ->orderBy('check_in_date')
                ->get()
                ->map(function($booking) {
                    return [
                        'id' => $booking->id,
                        'title' => $booking->guest_first_name . ' ' . $booking->guest_surname,
                        'start' => $booking->check_in_date->format('Y-m-d'),
                        'end' => $booking->check_out_date->format('Y-m-d'),
                        'property_id' => $booking->property_id,
                        'property_name' => $booking->property->name ?? 'Propriedade',
                        'guest_email' => $booking->guest_email,
                        'guest_phone' => $booking->guest_phone ?? 'N/A',
                        'adults' => $booking->adults,
                        'children' => $booking->children,
                        'total_amount' => $booking->total_amount,
                        'currency' => $booking->currency,
                        'status' => $booking->status,
                        'sync_status' => $booking->sync_status,
                        'remarks' => $booking->remarks,
                        'backgroundColor' => $this->getBookingColor($booking->status, $booking->sync_status),
                        'borderColor' => $this->getBookingBorderColor($booking->status, $booking->sync_status),
                        'extendedProps' => [
                            'booking_number' => $booking->booking_number,
                            'nextpax_booking_id' => $booking->nextpax_booking_id,
                            'room_type' => $booking->room_type,
                            'payment_type' => $booking->payment_type,
                            'nights' => $booking->check_in_date->diffInDays($booking->check_out_date),
                            'total_occupancy' => $booking->adults + $booking->children + $booking->babies
                        ]
                    ];
                });

            // Buscar disponibilidade das propriedades
            $availability = $this->getPropertyAvailability($propertyManagerCode);

            return view('calendar.index', compact('properties', 'bookings', 'availability'));

        } catch (\Exception $e) {
            return view('calendar.index', ['error' => 'Erro ao carregar calendário: ' . $e->getMessage()]);
        }
    }

    private function getPropertyAvailability($propertyManagerCode)
    {
        try {
            // Buscar propriedades e suas configurações de disponibilidade
            $properties = Property::where('channel_type', 'nextpax')
                ->where('is_active', true)
                ->get()
                ->map(function($property) {
                    return [
                        'id' => $property->id,
                        'property_id' => $property->property_id,
                        'name' => $property->name,
                        'max_occupancy' => $property->max_occupancy ?? 4,
                        'max_adults' => $property->max_adults ?? 2,
                        'max_children' => $property->max_children ?? 2,
                        'bedrooms' => $property->bedrooms ?? 1,
                        'bathrooms' => $property->bathrooms ?? 1,
                        'base_price' => $property->base_price ?? 0,
                        'currency' => $property->currency ?? 'BRL',
                        'check_in_from' => $property->check_in_from ?? '14:00',
                        'check_in_until' => $property->check_in_until ?? '22:00',
                        'check_out_from' => $property->check_out_from ?? '08:00',
                        'check_out_until' => $property->check_out_until ?? '11:00'
                    ];
                });

            return $properties;
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar disponibilidade: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getBookingColor($status, $syncStatus)
    {
        // Cores baseadas no status da reserva
        $statusColors = [
            'pending' => '#ffc107',      // Amarelo
            'confirmed' => '#28a745',    // Verde
            'cancelled' => '#dc3545',    // Vermelho
            'failed' => '#6c757d',       // Cinza
            'request' => '#17a2b8',      // Azul
            'pending_sync' => '#fd7e14'  // Laranja
        ];

        return $statusColors[$status] ?? '#6c757d';
    }

    private function getBookingBorderColor($status, $syncStatus)
    {
        // Bordas baseadas no status de sincronização
        if ($syncStatus === 'pending') {
            return '#dc3545'; // Vermelho para pendente
        } elseif ($syncStatus === 'failed') {
            return '#fd7e14'; // Laranja para falha
        } else {
            return '#28a745'; // Verde para sincronizado
        }
    }

    // ===== RELATÓRIOS =====
    public function reports()
    {
        try {
            $user = Auth::user();
            $propertyId = $user->property_manager_code;

            if (!$propertyId) {
                return view('reports.index', ['error' => 'Usuário não possui propriedade associada']);
            }

            // Buscar dados reais da base de dados para relatórios
            $financialReport = $this->getDetailedFinancialReport($propertyId);
            $topProperties = $this->getTopProperties($propertyId);
            $bookingsByStatus = $this->getBookingsByStatus($propertyId);
            $monthlyRevenue = $this->getMonthlyRevenue($propertyId);
            $monthlyOccupancy = $this->getMonthlyOccupancy($propertyId);

            return view('reports.index', compact(
                'financialReport', 
                'topProperties', 
                'bookingsByStatus',
                'monthlyRevenue',
                'monthlyOccupancy'
            ));

        } catch (\Exception $e) {
            return view('reports.index', ['error' => 'Erro ao carregar relatórios: ' . $e->getMessage()]);
        }
    }

    // ===== MÉTODOS PRIVADOS AUXILIARES =====
    
    // Novos métodos para buscar dados da base de dados local
    private function getRecentBookingsFromDatabase($propertyManagerCode)
    {
        try {
            // Buscar reservas recentes da base de dados local
            $recentBookings = Booking::where('property_manager_code', $propertyManagerCode)
                ->with('property') // Carregar dados da propriedade
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return $recentBookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number ?? 'N/A',
                    'guest_name' => $this->formatGuestName($booking->guest_first_name, $booking->guest_surname),
                    'check_in' => $booking->check_in_date ? date('d/m/Y', strtotime($booking->check_in_date)) : 'N/A',
                    'check_out' => $booking->check_out_date ? date('d/m/Y', strtotime($booking->check_out_date)) : 'N/A',
                    'status' => $this->mapBookingStatus($booking->status),
                    'amount' => $booking->total_amount ?? 0,
                    'last_modified' => $booking->updated_at,
                    'remarks' => $booking->remarks,
                    'property_name' => $booking->property ? $booking->property->name : 'N/A',
                    'sync_status' => $booking->sync_status ?? 'pending',
                    'currency' => $booking->currency ?? 'BRL'
                ];
            })->toArray();

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar reservas da base de dados: ' . $e->getMessage());
            return [];
        }
    }

    private function getRecentMessagesFromDatabase($propertyManagerCode)
    {
        try {
            // Por enquanto, retornar array vazio já que não temos sistema de mensagens local
            // TODO: Implementar quando tivermos tabela de mensagens
            return [];
            
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar mensagens da base de dados: ' . $e->getMessage());
            return [];
        }
    }

    private function getFinancialReportFromDatabase($propertyManagerCode)
    {
        try {
            // Buscar todas as reservas da base de dados local
            $bookings = Booking::where('property_manager_code', $propertyManagerCode)->get();
            
            if ($bookings->isEmpty()) {
                return $this->getDefaultFinancialReport();
            }

            // Calcular métricas financeiras reais
            $totalBookings = $bookings->count();
            $totalRevenue = $bookings->sum('total_amount');
            $pendingBookings = $bookings->where('status', 'pending')->count();
            $cancelledBookings = $bookings->where('status', 'cancelled')->count();
            $confirmedBookings = $bookings->whereIn('status', ['confirmed', 'reservation'])->count();
            $pendingSyncBookings = $bookings->where('sync_status', 'pending')->count();
            $failedSyncBookings = $bookings->where('sync_status', 'failed')->count();

            // Calcular taxa de ocupação (baseado em reservas confirmadas)
            $occupancyRate = $totalBookings > 0 ? min(95, ($confirmedBookings / $totalBookings) * 100) : 0;
            
            // Calcular taxa média diária
            $averageDailyRate = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

            // Calcular receita por status de sincronização
            $syncedRevenue = $bookings->where('sync_status', 'synced')->sum('total_amount');
            $pendingSyncRevenue = $bookings->where('sync_status', 'pending')->sum('total_amount');

            return [
                'total_bookings' => $totalBookings,
                'total_revenue' => round($totalRevenue, 2),
                'pending_bookings' => $pendingBookings,
                'cancelled_bookings' => $cancelledBookings,
                'confirmed_bookings' => $confirmedBookings,
                'occupancy_rate' => round($occupancyRate, 1),
                'average_daily_rate' => round($averageDailyRate, 2),
                'pending_sync_bookings' => $pendingSyncBookings,
                'failed_sync_bookings' => $failedSyncBookings,
                'synced_revenue' => round($syncedRevenue, 2),
                'pending_sync_revenue' => round($pendingSyncRevenue, 2)
            ];

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar relatório financeiro da base de dados: ' . $e->getMessage());
            return $this->getDefaultFinancialReport();
        }
    }

    // Métodos auxiliares para formatação
    private function formatGuestName($firstName, $lastName)
    {
        if ($firstName && $lastName) {
            return trim($firstName . ' ' . $lastName);
        } elseif ($firstName) {
            return $firstName;
        } elseif ($lastName) {
            return $lastName;
        }
        return 'Hóspede';
    }

    private function mapBookingStatus($status)
    {
        $statusMap = [
            'confirmed' => 'reservation',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'pending_sync' => 'pending',
            'failed' => 'failed',
            'synced' => 'reservation'
        ];

        return $statusMap[$status] ?? $status;
    }

    // ===== MÉTODOS PARA RELATÓRIOS DETALHADOS =====
    
    private function getDetailedFinancialReport($propertyManagerCode)
    {
        try {
            $bookings = Booking::where('property_manager_code', $propertyManagerCode)->get();
            
            if ($bookings->isEmpty()) {
                return $this->getDefaultDetailedFinancialReport();
            }

            // Calcular métricas por período
            $now = now();
            $weekStart = $now->copy()->startOfWeek();
            $monthStart = $now->copy()->startOfMonth();
            $yearStart = $now->copy()->startOfYear();

            $weeklyRevenue = $bookings->where('created_at', '>=', $weekStart)->sum('total_amount');
            $monthlyRevenue = $bookings->where('created_at', '>=', $monthStart)->sum('total_amount');
            $yearlyRevenue = $bookings->where('created_at', '>=', $yearStart)->sum('total_amount');

            return [
                'weekly' => round($weeklyRevenue, 2),
                'monthly' => round($monthlyRevenue, 2),
                'yearly' => round($yearlyRevenue, 2),
                'total_bookings' => $bookings->count(),
                'total_revenue' => round($bookings->sum('total_amount'), 2),
                'average_booking_value' => round($bookings->avg('total_amount'), 2),
                'pending_sync_revenue' => round($bookings->where('sync_status', 'pending')->sum('total_amount'), 2),
                'synced_revenue' => round($bookings->where('sync_status', 'synced')->sum('total_amount'), 2)
            ];

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar relatório financeiro detalhado: ' . $e->getMessage());
            return $this->getDefaultDetailedFinancialReport();
        }
    }

    private function getTopProperties($propertyManagerCode)
    {
        try {
            $topProperties = Booking::where('property_manager_code', $propertyManagerCode)
                ->with('property')
                ->get()
                ->groupBy('property_id')
                ->map(function($propertyBookings) {
                    $property = $propertyBookings->first()->property;
                    return [
                        'name' => $property ? $property->name : 'Propriedade Desconhecida',
                        'bookings' => $propertyBookings->count(),
                        'revenue' => round($propertyBookings->sum('total_amount'), 2),
                        'average_value' => round($propertyBookings->avg('total_amount'), 2),
                        'sync_status' => [
                            'synced' => $propertyBookings->where('sync_status', 'synced')->count(),
                            'pending' => $propertyBookings->where('sync_status', 'pending')->count(),
                            'failed' => $propertyBookings->where('sync_status', 'failed')->count()
                        ]
                    ];
                })
                ->sortByDesc('revenue')
                ->take(5)
                ->values()
                ->toArray();

            return $topProperties;

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar top propriedades: ' . $e->getMessage());
            return [];
        }
    }

    private function getBookingsByStatus($propertyManagerCode)
    {
        try {
            $bookings = Booking::where('property_manager_code', $propertyManagerCode)->get();
            
            $statusCounts = [
                'confirmed' => $bookings->whereIn('status', ['confirmed', 'reservation'])->count(),
                'pending' => $bookings->whereIn('status', ['pending', 'request', 'request-accepted'])->count(),
                'cancelled' => $bookings->where('status', 'cancelled')->count(),
                'failed' => $bookings->where('status', 'failed')->count()
            ];

            $syncStatusCounts = [
                'synced' => $bookings->where('sync_status', 'synced')->count(),
                'pending' => $bookings->where('sync_status', 'pending')->count(),
                'failed' => $bookings->where('sync_status', 'failed')->count(),
                'syncing' => $bookings->where('sync_status', 'syncing')->count()
            ];

            return [
                'by_status' => $statusCounts,
                'by_sync_status' => $syncStatusCounts,
                'total' => $bookings->count()
            ];

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar reservas por status: ' . $e->getMessage());
            return [
                'by_status' => ['confirmed' => 0, 'pending' => 0, 'cancelled' => 0, 'failed' => 0],
                'by_sync_status' => ['synced' => 0, 'pending' => 0, 'failed' => 0, 'syncing' => 0],
                'total' => 0
            ];
        }
    }

    private function getMonthlyRevenue($propertyManagerCode)
    {
        try {
            $bookings = Booking::where('property_manager_code', $propertyManagerCode)
                ->where('created_at', '>=', now()->subYear())
                ->get();

            $monthlyData = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthKey = $month->format('M');
                $monthStart = $month->copy()->startOfMonth();
                $monthEnd = $month->copy()->endOfMonth();

                $monthlyData[$monthKey] = round(
                    $bookings->whereBetween('created_at', [$monthStart, $monthEnd])->sum('total_amount'), 
                    2
                );
            }

            return $monthlyData;

        } catch (\Exception $e) {
            \Log::error('Erro ao calcular receita mensal: ' . $e->getMessage());
            return array_fill_keys(['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], 0);
        }
    }

    private function getMonthlyOccupancy($propertyManagerCode)
    {
        try {
            $bookings = Booking::where('property_manager_code', $propertyManagerCode)
                ->where('created_at', '>=', now()->subYear())
                ->get();

            $monthlyData = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthKey = $month->format('M');
                $monthStart = $month->copy()->startOfMonth();
                $monthEnd = $month->copy()->endOfMonth();

                $monthBookings = $bookings->whereBetween('created_at', [$monthStart, $monthEnd]);
                $confirmedBookings = $monthBookings->whereIn('status', ['confirmed', 'reservation'])->count();
                $totalBookings = $monthBookings->count();

                $monthlyData[$monthKey] = $totalBookings > 0 ? 
                    min(95, round(($confirmedBookings / $totalBookings) * 100, 1)) : 0;
            }

            return $monthlyData;

        } catch (\Exception $e) {
            \Log::error('Erro ao calcular ocupação mensal: ' . $e->getMessage());
            return array_fill_keys(['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], 0);
        }
    }

    private function getDefaultDetailedFinancialReport()
    {
        return [
            'weekly' => 0,
            'monthly' => 0,
            'yearly' => 0,
            'total_bookings' => 0,
            'total_revenue' => 0,
            'average_booking_value' => 0,
            'pending_sync_revenue' => 0,
            'synced_revenue' => 0
        ];
    }

    // Métodos auxiliares para extrair dados das reservas
    private function extractGuestName($booking)
    {
        // Tentar extrair nome do hóspede de diferentes campos
        if (isset($booking['guestName'])) {
            return $booking['guestName'];
        }
        
        if (isset($booking['remarks']) && preg_match('/nome[:\s]+([^\n\r,]+)/i', $booking['remarks'], $matches)) {
            return trim($matches[1]);
        }
        
        return 'Hóspede';
    }

    private function extractCheckInDate($booking)
    {
        // Extrair data de check-in dos dados da reserva
        if (isset($booking['arrivalDate'])) {
            return date('Y-m-d', strtotime($booking['arrivalDate']));
        }
        
        return 'N/A';
    }

    private function extractCheckOutDate($booking)
    {
        // Extrair data de check-out dos dados da reserva
        if (isset($booking['departureDate'])) {
            return date('Y-m-d', strtotime($booking['departureDate']));
        }
        
        return 'N/A';
    }

    private function extractAmount($booking)
    {
        // Extrair valor da reserva
        if (isset($booking['totalAmount'])) {
            return floatval($booking['totalAmount']);
        }
        
        if (isset($booking['amount'])) {
            return floatval($booking['amount']);
        }
        
        return 0;
    }

    private function extractLastMessage($thread)
    {
        // Extrair última mensagem do thread
        if (isset($thread['lastMessage'])) {
            return $thread['lastMessage'];
        }
        
        if (isset($thread['messages']) && is_array($thread['messages']) && !empty($thread['messages'])) {
            $lastMessage = end($thread['messages']);
            return $lastMessage['content'] ?? 'Mensagem';
        }
        
        return 'Nenhuma mensagem';
    }

    /**
     * Check if a string is a NextPax UUID format
     */
    private function isNextPaxUuid(string $id): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id);
    }

    private function getDefaultFinancialReport()
    {
        return [
            'total_bookings' => 0,
            'total_revenue' => 0,
            'pending_bookings' => 0,
            'cancelled_bookings' => 0,
            'confirmed_bookings' => 0,
            'occupancy_rate' => 0,
            'average_daily_rate' => 0,
            'pending_sync_bookings' => 0,
            'failed_sync_bookings' => 0,
            'synced_revenue' => 0,
            'pending_sync_revenue' => 0
        ];
    }
}
