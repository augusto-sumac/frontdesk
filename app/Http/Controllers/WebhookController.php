<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyChannel;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function airbnb(Request $request)
    {
        Log::info('Airbnb webhook received', ['payload' => $request->all()]);
        
        try {
            $data = $request->all();
            
            // Find property
            $propertyChannel = PropertyChannel::where('channel_property_id', $data['listing_id'])
                ->whereHas('channel', function($query) {
                    $query->where('channel_id', 'AIR298');
                })
                ->first();

            if (!$propertyChannel) {
                throw new \Exception('Property not found');
            }

            $property = $propertyChannel->property;

            // Create or update booking
            $booking = Booking::updateOrCreate(
                [
                    'channel_partner_reference' => $data['reservation_id'],
                    'channel_id' => 'AIR298'
                ],
                [
                    'booking_number' => 'AIR-' . $data['reservation_id'],
                    'property_id' => $property->property_id,
                    'supplier_property_id' => $property->supplier_property_id,
                    'property_manager_code' => $property->property_manager_code,
                    'guest_first_name' => $data['guest']['first_name'],
                    'guest_surname' => $data['guest']['last_name'],
                    'guest_email' => $data['guest']['email'],
                    'check_in_date' => $data['start_date'],
                    'check_out_date' => $data['end_date'],
                    'total_amount' => $data['total_paid'],
                    'currency' => $data['currency'],
                    'status' => 'confirmed',
                    'api_response' => $data,
                    'sync_status' => 'synced',
                    'synced_at' => now(),
                ]
            );

            return response()->json(['status' => 'success', 'booking_id' => $booking->id]);
            
        } catch (\Exception $e) {
            Log::error('Airbnb webhook error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function booking(Request $request)
    {
        Log::info('Booking.com webhook received', ['payload' => $request->all()]);
        
        try {
            $data = $request->all();
            
            // Find property
            $propertyChannel = PropertyChannel::where('channel_property_id', $data['hotel_id'])
                ->whereHas('channel', function($query) {
                    $query->where('channel_id', 'BOO142');
                })
                ->first();

            if (!$propertyChannel) {
                throw new \Exception('Property not found');
            }

            $property = $propertyChannel->property;

            // Create or update booking
            $booking = Booking::updateOrCreate(
                [
                    'channel_partner_reference' => $data['reservation_id'],
                    'channel_id' => 'BOO142'
                ],
                [
                    'booking_number' => 'BOO-' . $data['reservation_id'],
                    'property_id' => $property->property_id,
                    'supplier_property_id' => $property->supplier_property_id,
                    'property_manager_code' => $property->property_manager_code,
                    'guest_first_name' => $data['guest']['first_name'],
                    'guest_surname' => $data['guest']['last_name'],
                    'guest_email' => $data['guest']['email'],
                    'check_in_date' => $data['checkin'],
                    'check_out_date' => $data['checkout'],
                    'total_amount' => $data['total_amount'],
                    'currency' => $data['currency'],
                    'status' => 'confirmed',
                    'api_response' => $data,
                    'sync_status' => 'synced',
                    'synced_at' => now(),
                ]
            );

            return response()->json(['status' => 'success', 'booking_id' => $booking->id]);
            
        } catch (\Exception $e) {
            Log::error('Booking.com webhook error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function nextpax(Request $request)
    {
        Log::info('NextPax webhook received', ['payload' => $request->all()]);
        
        try {
            $data = $request->all();
            
            // Find property
            $property = Property::where('channel_property_id', $data['propertyId'])->first();

            if (!$property) {
                throw new \Exception('Property not found');
            }

            // Create or update booking
            $booking = Booking::updateOrCreate(
                [
                    'booking_number' => $data['bookingNumber']
                ],
                [
                    'nextpax_booking_id' => $data['id'] ?? null,
                    'channel_partner_reference' => $data['channelPartnerReference'] ?? null,
                    'channel_id' => $data['channelId'],
                    'property_id' => $property->property_id,
                    'supplier_property_id' => $property->supplier_property_id,
                    'property_manager_code' => $property->property_manager_code,
                    'guest_first_name' => $data['guest']['firstName'],
                    'guest_surname' => $data['guest']['surname'],
                    'guest_email' => $data['guest']['email'],
                    'check_in_date' => $data['checkIn'],
                    'check_out_date' => $data['checkOut'],
                    'total_amount' => $data['totalAmount'],
                    'currency' => $data['currency'],
                    'status' => 'confirmed',
                    'api_response' => $data,
                    'sync_status' => 'synced',
                    'synced_at' => now(),
                ]
            );

            return response()->json(['status' => 'success', 'booking_id' => $booking->id]);
            
        } catch (\Exception $e) {
            Log::error('NextPax webhook error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}