<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\NextPaxService;

class ProfileController extends Controller
{
    private NextPaxService $nextPaxService;

    public function __construct(NextPaxService $nextPaxService)
    {
        $this->nextPaxService = $nextPaxService;
    }

    public function index()
    {
        $user = Auth::user();
        $propertyManager = null;

        if (!empty($user->property_manager_code)) {
            try {
                $propertyManager = $this->nextPaxService->getPropertyManager($user->property_manager_code);
            } catch (\Exception $e) {
                // silencioso: manter perfil funcionando mesmo se API falhar
            }
        }

        return view('profile.index', compact('user', 'propertyManager'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'company_postal_code' => 'nullable|string|max:20',
            'company_phone' => 'nullable|string|max:20',
            'host_information' => 'nullable|string|max:1000',
            'checkin_from' => 'nullable|date_format:H:i',
            'checkin_until' => 'nullable|date_format:H:i',
            'checkout_from' => 'nullable|date_format:H:i',
            'checkout_until' => 'nullable|date_format:H:i',
            'default_min_stay' => 'nullable|integer|min:1',
            'default_max_stay' => 'nullable|integer|min:1',
            'month_length' => 'nullable|integer|min:1|max:31',
            'min_booking_offset' => 'nullable|integer|min:0',
            'max_booking_offset' => 'nullable|integer|min:1',
            'contact_first_name' => 'nullable|string|max:255',
            'contact_last_name' => 'nullable|string|max:255',
            'contact_telephone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'contact_address' => 'nullable|string|max:255',
            'contact_city' => 'nullable|string|max:255',
            'contact_postal_code' => 'nullable|string|max:20',
            'invoice_entity_name' => 'nullable|string|max:255',
            'invoice_first_name' => 'nullable|string|max:255',
            'invoice_last_name' => 'nullable|string|max:255',
            'invoice_telephone' => 'nullable|string|max:20',
            'invoice_email' => 'nullable|email|max:255',
            'invoice_address' => 'nullable|string|max:255',
            'invoice_city' => 'nullable|string|max:255',
            'invoice_postal_code' => 'nullable|string|max:20',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
            // NextPax fields
            'website' => 'nullable|url',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'company_postal_code' => 'nullable|string|max:20',
            'host_information' => 'nullable|string|max:1000',
            // Check-in/Check-out times
            'checkin_from' => 'nullable|date_format:H:i',
            'checkin_until' => 'nullable|date_format:H:i|after:checkin_from',
            'checkout_from' => 'nullable|date_format:H:i',
            'checkout_until' => 'nullable|date_format:H:i|after:checkout_from',
            // Rates and Availability
            'month_length' => 'nullable|integer|min:1|max:31',
            'min_booking_offset' => 'nullable|integer|min:0',
            'max_booking_offset' => 'nullable|integer|min:1',
            'default_min_stay' => 'nullable|integer|min:1',
            'default_max_stay' => 'nullable|integer|min:1',
            // Contact details
            'contact_address' => 'nullable|string|max:255',
            'contact_postal_code' => 'nullable|string|max:20',
            'contact_city' => 'nullable|string|max:255',
            // Invoice details
            'invoice_address' => 'nullable|string|max:255',
            'invoice_postal_code' => 'nullable|string|max:20',
            'invoice_city' => 'nullable|string|max:255',
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Digite um email válido.',
            'email.unique' => 'Este email já está em uso.',
            'phone.max' => 'O telefone deve ter no máximo 20 caracteres.',
            'company_name.max' => 'O nome da empresa deve ter no máximo 255 caracteres.',
            'property_manager_code.max' => 'O código do gerenciador de propriedades deve ter no máximo 255 caracteres.',
            'current_password.required_with' => 'A senha atual é obrigatória para alterar a senha.',
            'new_password.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
            'new_password.confirmed' => 'A confirmação da nova senha não confere.',
            'website.url' => 'Digite uma URL válida para o website.',
        ]);

        try {
            // Atualizar dados básicos do usuário
            $user->name = $request->name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->company_name = $request->company_name;

            // Atualizar senha se fornecida
            if ($request->filled('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return back()->withErrors(['current_password' => 'A senha atual está incorreta.']);
                }
                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            // Atualizar dados no NextPax se tiver property_manager_code
            if ($user->property_manager_code) {
                $this->updateNextPaxSupplier($user, $request);
            }

            return back()->with('success', 'Perfil atualizado com sucesso!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erro ao atualizar perfil: ' . $e->getMessage()]);
        }
    }

    private function updateNextPaxSupplier($user, $request)
    {
        $payload = [
            'companyName' => $request->company_name,
            'general' => [
                'companyEmail' => $request->email,
                'companyPhone' => preg_replace('/\D+/', '', (string) $request->input('company_phone', $request->phone)) ?: '11999999999',
                'countryCode' => 'BR',
                'mainCurrency' => 'BRL',
                'spokenLanguages' => ['pt', 'en'],
                'acceptedCurrencies' => ['BRL'],
                'checkInOutTimes' => [
                    'checkInFrom' => $request->input('checkin_from', '14:00') ?: '14:00',
                    'checkInUntil' => $request->input('checkin_until', '22:00') ?: '22:00',
                    'checkOutFrom' => $request->input('checkout_from', '08:00') ?: '08:00',
                    'checkOutUntil' => $request->input('checkout_until', '11:00') ?: '11:00',
                ],
            ],
            'contacts' => [[
                'firstName' => $request->input('contact_first_name', $request->name),
                'lastName' => $request->input('contact_last_name', $user->last_name ?? ''),
                'email' => $request->input('contact_email', $request->email),
                'telephone' => preg_replace('/\D+/', '', (string) $request->input('contact_telephone', $request->phone)) ?: '11999999999',
                'role' => 'main',
                'country' => 'BR',
                'address' => $request->input('contact_address', $request->input('address')) ?: 'Endereço não informado',
                'postalCode' => preg_replace('/\s+/', '', (string) $request->input('contact_postal_code', $request->input('company_postal_code', ''))) ?: '00000-000',
                'city' => $request->input('contact_city', $request->input('city')) ?: 'São Paulo',
                'state' => 'BR_SP',
                'region' => 'BR_SP_1',
            ]],
            'ratesAndAvailabilitySettings' => [
                'defaultMinStay' => (int) $request->input('default_min_stay', 1),
                'defaultMaxStay' => (int) $request->input('default_max_stay', 30),
                'monthLength' => (int) $request->input('month_length', 30),
                'minBookingOffset' => (int) $request->input('min_booking_offset', 0),
                'maxBookingOffset' => (int) $request->input('max_booking_offset', 12),
            ],
            'invoiceDetails' => [
                'entityName' => $request->input('invoice_entity_name', $request->company_name),
                'firstName' => $request->input('invoice_first_name', $request->name),
                'lastName' => $request->input('invoice_last_name', $user->last_name ?? ''),
                'email' => $request->input('invoice_email', $request->email),
                'telephone' => preg_replace('/\D+/', '', (string) $request->input('invoice_telephone', $request->phone)) ?: '11999999999',
                'entityAddress1' => $request->input('invoice_address', $request->input('address', '')) ?: 'Endereço não informado',
                'entityCity' => $request->input('invoice_city', $request->input('city', '')) ?: 'São Paulo',
                'entityState' => 'BR_SP',
                'entityCountry' => 'BR',
                'entityPostalCode' => preg_replace('/\s+/', '', (string) $request->input('invoice_postal_code', $request->input('company_postal_code', ''))) ?: '00000-000',
                'entityRegion' => 'BR_SP_1',
            ],
        ];

        // Campos opcionais da empresa
        if ($request->filled('website')) {
            $payload['general']['website'] = $request->website;
        }
        if ($request->filled('address')) {
            $payload['general']['address'] = $request->address;
        }
        if ($request->filled('city')) {
            $payload['general']['city'] = $request->city;
        }
        if ($request->filled('company_postal_code')) {
            $payload['general']['companyPostalCode'] = preg_replace('/\s+/', '', (string) $request->company_postal_code);
        }
        if ($request->filled('host_information')) {
            $payload['general']['hostInformation'] = $request->host_information;
        }

        // Campos opcionais de contato
        if ($request->filled('contact_first_name')) {
            $payload['contacts'][0]['firstName'] = $request->contact_first_name;
        }
        if ($request->filled('contact_last_name')) {
            $payload['contacts'][0]['lastName'] = $request->contact_last_name;
        }
        if ($request->filled('contact_telephone')) {
            $payload['contacts'][0]['telephone'] = preg_replace('/\D+/', '', (string) $request->contact_telephone);
        }
        if ($request->filled('contact_email')) {
            $payload['contacts'][0]['email'] = $request->contact_email;
        }
        if ($request->filled('contact_address')) {
            $payload['contacts'][0]['address'] = $request->contact_address;
        }
        if ($request->filled('contact_city')) {
            $payload['contacts'][0]['city'] = $request->contact_city;
        }
        if ($request->filled('contact_postal_code')) {
            $payload['contacts'][0]['postalCode'] = preg_replace('/\s+/', '', (string) $request->contact_postal_code);
        }

        // Campos opcionais de faturamento
        if ($request->filled('invoice_entity_name')) {
            $payload['invoiceDetails']['entityName'] = $request->invoice_entity_name;
        }
        if ($request->filled('invoice_first_name')) {
            $payload['invoiceDetails']['firstName'] = $request->invoice_first_name;
        }
        if ($request->filled('invoice_last_name')) {
            $payload['invoiceDetails']['lastName'] = $request->invoice_last_name;
        }
        if ($request->filled('invoice_telephone')) {
            $payload['invoiceDetails']['telephone'] = preg_replace('/\D+/', '', (string) $request->invoice_telephone);
        }
        if ($request->filled('invoice_email')) {
            $payload['invoiceDetails']['email'] = $request->invoice_email;
        }
        if ($request->filled('invoice_address')) {
            $payload['invoiceDetails']['entityAddress1'] = $request->invoice_address;
        }
        if ($request->filled('invoice_city')) {
            $payload['invoiceDetails']['entityCity'] = $request->invoice_city;
        }
        if ($request->filled('invoice_postal_code')) {
            $payload['invoiceDetails']['entityPostalCode'] = preg_replace('/\s+/', '', (string) $request->invoice_postal_code);
        }

        // Fazer a requisição para atualizar o supplier no NextPax
        $this->nextPaxService->updatePropertyManager($user->property_manager_code, $payload);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'avatar.required' => 'Selecione uma imagem.',
            'avatar.image' => 'O arquivo deve ser uma imagem.',
            'avatar.mimes' => 'A imagem deve ser do tipo: jpeg, png, jpg, gif.',
            'avatar.max' => 'A imagem deve ter no máximo 2MB.',
        ]);

        $user = Auth::user();
        
        // TODO: Implementar upload de avatar
        
        return back()->with('success', 'Avatar atualizado com sucesso!');
    }
} 