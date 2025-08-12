<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'property_id' => 'required|string|max:255|unique:properties',
        ]);

        try {
            DB::beginTransaction();

            // Criar a propriedade com dados mínimos (serão preenchidos via API)
            $property = Property::create([
                'name' => 'Propriedade ' . $request->property_id, // Nome temporário
                'property_id' => $request->property_id,
                'channel_type' => 'nextpax', // Padrão para NextPax
                'channel_property_id' => null,
                'address' => null, // Será preenchido via API
                'city' => null, // Será preenchido via API
                'state' => null, // Será preenchido via API
                'country' => null, // Será preenchido via API
                'phone' => null, // Será preenchido via API
                'email' => null, // Será preenchido via API
                'total_rooms' => 0, // Será preenchido via API
                'is_active' => true,
                'channel_config' => null,
            ]);

            // Criar o usuário
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'property_id' => $property->id,
                'role' => 'owner',
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'is_active' => true,
            ]);

            DB::commit();

            // Fazer login automático
            auth()->login($user);

            return redirect()->route('dashboard')->with('success', 'Conta criada com sucesso! Os dados da propriedade serão sincronizados automaticamente via API.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erro ao criar conta. Tente novamente.'])->withInput();
        }
    }
}
