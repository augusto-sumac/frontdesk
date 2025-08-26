<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\NextPaxService;

class AdminController extends Controller
{
    private $nextPaxService;

    public function __construct(NextPaxService $nextPaxService)
    {
        $this->nextPaxService = $nextPaxService;
    }

    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,supply',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'booking_id' => 'nullable|string|max:255',
            'airbnb_id' => 'nullable|string|max:255',
            'address' => 'required_if:role,supply|nullable|string|max:255',
            'city' => 'required_if:role,supply|nullable|string|max:100',
            'state' => 'required_if:role,supply|nullable|string|max:10',
            'postal_code' => 'required_if:role,supply|nullable|string|max:20',
            'website' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $userData = [
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'is_active' => $request->has('is_active'),
                'booking_id' => $request->booking_id,
                'airbnb_id' => $request->airbnb_id,
            ];

            // Se for um usuário supply, criar Property Manager na NextPax
            if ($request->role === 'supply') {
                $propertyManagerCode = $this->createPropertyManagerInNextPax($request);
                if ($propertyManagerCode) {
                    $userData['property_manager_code'] = $propertyManagerCode;
                }
            }

            User::create($userData);

            $successMessage = 'Usuário criado com sucesso!';
            if ($request->role === 'supply' && isset($propertyManagerCode)) {
                $successMessage .= " Property Manager criado na NextPax com código: {$propertyManagerCode}";
            }

            return redirect()->route('admin.users.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário via admin:', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Erro ao criar usuário: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,supply',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'booking_id' => 'nullable|string|max:255',
            'airbnb_id' => 'nullable|string|max:255',
            'address' => 'required_if:role,supply|nullable|string|max:255',
            'city' => 'required_if:role,supply|nullable|string|max:100',
            'state' => 'required_if:role,supply|nullable|string|max:10',
            'postal_code' => 'required_if:role,supply|nullable|string|max:20',
            'website' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $updateData = [
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'role' => $request->role,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'is_active' => $request->has('is_active'),
                'booking_id' => $request->booking_id,
                'airbnb_id' => $request->airbnb_id,
            ];

            // Se mudou para supply e não tem property_manager_code, criar na NextPax
            if ($request->role === 'supply' && !$user->property_manager_code) {
                $propertyManagerCode = $this->createPropertyManagerInNextPax($request);
                if ($propertyManagerCode) {
                    $updateData['property_manager_code'] = $propertyManagerCode;
                }
            }

            $user->update($updateData);

            $successMessage = 'Usuário atualizado com sucesso!';
            if ($request->role === 'supply' && isset($propertyManagerCode)) {
                $successMessage .= " Property Manager criado na NextPax com código: {$propertyManagerCode}";
            }

            return redirect()->route('admin.users.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar usuário via admin:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'request_data' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Erro ao atualizar usuário: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode deletar sua própria conta!');
        }

        try {
            $user->delete();
            return redirect()->route('admin.users.index')
                ->with('success', 'Usuário deletado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao deletar usuário via admin:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return back()->with('error', 'Erro ao deletar usuário: ' . $e->getMessage());
        }
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode desativar sua própria conta!');
        }

        try {
            $user->update(['is_active' => !$user->is_active]);
            $status = $user->is_active ? 'ativada' : 'desativada';
            return back()->with('success', "Conta do usuário {$status} com sucesso!");
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do usuário via admin:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return back()->with('error', 'Erro ao alterar status: ' . $e->getMessage());
        }
    }

    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with('success', 'Senha alterada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao alterar senha via admin:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return back()->with('error', 'Erro ao alterar senha: ' . $e->getMessage());
        }
    }

    /**
     * Cria Property Manager na API NextPax
     */
    private function createPropertyManagerInNextPax(Request $request): ?string
    {
        try {
            $payload = [
                'companyName' => $request->company_name ?: 'Cliente ' . $request->name,
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
                    'postalCode' => preg_replace('/\s+/', '', (string) $request->input('postal_code', '')),
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
                    'entityName' => $request->company_name ?: 'Cliente ' . $request->name,
                    'firstName' => $request->name,
                    'lastName' => $request->last_name,
                    'email' => $request->email,
                    'telephone' => preg_replace('/\D+/', '', (string) $request->phone),
                    'entityAddress1' => $request->input('address', ''),
                    'entityCity' => $request->input('city', ''),
                    'entityState' => 'BR_SP',
                    'entityCountry' => 'BR',
                    'entityPostalCode' => preg_replace('/\s+/', '', (string) $request->input('postal_code', '')),
                    'entityRegion' => 'BR_SP_1',
                ],
            ];

            // Campos opcionais em general (copiado do AuthController)
            if ($request->filled('website')) {
                $payload['general']['website'] = $request->website;
            }
            if ($request->filled('address')) {
                $payload['general']['address'] = $request->address;
            }
            if ($request->filled('city')) {
                $payload['general']['city'] = $request->city;
            }
            if ($request->filled('postal_code')) {
                $payload['general']['companyPostalCode'] = preg_replace('/\s+/', '', (string) $request->postal_code);
            }

            $response = $this->nextPaxService->createPropertyManager($payload);
            $propertyManagerCode = $response['data']['propertyManager'] ?? null;

            if ($propertyManagerCode) {
                Log::info('Property Manager criado na NextPax via admin:', [
                    'email' => $request->email,
                    'propertyManagerCode' => $propertyManagerCode
                ]);
                return $propertyManagerCode;
            }

            Log::warning('Property Manager não foi criado na NextPax - resposta inválida:', [
                'email' => $request->email,
                'response' => $response
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao criar Property Manager na NextPax via admin:', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
