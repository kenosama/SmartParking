<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    /**
     * Displays all reservations
     */
    public function index()
    {
        return response()->json(
            Reservation::with(['user', 'parkingSpot.parking'])->get()
        );
    }

    /**
     * Creates one or more new reservations
     */
    public function store(Request $request)
    {
        // Validate request data: user, parking, time slots, spot identifiers, plates...
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'parking_id' => 'required|exists:parkings,id',
            'parking_spot_identifiers' => 'required|string', // comma-separated identifiers
            'reserved_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'in:active,cancelled_by_user,cancelled_by_owner,done',
            'license_plate' => 'required|string' // comma-separated plates
        ]);

        // Additional logic check to ensure start is before end (both date and time)
        $startDateTime = Carbon::parse($validated['reserved_date'] . ' ' . $validated['start_time']);
        $endDateTime = Carbon::parse(($validated['end_date'] ?? $validated['reserved_date']) . ' ' . $validated['end_time']);

        if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
            return response()->json(['error' => 'End time must be after start time.'], 422);
        }

        // Split identifiers by comma to support A1,A2,B1-B3,B5 etc.
        $rawIdentifiers = array_map('trim', explode(',', $validated['parking_spot_identifiers']));
        $identifiers = [];

        // Detect ranges like "A1-A5" and expand them into [A1, A2, ..., A5]
        foreach ($rawIdentifiers as $entry) {
            if (preg_match('/^([A-Z]+)(\d+)-([A-Z]+)?(\d+)$/i', $entry, $matches)) {
                $prefixStart = strtoupper($matches[1]);
                $startNum = (int)$matches[2];
                $prefixEnd = $matches[3] ? strtoupper($matches[3]) : $prefixStart;
                $endNum = (int)$matches[4];

                if ($prefixStart !== $prefixEnd) {
                    continue; // ignore cross-letter ranges
                }

                for ($i = $startNum; $i <= $endNum; $i++) {
                    $identifiers[] = $prefixStart . $i;
                }
            } else {
                $identifiers[] = strtoupper($entry);
            }
        }

        // Clean license plates (remove spaces/symbols and convert to uppercase)
        $plates = array_map(function ($p) {
            return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $p));
        }, explode(',', $validated['license_plate']));

        // Ensure each spot has a corresponding license plate
        if (count($identifiers) !== count($plates)) {
            return response()->json([
                'error' => 'Number of license plates must match number of parking spots.'
            ], 422);
        }

        // Get valid spots matching given identifiers for the selected parking (active only)
        $parkingSpots = \App\Models\ParkingSpot::where('parking_id', $validated['parking_id'])
            ->whereHas('parking', function ($query) {
                $query->where('is_active', true);
            })
            ->whereIn('identifier', $identifiers)
            ->get();

        if (count($parkingSpots) !== count($identifiers)) {
            return response()->json([
                'error' => 'One or more parking spot identifiers are invalid for the selected parking.'
            ], 422);
        }

        $reservations = [];

        foreach ($parkingSpots as $index => $spot) {
            // Determine if spot uses per-day-only reservation
            $perDayOnly = $spot->per_day_only;

            // Calculate start and end datetime based on per_day_only flag
            if ($perDayOnly) {
                $startDateTime = Carbon::parse($validated['reserved_date'])->startOfDay();
                $endDateTime = Carbon::parse($validated['end_date'] ?? $validated['reserved_date'])->endOfDay();
                $adjustment_message = 'Reservation times adjusted for daily-only spot.';
            } else {
                $startDateTime = Carbon::parse($validated['reserved_date'] . ' ' . $validated['start_time']);
                $endDateTime = Carbon::parse(($validated['end_date'] ?? $validated['reserved_date']) . ' ' . $validated['end_time']);
                $adjustment_message = null;
            }

            // Check for reservation conflicts for each requested spot using datetime logic
            $conflict = \App\Models\Reservation::where('parking_spot_id', $spot->id)
                ->where(function ($query) use ($startDateTime, $endDateTime) {
                    $query->where(function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('reserved_date', '<=', $endDateTime->toDateString())
                          ->where(function ($q2) use ($startDateTime, $endDateTime) {
                              $q2->where(function ($q3) use ($startDateTime, $endDateTime) {
                                  $q3->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') < ?", [$endDateTime])
                                     ->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') > ?", [$startDateTime]);
                              });
                          });
                    });
                })
                ->whereIn('status', ['active'])
                ->exists();

            if ($conflict) {
                return response()->json([
                    'error' => "Spot {$spot->identifier} is already reserved for the selected time."
                ], 409);
            }

            // Create reservation if no conflict
            $reservation = \App\Models\Reservation::create([
                'user_id' => $validated['user_id'],
                'parking_spot_id' => $spot->id,
                'reserved_date' => $validated['reserved_date'],
                'end_date' => $validated['end_date'] ?? null,
                'start_time' => $perDayOnly ? '00:00' : $validated['start_time'],
                'end_time' => $perDayOnly ? '23:59' : $validated['end_time'],
                'status' => $validated['status'] ?? 'active',
                'license_plate' => $plates[$index],
            ]);

            // Update spot availability
            $spot->is_available = false;
            $spot->save();

            $reservations[] = $reservation->load('user', 'parkingSpot.parking');
        }

        // Return all created reservations with user and parking relations loaded
        $response = response()->json($reservations, 201);
        if ($adjustment_message) {
            $response->setData(array_merge(
                ['message' => $adjustment_message],
                ['reservations' => $reservations]
            ));
        }
        return $response;
    }

    /**
     * Displays a specific reservation
     */
    public function show(Reservation $reservation)
    {
        return response()->json(
            $reservation->load('user', 'parkingSpot.parking')
        );
    }

    /**
     * Updates a reservation
     */
    public function update(Request $request, Reservation $reservation)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->is_admin && $currentUser->id !== $reservation->user_id) {
            return response()->json(['error' => 'Unauthorized to update this reservation.'], 403);
        }

        // New validation for multi-spot/plate update
        $validated = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'parking_id' => 'sometimes|required|exists:parkings,id',
            'parking_spot_identifiers' => 'sometimes|required|string',
            'reserved_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'status' => 'in:active,cancelled_by_user,cancelled_by_owner,done,manual_override',
            'license_plate' => 'sometimes|required|string',
        ]);

        // Use current reservation values as fallback for fields not provided
        $user_id = $validated['user_id'] ?? $reservation->user_id;
        $parking_id = $validated['parking_id'] ?? $reservation->parkingSpot->parking_id;
        $reserved_date = $validated['reserved_date'] ?? $reservation->reserved_date;
        $end_date = array_key_exists('end_date', $validated)
            ? $validated['end_date']
            : ($reservation->end_date ?? $reserved_date);
        $start_time = $validated['start_time'] ?? $reservation->start_time;
        $end_time = $validated['end_time'] ?? $reservation->end_time;
        $status = $validated['status'] ?? $reservation->status;
        $spot_identifiers_str = $validated['parking_spot_identifiers'] ?? $reservation->parkingSpot->identifier;
        $license_plate_str = $validated['license_plate'] ?? $reservation->license_plate;

        // Validate interval
        if ($reserved_date && $start_time && $end_time) {
            $startDateTime = \Carbon\Carbon::parse($reserved_date . ' ' . $start_time);
            $endDateTime = \Carbon\Carbon::parse(($end_date ?? $reserved_date) . ' ' . $end_time);
            if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
                return response()->json(['error' => 'End time must be after start time.'], 422);
            }
        }

        // Parse spot identifiers (expand ranges)
        $rawIdentifiers = array_map('trim', explode(',', $spot_identifiers_str));
        $identifiers = [];
        foreach ($rawIdentifiers as $entry) {
            if (preg_match('/^([A-Z]+)(\d+)-([A-Z]+)?(\d+)$/i', $entry, $matches)) {
                $prefixStart = strtoupper($matches[1]);
                $startNum = (int)$matches[2];
                $prefixEnd = $matches[3] ? strtoupper($matches[3]) : $prefixStart;
                $endNum = (int)$matches[4];
                if ($prefixStart !== $prefixEnd) {
                    continue;
                }
                for ($i = $startNum; $i <= $endNum; $i++) {
                    $identifiers[] = $prefixStart . $i;
                }
            } else {
                $identifiers[] = strtoupper($entry);
            }
        }

        // Normalize and split plates
        $plates = array_map(function ($p) {
            return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $p));
        }, explode(',', $license_plate_str));

        // Ensure each spot has a corresponding license plate
        if (count($identifiers) !== count($plates)) {
            return response()->json([
                'error' => 'Number of license plates must match number of parking spots.'
            ], 422);
        }

        // Retrieve all matching spots (active only, for parking_id)
        $parkingSpots = \App\Models\ParkingSpot::where('parking_id', $parking_id)
            ->whereHas('parking', function ($query) {
                $query->where('is_active', true);
            })
            ->whereIn('identifier', $identifiers)
            ->get();

        if (count($parkingSpots) !== count($identifiers)) {
            return response()->json([
                'error' => 'One or more parking spot identifiers are invalid for the selected parking.'
            ], 422);
        }

        // Map identifiers to ParkingSpot models
        $spotMap = [];
        foreach ($parkingSpots as $spot) {
            $spotMap[$spot->identifier] = $spot;
        }

        // Find which index in identifiers corresponds to this reservation
        $current_identifier = $reservation->parkingSpot->identifier;
        $update_index = null;
        foreach ($identifiers as $idx => $identifier) {
            // If updating to a different spot, allow; else, match current
            if ($identifier === $current_identifier) {
                $update_index = $idx;
                break;
            }
        }
        // If not found, just use first (if changing spot)
        if ($update_index === null) {
            $update_index = 0;
        }
        $target_identifier = $identifiers[$update_index];
        $target_plate = $plates[$update_index];
        $target_spot = $spotMap[$target_identifier] ?? null;
        if (!$target_spot) {
            return response()->json([
                'error' => 'Target parking spot not found or not available.'
            ], 422);
        }

        // Check for reservation conflict for this spot and timing
        $perDayOnly = $target_spot->per_day_only;
        if ($perDayOnly) {
            $startDateTime = \Carbon\Carbon::parse($reserved_date)->startOfDay();
            $endDateTime = \Carbon\Carbon::parse($end_date ?? $reserved_date)->endOfDay();
            $new_start_time = '00:00';
            $new_end_time = '23:59';
            $adjustment_message = 'Reservation times adjusted for daily-only spot.';
        } else {
            $startDateTime = \Carbon\Carbon::parse($reserved_date . ' ' . $start_time);
            $endDateTime = \Carbon\Carbon::parse(($end_date ?? $reserved_date) . ' ' . $end_time);
            $new_start_time = $start_time;
            $new_end_time = $end_time;
            $adjustment_message = null;
        }
        $conflict = \App\Models\Reservation::where('parking_spot_id', $target_spot->id)
            ->where('id', '<>', $reservation->id)
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->where(function ($q) use ($startDateTime, $endDateTime) {
                    $q->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') < ?", [$endDateTime])
                      ->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') > ?", [$startDateTime]);
                });
            })
            ->whereIn('status', ['active'])
            ->exists();
        if ($conflict) {
            return response()->json([
                'error' => "Spot {$target_spot->identifier} is already reserved for the selected time."
            ], 409);
        }

        // Update reservation
        $reservation->user_id = $user_id;
        $reservation->parking_spot_id = $target_spot->id;
        $reservation->reserved_date = $reserved_date;
        $reservation->end_date = $end_date ?? null;
        $reservation->start_time = $new_start_time;
        $reservation->end_time = $new_end_time;
        $reservation->status = $status;
        $reservation->license_plate = $target_plate;
        $reservation->save();

        // Mark spot as unavailable
        $target_spot->is_available = false;
        $target_spot->save();

        $responseData = $reservation->load('user', 'parkingSpot.parking');
        if ($adjustment_message) {
            return response()->json([
                'message' => $adjustment_message,
                'reservation' => $responseData
            ]);
        }
        return response()->json($responseData);
    }

    /**
     * Soft cancels a reservation by updating its status (logical soft delete)
     */
    public function destroy(Reservation $reservation)
    {
        // If the reservation is already cancelled or completed, no update needed
        if (in_array($reservation->status, ['cancelled_by_user', 'cancelled_by_owner', 'done'])) {
            return response()->json([
                'message' => 'Reservation is already finalized or cancelled.'
            ], 200);
        }

        // Get the owner ID of the parking associated with this reservation
        $spotOwnerId = $reservation->parkingSpot->parking->user_id ?? null;

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Prevent an admin from cancelling if not authorized (for info only)
        if ($currentUser->is_admin && $currentUser->id !== $reservation->user_id && $currentUser->id !== ($reservation->parkingSpot->parking->user_id ?? null)) {
            // Admins can cancel anytime, so this check is purely informative if we want to use it later
            // return response()->json(['error' => 'Admin override not authorized'], 403);
        }

        // Admin can do anything, skip checks
        if ($currentUser->is_admin) {
            $reservation->status = 'cancelled_by_admin';
            $reservation->save();

            return response()->json([
                'message' => 'Reservation cancelled by admin.',
                'status' => $reservation->status
            ], 200);
        }

        if ($currentUser->id === $spotOwnerId) {
            $now = now();
            $reservationDateTime = Carbon::parse($reservation->reserved_date . ' ' . $reservation->start_time);

            if ($reservationDateTime->diffInHours($now) <= 48) {
                $reservation->status = 'cancelled_by_owner';
                $reservation->save();

                return response()->json([
                    'message' => 'Reservation cancelled by parking owner within 48h before start.',
                    'status' => $reservation->status
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Parking owner can only cancel within 48h before the reservation start time.'
                ], 403);
            }
        }

        // Determine who cancels: user, admin or other
        if ($currentUser->id === $reservation->user_id) {
            $now = now();
            $reservationDateTime = Carbon::parse($reservation->reserved_date . ' ' . $reservation->start_time);

            // User can cancel only if at least 24h before reservation start
            if ($reservationDateTime->diffInHours($now) >= 24) {
                $reservation->status = 'cancelled_by_user';
            } else {
                return response()->json(['error' => 'You can only cancel your reservation at least 24h in advance.'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized to cancel this reservation.'], 403);
        }

        $reservation->save();

        return response()->json(['message' => 'Reservation cancelled successfully.', 'status' => $reservation->status], 200);
    }

    /**
     * Ends a reservation early by user or admin, marks reservation done and spot available
     */
    public function endReservation(Request $request, Reservation $reservation)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->is_admin && $currentUser->id !== $reservation->user_id) {
            return response()->json(['error' => 'Unauthorized to end this reservation early.'], 403);
        }

        if ($reservation->status !== 'active') {
            return response()->json(['error' => 'Only active reservations can be ended early.'], 400);
        }

        $reservation->status = 'done';
        $reservation->save();

        // Mark spot as available again
        $spot = $reservation->parkingSpot;
        $spot->is_available = true;
        $spot->save();

        return response()->json(['message' => 'Reservation ended successfully.', 'status' => $reservation->status], 200);
    }

    /**
     * Allows an admin to manually occupy a parking spot without assigning a user (temporary block)
     */
    public function manualOccupy(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->is_admin) {
            return response()->json(['error' => 'Only admins can manually occupy spots.'], 403);
        }

        $validated = $request->validate([
            'parking_spot_id' => 'required|exists:parking_spots,id',
            'reserved_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $spot = \App\Models\ParkingSpot::find($validated['parking_spot_id']);

        // Check if spot is already occupied or reserved for the given time
        $startDateTime = Carbon::parse($validated['reserved_date'] . ' ' . $validated['start_time']);
        $endDateTime = Carbon::parse(($validated['end_date'] ?? $validated['reserved_date']) . ' ' . $validated['end_time']);

        $conflict = \App\Models\Reservation::where('parking_spot_id', $spot->id)
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->where(function ($q) use ($startDateTime, $endDateTime) {
                    $q->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') < ?", [$endDateTime])
                      ->whereRaw("STR_TO_DATE(CONCAT(reserved_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') > ?", [$startDateTime]);
                });
            })
            ->whereIn('status', ['active'])
            ->exists();

        if ($conflict) {
            return response()->json([
                'error' => "Spot {$spot->identifier} is already reserved or occupied for the selected time."
            ], 409);
        }

        // Create a manual reservation without user and license plate, status 'manual_override'
        $reservation = \App\Models\Reservation::create([
            'user_id' => null,
            'parking_spot_id' => $spot->id,
            'reserved_date' => $validated['reserved_date'],
            'end_date' => $validated['end_date'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'manual_override',
            'license_plate' => null,
        ]);

        // Mark spot as unavailable
        $spot->is_available = false;
        $spot->save();

        return response()->json([
            'message' => 'Spot manually occupied successfully.',
            'reservation' => $reservation->load('parkingSpot.parking')
        ], 201);
    }
}
