<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\NextPaxService;
use Intervention\Image\Facades\Image;

class PropertyController extends Controller
{
    private $nextPaxService;

    public function __construct(NextPaxService $nextPaxService)
    {
        $this->nextPaxService = $nextPaxService;
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return view('properties.index', ['error' => 'Configure seu Código do Gerenciador (NextPax) no perfil para listar propriedades.', 'localProperties' => collect(), 'apiProperties' => []]);
            }

            // Carregar propriedades locais (que já foram criadas via API)
            $localProperties = Property::where('channel_type', 'nextpax')
                ->whereNotNull('channel_property_id')
                ->orderBy('created_at', 'desc')
                ->get();

            // Buscar propriedades da API NextPax para sincronização
            $response = $this->nextPaxService->getProperties($propertyManagerCode);
            $apiProperties = $response['data'] ?? $response ?? [];

            // Mapear propriedades locais com dados da API
            $mappedProperties = [];
            foreach ($localProperties as $localProperty) {
                // Buscar dados completos da API para cada propriedade local
                try {
                    $apiProperty = $this->nextPaxService->getProperty($localProperty->channel_property_id);
                    if (is_array($apiProperty)) {
                        $mappedProperties[] = [
                            'local' => $localProperty,
                            'api' => $apiProperty,
                            'is_synced' => true,
                            'status' => $localProperty->status ?? 'active'
                        ];
                    }
                } catch (\Exception $e) {
                    // Se não conseguir buscar da API, usar dados locais
                    $mappedProperties[] = [
                        'local' => $localProperty,
                        'api' => null,
                        'is_synced' => false,
                        'status' => $localProperty->status ?? 'active'
                    ];
                }
            }

            return view('properties.index', compact('mappedProperties'));

        } catch (\Exception $e) {
            return view('properties.index', [
                'mappedProperties' => [],
                'error' => 'Não foi possível carregar as propriedades agora. Tente novamente mais tarde.'
            ]);
        }
    }

    public function show($propertyId)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Usuário não possui código de gerenciador configurado'], 400);
            }

            // Try to get from local database first by multiple fields
            $property = Property::where('id', $propertyId)
                ->orWhere('channel_property_id', $propertyId)
                ->first();

            // If not found by ID or channel_property_id, try by property_id (but only if it's a valid UUID)
            if (!$property && $this->isValidUuid($propertyId)) {
                $property = Property::where('property_id', $propertyId)->first();
            }

            $isApiProperty = false;
            $nextPaxPropertyId = $propertyId;

            if ($property && ($property->channel_property_id || $this->isValidUuid($property->property_id))) {
                $nextPaxPropertyId = $property->channel_property_id ?: $property->property_id;
            }

            // Always refresh from API to reflect latest sent data
            $apiProperty = $this->nextPaxService->getProperty($nextPaxPropertyId);
            if (!$apiProperty) {
                return response()->json(['error' => 'Propriedade não encontrada'], 404);
            }
            if (!isset($apiProperty['propertyId'])) { $apiProperty['propertyId'] = $nextPaxPropertyId; }
            $property = $this->mapApiPropertyToModel($apiProperty);
            $isApiProperty = true;

            // Additional datasets
            try {
                $subrooms = $this->nextPaxService->getPropertySubrooms($nextPaxPropertyId);
            } catch (\Exception $e) {
                $subrooms = [];
            }
            try {
                $availability = $this->nextPaxService->getAvailability($nextPaxPropertyId);
            } catch (\Exception $e) {
                $availability = [];
            }
            try {
                $rates = $this->nextPaxService->getRates($nextPaxPropertyId);
            } catch (\Exception $e) {
                $rates = [];
            }
            // Images
            try {
                $imagesResponse = $this->nextPaxService->getPropertyImages($nextPaxPropertyId);
                $images = ($imagesResponse['data']['images'] ?? $imagesResponse['images'] ?? []) ?: [];
            } catch (\Exception $e) {
                $images = [];
            }

            $amenities = $apiProperty['amenities'] ?? [];
            $descriptions = $apiProperty['descriptions'] ?? [];

            $meta = [
                'supplierPropertyId' => $apiProperty['supplierPropertyId'] ?? null,
                'propertyManager' => $apiProperty['propertyManager'] ?? null,
                'pricingModel' => $apiProperty['pricingModel'] ?? null,
            ];
            $general = $apiProperty['general'] ?? [];

            return view('properties.show', compact('property', 'subrooms', 'availability', 'rates', 'images', 'amenities', 'descriptions', 'general', 'meta', 'isApiProperty'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao carregar propriedade: ' . $e->getMessage()], 500);
        }
    }

    public function create()
    {
        return view('properties.create');
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $propertyManagerCode = $user->property_manager_code;

            if (!$propertyManagerCode) {
                return response()->json(['error' => 'Configure seu Código do Gerenciador (NextPax) no perfil antes de criar propriedades.'], 400);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'property_type' => 'required|string|in:apartment,house,hotel,hostel,resort,villa,cabin,loft',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'max_occupancy' => 'required|integer|min:1|max:20',
                'max_adults' => 'required|integer|min:1|max:20',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();

            // Definir horários padrão
            $data['check_in_from'] = '14:00';
            $data['check_in_until'] = '22:00';
            $data['check_out_from'] = '08:00';
            $data['check_out_until'] = '11:00';

            // Criar payload para NextPax
            $apiPayload = $this->buildNextPaxPayload(new Property($data), $data);

            // Criar propriedade na NextPax
            $response = $this->nextPaxService->createProperty($apiPayload);
            
            if (!isset($response['data']['propertyId'])) {
                throw new \Exception('Resposta inválida da API ao criar o Property Manager');
            }

            $nextPaxPropertyId = $response['data']['propertyId'];

            // Salvar localmente apenas para referência (sem duplicação)
            $property = Property::create([
                'name' => $data['name'],
                'property_type' => $data['property_type'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'postal_code' => $data['postal_code'],
                'country' => 'BR',
                'max_occupancy' => $data['max_occupancy'],
                'max_adults' => $data['max_adults'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'channel_type' => 'nextpax',
                'channel_property_id' => $nextPaxPropertyId,
                'is_active' => false, // Inicialmente inativo
                'status' => 'draft', // Status inicial: rascunho
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Propriedade criada com sucesso na NextPax! ID: ' . $nextPaxPropertyId,
                'property' => $property,
                'nextPaxId' => $nextPaxPropertyId
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao criar propriedade:', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Erro ao criar propriedade: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($propertyId)
    {
        $property = Property::findOrFail($propertyId);
        return view('properties.edit', compact('property'));
    }

    public function update(Request $request, $propertyId)
    {
        try {
            $property = Property::findOrFail($propertyId);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'property_type' => 'required|string|in:apartment,house,hotel,hostel,resort,villa,cabin,loft',
                'description' => 'nullable|string|max:1000',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'country' => 'required|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'max_occupancy' => 'required|integer|min:1|max:20',
                'max_adults' => 'required|integer|min:1|max:20',
                'max_children' => 'nullable|integer|min:0|max:20',
                'bedrooms' => 'required|integer|min:1|max:20',
                'bathrooms' => 'required|integer|min:1|max:20',
                'base_price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'check_in_from' => 'required|date_format:H:i',
                'check_in_until' => 'required|date_format:H:i',
                'check_out_from' => 'required|date_format:H:i',
                'check_out_until' => 'required|date_format:H:i',
                'amenities' => 'nullable|array',
                'amenities.*' => 'string|max:100',
                'house_rules' => 'nullable|array',
                'house_rules.*' => 'string|max:200',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email|max:255',
                'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            
            // Update property
            $property->update([
                'name' => $data['name'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'country' => $data['country'],
                'postal_code' => $data['postal_code'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'description' => $data['description'],
                'property_type' => $data['property_type'],
                'max_occupancy' => $data['max_occupancy'],
                'max_adults' => $data['max_adults'],
                'max_children' => $data['max_children'] ?? 0,
                'bedrooms' => $data['bedrooms'],
                'bathrooms' => $data['bathrooms'],
                'base_price' => $data['base_price'],
                'currency' => $data['currency'] ?? 'BRL',
                'amenities' => $data['amenities'] ?? [],
                'house_rules' => $data['house_rules'] ?? [],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'],
                'check_in_from' => $data['check_in_from'],
                'check_in_until' => $data['check_in_until'],
                'check_out_from' => $data['check_out_from'],
                'check_out_until' => $data['check_out_until'],
            ]);

            // Handle main image upload
            if ($request->hasFile('main_image')) {
                // Delete old main image
                $property->images()->where('type', 'main')->delete();
                $this->uploadPropertyImage($property, $request->file('main_image'), 'main');
            }

            // Handle gallery images upload
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $image) {
                    $this->uploadPropertyImage($property, $image, 'gallery');
                }
            }

            // Update in NextPax API if channel_property_id exists
            if ($property->channel_property_id) {
                $apiPayload = $this->buildNextPaxPayload($property, $data);
                $this->nextPaxService->updateProperty($property->channel_property_id, $apiPayload);
            }

            return response()->json([
                'success' => true,
                'message' => 'Propriedade atualizada com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar propriedade: ' . $e->getMessage()], 500);
        }
    }

    public function updateGeneral(Request $request, string $propertyId)
    {
        try {
            $built = $this->buildNextPaxPayload(new Property(), $request->all());
            $payload = [
                'propertyManager' => Auth::user()->property_manager_code,
                'general' => $built['general']
            ];
            $resp = $this->nextPaxService->updateProperty($propertyId, $payload);
            return response()->json(['success' => true, 'data' => $resp]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateDescriptions(Request $request, string $propertyId)
    {
        try {
            $descs = $request->get('descriptions', []);
            $payload = [ 'descriptions' => [] ];
            foreach ($descs as $lang => $desc) {
                if (!empty($desc['text'])) {
                    $payload['descriptions'][] = [
                        'typeCode' => $desc['typeCode'] ?? 'house',
                        'language' => strtoupper($lang),
                        'text' => $desc['text']
                    ];
                }
            }
            // Include identifiers if API requires them
            try {
                $current = $this->nextPaxService->getProperty($propertyId);
                if (!empty($current['supplierPropertyId'])) {
                    $payload['supplierPropertyId'] = $current['supplierPropertyId'];
                }
            } catch (\Exception $ignore) {}
            $payload['propertyManager'] = Auth::user()->property_manager_code;

            $resp = $this->nextPaxService->updateProperty($propertyId, $payload);
            return response()->json(['success' => true, 'data' => $resp]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateImagesApi(Request $request, string $propertyId)
    {
        try {
            $images = $request->get('images', []);
            // Ensure required fields per schema
            $images = array_map(function($img){
                $img['lastUpdated'] = $img['lastUpdated'] ?? now()->toDateString();
                $img['displayPriority'] = isset($img['displayPriority']) ? (int)$img['displayPriority'] : 0;
                return $img;
            }, $images);
            $resp = $this->nextPaxService->updatePropertyImages($propertyId, ['images' => $images]);
            return response()->json(['success' => true, 'data' => $resp]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateFees(Request $request, string $propertyId)
    {
        try {
            $fees = array_values(array_filter($request->get('fees', []), fn($f) => !empty($f['feeCode'])));
            $payload = [
                'supplierPropertyId' => $request->get('supplierPropertyId', 'tmp'),
                'propertyManager' => Auth::user()->property_manager_code,
                'fees' => $fees
            ];
            $resp = $this->nextPaxService->updateProperty($propertyId, $payload);
            return response()->json(['success' => true, 'data' => $resp]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateTaxes(Request $request, string $propertyId)
    {
        try {
            $taxes = array_values(array_filter($request->get('taxes', []), fn($t) => !empty($t['taxCode'])));
            $payload = [
                'supplierPropertyId' => $request->get('supplierPropertyId', 'tmp'),
                'propertyManager' => Auth::user()->property_manager_code,
                'taxes' => $taxes
            ];
            $resp = $this->nextPaxService->updateProperty($propertyId, $payload);
            return response()->json(['success' => true, 'data' => $resp]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateNearestPlaces(Request $request, string $propertyId)
    {
        try {
            $np = array_values(array_filter($request->get('nearestPlaces', []), fn($p) => !empty($p['typeCode'])));
            $payload = [
                'supplierPropertyId' => $request->get('supplierPropertyId', 'tmp'),
                'propertyManager' => Auth::user()->property_manager_code,
                'nearestPlaces' => $np
            ];
            $resp = $this->nextPaxService->updateProperty($propertyId, $payload);
            return response()->json(['success' => true, 'data' => $resp]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($propertyId)
    {
        try {
            $property = Property::findOrFail($propertyId);
            
            // Delete from NextPax API if channel_property_id exists
            if ($property->channel_property_id) {
                $this->nextPaxService->deleteProperty($property->channel_property_id);
            }
            
            // Delete all images
            foreach ($property->images as $image) {
                $image->deleteImage();
            }
            
            // Delete property
            $property->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Propriedade excluída com sucesso!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao excluir propriedade: ' . $e->getMessage()], 500);
        }
    }

    public function uploadImages(Request $request, $propertyId)
    {
        try {
            $property = Property::findOrFail($propertyId);
            
            $validator = Validator::make($request->all(), [
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                'type' => 'required|in:main,gallery,floorplan,amenity'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $uploadedImages = [];
            
            foreach ($request->file('images') as $image) {
                $uploadedImages[] = $this->uploadPropertyImage($property, $image, $request->type);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagens enviadas com sucesso!',
                'images' => $uploadedImages
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao enviar imagens: ' . $e->getMessage()], 500);
        }
    }

    public function deleteImage($propertyId, $imageId)
    {
        try {
            $property = Property::findOrFail($propertyId);
            $image = $property->images()->findOrFail($imageId);
            
            $image->deleteImage();
            
            return response()->json([
                'success' => true,
                'message' => 'Imagem excluída com sucesso!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao excluir imagem: ' . $e->getMessage()], 500);
        }
    }

    public function reorderImages(Request $request, $propertyId)
    {
        try {
            $property = Property::findOrFail($propertyId);
            
            $validator = Validator::make($request->all(), [
                'images' => 'required|array',
                'images.*.id' => 'required|exists:property_images,id',
                'images.*.sort_order' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            foreach ($request->images as $imageData) {
                $image = $property->images()->find($imageData['id']);
                if ($image) {
                    $image->moveToPosition($imageData['sort_order']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Ordem das imagens atualizada com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao reordenar imagens: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ativar propriedade na NextPax
     */
    public function activate(Request $request, $id)
    {
        try {
            $property = Property::findOrFail($id);
            
            if ($property->status === 'active') {
                return response()->json(['error' => 'Propriedade já está ativa'], 400);
            }

            // Ativar na NextPax (definir disponibilidade)
            $response = $this->nextPaxService->activateProperty($property->channel_property_id);
            
            // Verificar se a resposta foi bem-sucedida
            if (isset($response['data']) || isset($response['requestId'])) {
                // Atualizar status local
                $property->update([
                    'is_active' => true,
                    'status' => 'active'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Propriedade ativada com sucesso na NextPax!',
                    'property' => $property
                ]);
            } else {
                throw new \Exception('Erro ao ativar na NextPax: ' . ($response['message'] ?? 'Resposta inválida da API'));
            }

        } catch (\Exception $e) {
            Log::error('Erro ao ativar propriedade:', [
                'property_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro ao ativar propriedade: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar preços e configurações da propriedade
     */
    public function updatePricing(Request $request, $id)
    {
        try {
            $property = Property::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'base_price' => 'required|numeric|min:0',
                'currency' => 'required|string|in:BRL,USD,EUR',
                'nightly_rate' => 'nullable|numeric|min:0',
                'weekly_rate' => 'nullable|numeric|min:0',
                'monthly_rate' => 'nullable|numeric|min:0',
                'cleaning_fee' => 'nullable|numeric|min:0',
                'security_deposit' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Atualizar preços localmente
            $property->update($request->only([
                'base_price', 'currency', 'nightly_rate', 'weekly_rate', 
                'monthly_rate', 'cleaning_fee', 'security_deposit'
            ]));

            // Atualizar preços na NextPax
            $pricingPayload = $this->buildPricingPayload($property, $request->all());
            $response = $this->nextPaxService->updatePropertyPricing($property->channel_property_id, $pricingPayload);

            // Verificar se a resposta foi bem-sucedida
            if (isset($response['data']) || isset($response['requestId'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Preços atualizados com sucesso na NextPax!',
                    'property' => $property
                ]);
            } else {
                // Se falhar na API, ainda salvamos localmente
                Log::warning('Falha ao atualizar preços na NextPax, mas salvos localmente:', [
                    'property_id' => $id,
                    'api_response' => $response
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Preços salvos localmente. Erro na API: ' . ($response['message'] ?? 'Erro desconhecido'),
                    'property' => $property
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar preços:', [
                'property_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro ao atualizar preços: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar dados da propriedade na API NextPax
     */
    public function getApiData($id)
    {
        try {
            // Buscar propriedade por ID local, channel_property_id ou property_id
            $property = Property::where('id', $id)
                ->orWhere('channel_property_id', $id)
                ->orWhere('property_id', $id)
                ->first();
            
            if (!$property) {
                return response()->json(['error' => 'Propriedade não encontrada'], 404);
            }
            
            if (!$property->channel_property_id) {
                return response()->json(['error' => 'Propriedade não possui ID da NextPax'], 400);
            }

            // Buscar dados completos da API
            $apiData = $this->nextPaxService->getPropertyComplete($property->channel_property_id);
            
            if (isset($apiData['error'])) {
                return response()->json(['error' => $apiData['error']], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $apiData,
                'property' => $property
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados da API:', [
                'property_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erro ao buscar dados da API: ' . $e->getMessage()
            ], 500);
        }
    }

    private function uploadPropertyImage(Property $property, $imageFile, string $type): PropertyImage
    {
        $fileName = time() . '_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
        $path = 'properties/' . $property->id . '/' . $type . '/' . $fileName;
        
        // Store original image
        Storage::put($path, file_get_contents($imageFile));
        
        // Create thumbnail if it's a gallery image
        if ($type === 'gallery') {
            $this->createThumbnail($path, $fileName, $property->id, $type);
        }
        
        // Get next sort order
        $nextSortOrder = $property->images()->where('type', $type)->max('sort_order') + 1;
        
        // Create database record
        return PropertyImage::create([
            'property_id' => $property->id,
            'image_path' => $path,
            'image_name' => $imageFile->getClientOriginalName(),
            'alt_text' => $property->name . ' - ' . ucfirst($type),
            'type' => $type,
            'sort_order' => $nextSortOrder,
            'is_active' => true
        ]);
    }

    private function createThumbnail(string $originalPath, string $fileName, int $propertyId, string $type): void
    {
        try {
            $thumbnailPath = 'properties/' . $propertyId . '/' . $type . '/thumbnails/' . $fileName;
            
            // Create thumbnail using Intervention Image
            $image = Image::make(Storage::path($originalPath));
            $image->fit(300, 200, function ($constraint) {
                $constraint->upsize();
            });
            
            Storage::put($thumbnailPath, $image->encode());
            
        } catch (\Exception $e) {
            // If thumbnail creation fails, continue without it
            \Log::warning('Failed to create thumbnail for image: ' . $originalPath, ['error' => $e->getMessage()]);
        }
    }

    private function buildNextPaxPayload(Property $property, array $data): array
    {
        $country = $property->country ?: ($data['country'] ?? 'BR');
        $state = $property->state ?: ($data['state'] ?? null);
        $stateCode = $this->buildStateCode($country, $state);
        $typeCode = $this->mapPropertyTypeToNextPaxCode($property->property_type ?: ($data['property_type'] ?? 'apartment'));

        $payload = [
            'supplierPropertyId' => 'prop-' . Str::random(12),
            'propertyManager' => Auth::user()->property_manager_code,
            'general' => [
                'name' => $property->name ?? ($data['name'] ?? ''),
                'minOccupancy' => 1,
                'maxAdults' => (int) ($property->max_adults ?? ($data['max_adults'] ?? 2)),
                'maxOccupancy' => (int) ($property->max_occupancy ?? ($data['max_occupancy'] ?? 2)),
                'classification' => 'single-unit',
                'baseCurrency' => 'BRL',
                'typeCode' => $typeCode,
                'address' => [
                    'city' => $property->city ?? ($data['city'] ?? ''),
                    'countryCode' => strtoupper($country ?? 'BR'),
                    'street' => $property->address ?? ($data['address'] ?? ''),
                    'postalCode' => $property->postal_code ?? ($data['postal_code'] ?? ''),
                ],
                'checkInOutTimes' => [
                    'checkInFrom' => '14:00',
                    'checkInUntil' => '22:00',
                    'checkOutFrom' => '08:00',
                    'checkOutUntil' => '11:00',
                ],
                'geoLocation' => [
                    'latitude' => (float) ($property->latitude ?? ($data['latitude'] ?? 0)),
                    'longitude' => (float) ($property->longitude ?? ($data['longitude'] ?? 0)),
                ],
            ],
        ];

        if ($stateCode) {
            $payload['general']['address']['state'] = $stateCode;
        }

        return $payload;
    }

    private function buildStateCode(?string $country, ?string $state): ?string
    {
        if (!$country || !$state) return null;
        $countryCode = $this->normalizeCountryCode($country);
        // Apenas BR mapeado por enquanto
        if ($countryCode === 'BR') {
            // Se já vier BR_SP, mantém
            if (strtoupper($state) === $state && str_starts_with($state, 'BR_')) return $state;
            $map = [
                'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM', 'BA' => 'BA', 'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES', 'GO' => 'GO',
                'MA' => 'MA', 'MT' => 'MT', 'MS' => 'MS', 'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB', 'PR' => 'PR', 'PE' => 'PE', 'PI' => 'PI',
                'RJ' => 'RJ', 'RN' => 'RN', 'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC', 'SP' => 'SP', 'SE' => 'SE', 'TO' => 'TO',
                // nomes completos
                'ACRE' => 'AC', 'ALAGOAS' => 'AL', 'AMAPA' => 'AP', 'AMAPÁ' => 'AP', 'AMAZONAS' => 'AM', 'BAHIA' => 'BA', 'CEARÁ' => 'CE', 'CEARA' => 'CE',
                'DISTRITO FEDERAL' => 'DF', 'ESPIRITO SANTO' => 'ES', 'ESPÍRITO SANTO' => 'ES', 'GOIÁS' => 'GO', 'GOIAS' => 'GO', 'MARANHÃO' => 'MA', 'MARANHAO' => 'MA',
                'MATO GROSSO' => 'MT', 'MATO GROSSO DO SUL' => 'MS', 'MINAS GERAIS' => 'MG', 'PARÁ' => 'PA', 'PARA' => 'PA', 'PARAÍBA' => 'PB', 'PARAIBA' => 'PB',
                'PARANÁ' => 'PR', 'PARANA' => 'PR', 'PERNAMBUCO' => 'PE', 'PIAUÍ' => 'PI', 'PIAUI' => 'PI', 'RIO DE JANEIRO' => 'RJ', 'RIO GRANDE DO NORTE' => 'RN',
                'RIO GRANDE DO SUL' => 'RS', 'RONDÔNIA' => 'RO', 'RONDONIA' => 'RO', 'RORAIMA' => 'RR', 'SANTA CATARINA' => 'SC', 'SÃO PAULO' => 'SP', 'SAO PAULO' => 'SP',
                'SERGIPE' => 'SE', 'TOCANTINS' => 'TO'
            ];
            $key = strtoupper($state);
            $uf = $map[$key] ?? (strlen($key) === 2 ? $key : null);
            return $uf ? "BR_{$uf}" : null;
        }
        return null;
    }

    private function mapPropertyTypeToNextPaxCode(string $type): string
    {
        $map = [
            'apartment' => 'APP',
            'house' => 'HOU',
            'hotel' => 'HOT',
            'hostel' => 'HST',
            'resort' => 'RSR',
            'villa' => 'VIL',
            'cabin' => 'CAB',
            'loft' => 'LOF',
        ];
        return $map[strtolower($type)] ?? 'APP';
    }

    private function mapNextPaxTypeToLocal(string $code): string
    {
        $map = [
            'APP' => 'apartment',
            'HOU' => 'house',
            'HOT' => 'hotel',
            'HST' => 'hostel',
            'RSR' => 'resort',
            'VIL' => 'villa',
            'CAB' => 'cabin',
            'LOF' => 'loft',
        ];
        return $map[strtoupper($code)] ?? 'apartment';
    }

    private function normalizeCountryCode(?string $country): string
    {
        if (!$country) return 'BR';
        $upper = strtoupper(trim($country));
        // Já é código de 2 letras
        if (strlen($upper) === 2) return $upper;
        // Mapear nomes comuns
        $map = [
            'BRASIL' => 'BR', 'BRAZIL' => 'BR', 'BRASIL - BR' => 'BR',
            'UNITED STATES' => 'US', 'ESTADOS UNIDOS' => 'US', 'EUA' => 'US',
            'PORTUGAL' => 'PT', 'ESPANHA' => 'ES', 'SPAIN' => 'ES', 'ARGENTINA' => 'AR'
        ];
        return $map[$upper] ?? 'BR';
    }

    /**
     * Verifica se uma string é um UUID válido
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Valida o payload antes de enviar para a API NextPax
     */
    private function validateNextPaxPayload(array $payload): void
    {
        $errors = [];

        // Validação básica dos campos obrigatórios
        if (empty($payload['supplierPropertyId'])) {
            $errors[] = 'supplierPropertyId é obrigatório';
        }

        if (empty($payload['propertyManager'])) {
            $errors[] = 'propertyManager é obrigatório';
        }

        if (empty($payload['general']['name'])) {
            $errors[] = 'general.name é obrigatório';
        }

        if (empty($payload['general']['address']['city'])) {
            $errors[] = 'general.address.city é obrigatório';
        }

        if (empty($payload['general']['address']['street'])) {
            $errors[] = 'general.address.street é obrigatório';
        }

        if (empty($payload['general']['checkInOutTimes']['checkInFrom'])) {
            $errors[] = 'general.checkInOutTimes.checkInFrom é obrigatório';
        }

        if (empty($payload['general']['checkInOutTimes']['checkInUntil'])) {
            $errors[] = 'general.checkInOutTimes.checkInUntil é obrigatório';
        }

        if (empty($payload['general']['checkInOutTimes']['checkOutFrom'])) {
            $errors[] = 'general.checkInOutTimes.checkOutFrom é obrigatório';
        }

        if (empty($payload['general']['checkInOutTimes']['checkOutUntil'])) {
            $errors[] = 'general.checkInOutTimes.checkOutUntil é obrigatório';
        }

        if (empty($payload['general']['maxAdults'])) {
            $errors[] = 'general.maxAdults é obrigatório';
        }

        if (empty($payload['general']['maxOccupancy'])) {
            $errors[] = 'general.maxOccupancy é obrigatório';
        }

        // Se houver erros, loga e lança exceção
        if (!empty($errors)) {
            \Log::error('Payload NextPax inválido:', [
                'errors' => $errors,
                'payload' => $payload
            ]);
            throw new \InvalidArgumentException('Payload NextPax inválido: ' . implode(', ', $errors));
        }
    }

    /**
     * Formata o horário para o formato HH:MM esperado pela API NextPax
     */
    private function formatTimeForNextPax($time): string
    {
        // Se for null ou vazio, retorna horário padrão
        if (empty($time)) {
            return '14:00';
        }
        
        // Se for string, tenta fazer parse
        if (is_string($time)) {
            // Remove segundos se existirem (formato H:i:s -> H:i)
            if (strlen($time) > 5) {
                $time = substr($time, 0, 5);
            }
            
            // Valida se é um formato de hora válido
            if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                return $time;
            }
            
            // Tenta fazer parse com Carbon
            try {
                $carbonTime = \Carbon\Carbon::parse($time);
                return $carbonTime->format('H:i');
            } catch (\Exception $e) {
                \Log::warning('Failed to parse time: ' . $time, ['error' => $e->getMessage()]);
                return '14:00';
            }
        }
        
        // Se for Carbon, formata
        if ($time instanceof \Carbon\Carbon) {
            return $time->format('H:i');
        }
        
        // Se for DateTime, formata
        if ($time instanceof \DateTime) {
            return $time->format('H:i');
        }
        
        // Fallback para horário padrão
        \Log::warning('Unknown time format: ' . gettype($time) . ' - ' . $time);
        return '14:00';
    }

    /**
     * Converte um payload da API em um modelo Property NÃO persistido
     */
    private function mapApiPropertyToModel(array $apiProperty): Property
    {
        $property = new Property();
        $general = $apiProperty['general'] ?? [];
        $address = $general['address'] ?? [];

        $property->name = $general['name'] ?? 'Propriedade NextPax';
        $property->property_id = $apiProperty['propertyId'] ?? ($apiProperty['id'] ?? null);
        $property->channel_type = 'nextpax';
        $property->channel_property_id = $apiProperty['propertyId'] ?? null;
        $property->address = $address['street'] ?? '';
        $property->city = $address['city'] ?? '';
        $property->state = $address['state'] ?? '';
        $property->country = $address['countryCode'] ?? 'BR';
        $property->postal_code = $address['postalCode'] ?? '';
        $property->description = $general['description'] ?? '';
        $property->property_type = $this->mapNextPaxTypeToLocal($general['typeCode'] ?? 'APP');
        $property->max_occupancy = $general['maxOccupancy'] ?? 2;
        $property->max_adults = $general['maxAdults'] ?? 2;
        $property->currency = $general['baseCurrency'] ?? 'BRL';
        $times = $general['checkInOutTimes'] ?? [];
        $property->check_in_from = $times['checkInFrom'] ?? '14:00';
        $property->check_in_until = $times['checkInUntil'] ?? '22:00';
        $property->check_out_from = $times['checkOutFrom'] ?? '08:00';
        $property->check_out_until = $times['checkOutUntil'] ?? '11:00';
        $property->status = 'active';

        // Extras opcionais
        $property->amenities = $general['amenities'] ?? [];
        $property->house_rules = $general['houseRules'] ?? [];
        $property->base_price = $general['basePrice'] ?? null;

        return $property;
    }

    private function mapAmenitiesToNextPax(array $amenities): array
    {
        // Minimal mapping; should be extended using mapping-codes endpoint
        $map = [
            'wifi' => 'A19', // example
            'ar-condicionado' => 'A05',
            'estacionamento' => 'A03',
            'piscina' => 'A12',
        ];
        $result = [];
        foreach ($amenities as $a) {
            $code = $map[strtolower($a)] ?? null;
            if ($code) {
                $result[] = [
                    'typeCode' => $code,
                    'attributes' => ['Y'] // simplistic attribute
                ];
            }
        }
        return $result;
    }

    /**
     * Construir payload para atualização de preços
     */
    private function buildPricingPayload($property, $data)
    {
        return [
            'propertyId' => $property->channel_property_id,
            'pricing' => [
                'basePrice' => [
                    'amount' => $data['base_price'],
                    'currency' => $data['currency'] ?? 'BRL'
                ],
                'nightlyRate' => isset($data['nightly_rate']) && $data['nightly_rate'] > 0 ? $data['nightly_rate'] : null,
                'weeklyRate' => isset($data['weekly_rate']) && $data['weekly_rate'] > 0 ? $data['weekly_rate'] : null,
                'monthlyRate' => isset($data['monthly_rate']) && $data['monthly_rate'] > 0 ? $data['monthly_rate'] : null,
                'cleaningFee' => isset($data['cleaning_fee']) && $data['cleaning_fee'] > 0 ? $data['cleaning_fee'] : null,
                'securityDeposit' => isset($data['security_deposit']) && $data['security_deposit'] > 0 ? $data['security_deposit'] : null,
            ]
        ];
    }
} 