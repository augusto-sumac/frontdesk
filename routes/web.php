<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\PropertyChannelController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AdminDashboardController;

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

// ===== WEBHOOKS (sem middleware de autenticação) =====
Route::prefix('webhooks')->group(function () {
    Route::post('/airbnb', [WebhookController::class, 'airbnb'])->name('webhooks.airbnb');
    Route::post('/booking', [WebhookController::class, 'booking'])->name('webhooks.booking');
    Route::post('/homeaway', [WebhookController::class, 'homeaway'])->name('webhooks.homeaway');
    Route::post('/vrbo', [WebhookController::class, 'vrbo'])->name('webhooks.vrbo');
    Route::post('/nextpax', [WebhookController::class, 'nextpax'])->name('webhooks.nextpax');
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
        
        // Novas rotas para ativação e preços
        Route::post('/{propertyId}/activate', [PropertyController::class, 'activate'])->name('properties.activate');
        Route::post('/{propertyId}/pricing', [PropertyController::class, 'updatePricing'])->name('properties.pricing');
        Route::get('/{propertyId}/api-data', [PropertyController::class, 'getApiData'])->name('properties.api-data');
        
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
        
        // ===== CANAIS DE DISTRIBUIÇÃO =====
        Route::prefix('{propertyId}/channels')->group(function () {
            Route::get('/', [PropertyChannelController::class, 'index'])->name('properties.channels.index');
            Route::get('/create/{channel}', [PropertyChannelController::class, 'create'])->name('properties.channels.create');
            Route::post('/store/{channel}', [PropertyChannelController::class, 'store'])->name('properties.channels.store');
            Route::get('/{channel}', [PropertyChannelController::class, 'show'])->name('properties.channels.show');
            Route::get('/{channel}/edit', [PropertyChannelController::class, 'edit'])->name('properties.channels.edit');
            Route::put('/{channel}', [PropertyChannelController::class, 'update'])->name('properties.channels.update');
            Route::delete('/{channel}', [PropertyChannelController::class, 'destroy'])->name('properties.channels.destroy');
            Route::post('/{channel}/sync', [PropertyChannelController::class, 'sync'])->name('properties.channels.sync');
            Route::post('/{channel}/toggle-active', [PropertyChannelController::class, 'toggleActive'])->name('properties.channels.toggle-active');
            Route::post('/{channel}/toggle-auto-sync', [PropertyChannelController::class, 'toggleAutoSync'])->name('properties.channels.toggle-auto-sync');
            Route::get('/statistics', [PropertyChannelController::class, 'statistics'])->name('properties.channels.statistics');
        });
    });
    
    // ===== CANAIS GLOBAIS =====
    Route::prefix('channels')->middleware('tenant')->group(function () {
        Route::get('/', [ChannelController::class, 'index'])->name('channels.index');
        Route::get('/create', [ChannelController::class, 'create'])->name('channels.create');
        Route::post('/', [ChannelController::class, 'store'])->name('channels.store');
        Route::get('/{channel}', [ChannelController::class, 'show'])->name('channels.show');
        Route::get('/{channel}/properties', [ChannelController::class, 'properties'])->name('channels.properties');
        Route::post('/{channel}/sync', [ChannelController::class, 'syncProperties'])->name('channels.sync');
        Route::get('/{channel}/edit', [ChannelController::class, 'edit'])->name('channels.edit');
        Route::put('/{channel}', [ChannelController::class, 'update'])->name('channels.update');
        Route::delete('/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');
        Route::post('/{channel}/connect-property', [ChannelController::class, 'connectProperty'])->name('channels.connect-property');
        Route::delete('/{channel}/disconnect-property/{property}', [ChannelController::class, 'disconnectProperty'])->name('channels.disconnect-property');
        Route::put('/{channel}/update-property/{property}', [ChannelController::class, 'updatePropertyChannel'])->name('channels.update-property');
        Route::post('/{channel}/sync-property/{property}', [ChannelController::class, 'syncProperty'])->name('channels.sync-property');
        Route::get('/available/{property}', [ChannelController::class, 'getAvailableChannels'])->name('channels.available');
        Route::get('/status/{channel}/{property}', [ChannelController::class, 'getPropertyChannelStatus'])->name('channels.status');
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
    
    // ===== ADMINISTRAÇÃO =====
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/channels', [AdminDashboardController::class, 'channels'])->name('admin.channels');
        Route::get('/reports', [AdminDashboardController::class, 'reports'])->name('admin.reports');
        Route::get('/monitoring', [AdminDashboardController::class, 'monitoring'])->name('admin.monitoring');
        
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('admin.users.index');
            Route::get('/create', [AdminController::class, 'create'])->name('admin.users.create');
            Route::post('/', [AdminController::class, 'store'])->name('admin.users.store');
            Route::get('/{user}/edit', [AdminController::class, 'edit'])->name('admin.users.edit');
            Route::put('/{user}', [AdminController::class, 'update'])->name('admin.users.update');
            Route::delete('/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
            Route::post('/{user}/toggle-status', [AdminController::class, 'toggleStatus'])->name('admin.users.toggle-status');
            Route::post('/{user}/change-password', [AdminController::class, 'changePassword'])->name('admin.users.change-password');
        });
    });
});
