<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\NextPaxService;

class AuthController extends Controller
{
    private NextPaxService $nextPaxService;

    public function __construct(NextPaxService $nextPaxService)
    {
        $this->nextPaxService = $nextPaxService;
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Redirecionar admins para o dashboard administrativo
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->intended(route('admin.dashboard'));
            }
            
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->withInput($request->only('email'));
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        try {
            // 1) Criar Property Manager na API NextPax
            $payload = [
                'companyName' => $request->company_name,
                'general' => [
                    'companyEmail' => $request->email,
                    'companyPhone' => preg_replace('/\D+/', '', (string) $request->phone),
                    'countryCode' => 'BR',
                    'mainCurrency' => 'BRL',
                    'spokenLanguages' => ['pt', 'en'],
                    'acceptedCurrencies' => ['BRL'],
                    'checkInOutTimes' => [
                        'checkInFrom' => '14:00',
                        'checkInUntil' => '22:00',
                        'checkOutFrom' => '08:00',
                        'checkOutUntil' => '11:00',
                    ],
                ],
                'contacts' => [[
                    'firstName' => $request->name,
                    'lastName' => $request->last_name,
                    'email' => $request->email,
                    'telephone' => preg_replace('/\D+/', '', (string) $request->phone),
                    'role' => 'main',
                    'country' => 'BR',
                    'address' => $request->input('address'),
                    'postalCode' => preg_replace('/\s+/', '', (string) $request->input('company_postal_code', '')),
                    'city' => $request->input('city'),
                    'state' => 'BR_SP',
                    'region' => 'BR_SP_1',
                ]],
                'ratesAndAvailabilitySettings' => [
                    'defaultMinStay' => 1,
                    'defaultMaxStay' => 30,
                    'monthLength' => 30,
                    'minBookingOffset' => 0,
                    'maxBookingOffset' => 12,
                ],
                'invoiceDetails' => [
                    'entityName' => $request->company_name,
                    'firstName' => $request->name,
                    'lastName' => $request->last_name,
                    'email' => $request->email,
                    'telephone' => preg_replace('/\D+/', '', (string) $request->phone),
                    'entityAddress1' => $request->input('address', ''),
                    'entityCity' => $request->input('city', ''),
                    'entityState' => 'BR_SP',
                    'entityCountry' => 'BR',
                    'entityPostalCode' => preg_replace('/\s+/', '', (string) $request->input('company_postal_code', '')),
                    'entityRegion' => 'BR_SP_1',
                ],
            ];

            // Campos opcionais em general
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


            $pmResponse = $this->nextPaxService->createPropertyManager($payload);
            $propertyManagerCode = $pmResponse['data']['propertyManager'] ?? null;
            if (!$propertyManagerCode) {
                throw new \Exception('Resposta inválida da API ao criar o Property Manager');
            }

            // 2) Criar usuário local vinculado ao tenant
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
                'password' => Hash::make($request->password),
                'property_manager_code' => $propertyManagerCode,
                'role' => 'supply',
                'is_active' => true,
            'phone' => $request->phone,
            'company_name' => $request->company_name,
        ]);

            // 3) Autenticar e redirecionar ao perfil
        Auth::login($user);

            return redirect()->route('profile.index')
                ->with('success', 'Conta criada e tenant provisionado com sucesso! Código: ' . $propertyManagerCode);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erro ao criar conta (API): ' . $e->getMessage()])->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
