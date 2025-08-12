<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Rotas de autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rotas protegidas
Route::middleware(['auth','tenant'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // ===== PERFIL =====
    Route::prefix('profile')->withoutMiddleware('tenant')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('profile.index');
        Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    });
    
    // ===== RESERVAS CRUD =====
    Route::prefix('bookings')->middleware('tenant')->group(function () {
        Route::get('/', [DashboardController::class, 'bookings'])->name('bookings.index');
        Route::get('/{bookingId}', [DashboardController::class, 'showBooking'])->name('bookings.show');
        Route::post('/', [DashboardController::class, 'createBooking'])->name('bookings.create');
        Route::put('/{bookingId}', [DashboardController::class, 'updateBooking'])->name('bookings.update');
        Route::delete('/{bookingId}', [DashboardController::class, 'cancelBooking'])->name('bookings.cancel');
        Route::post('/accept', [DashboardController::class, 'acceptBooking'])->name('bookings.accept');
        Route::post('/reject', [DashboardController::class, 'rejectBooking'])->name('bookings.reject');
        Route::get('/context/{propertyId}', [DashboardController::class, 'bookingPropertyContext'])->name('bookings.property.context');
        Route::post('/sync-pending', [DashboardController::class, 'syncPendingBookings'])->name('bookings.sync-pending');
    });
    
    // ===== PROPRIEDADES CRUD =====
    Route::prefix('properties')->middleware('tenant')->group(function () {
        Route::get('/', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/create', [PropertyController::class, 'create'])->name('properties.create');
        Route::post('/', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('/{propertyId}', [PropertyController::class, 'show'])->name('properties.show');
        Route::get('/{propertyId}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
                 Route::put('/{propertyId}', [PropertyController::class, 'update'])->name('properties.update');
         Route::post('/{propertyId}/general', [PropertyController::class, 'updateGeneral'])->name('properties.update.general');
         Route::post('/{propertyId}/descriptions', [PropertyController::class, 'updateDescriptions'])->name('properties.update.descriptions');
         Route::post('/{propertyId}/images', [PropertyController::class, 'updateImagesApi'])->name('properties.update.images.api');
         Route::post('/{propertyId}/fees', [PropertyController::class, 'updateFees'])->name('properties.update.fees');
         Route::post('/{propertyId}/taxes', [PropertyController::class, 'updateTaxes'])->name('properties.update.taxes');
         Route::post('/{propertyId}/nearest-places', [PropertyController::class, 'updateNearestPlaces'])->name('properties.update.nearest');
        Route::delete('/{propertyId}', [PropertyController::class, 'destroy'])->name('properties.delete');
        
        // Image management
        Route::post('/{propertyId}/images', [PropertyController::class, 'uploadImages'])->name('properties.images.upload');
        Route::delete('/{propertyId}/images/{imageId}', [PropertyController::class, 'deleteImage'])->name('properties.images.delete');
        Route::post('/{propertyId}/images/reorder', [PropertyController::class, 'reorderImages'])->name('properties.images.reorder');
        
        // Quartos (subrooms) - keeping for backward compatibility
        Route::get('/{propertyId}/subrooms', [DashboardController::class, 'subrooms'])->name('properties.subrooms');
        Route::post('/{propertyId}/subrooms', [DashboardController::class, 'createSubroom'])->name('properties.subrooms.create');
    });
    
    // ===== MENSAGENS CRUD =====
    Route::prefix('messages')->middleware('tenant')->group(function () {
        Route::get('/', [DashboardController::class, 'messages'])->name('messages.index');
        Route::get('/{threadId}', [DashboardController::class, 'showThread'])->name('messages.thread');
        Route::post('/send', [DashboardController::class, 'sendMessage'])->name('messages.send');
    });
    
    // ===== CALENDÁRIO =====
    Route::get('/calendar', [DashboardController::class, 'calendar'])->middleware('tenant')->name('calendar.index');
    
    // ===== RELATÓRIOS =====
    Route::get('/reports', [DashboardController::class, 'reports'])->middleware('tenant')->name('reports.index');
});
