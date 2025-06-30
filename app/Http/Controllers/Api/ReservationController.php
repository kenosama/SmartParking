<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ParkingSpot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReservationController extends Controller
{

    protected function getValidationRules(): array
    {
        return [
            'user_id' => 'sometimes|exists:users,id',
            'parking_id' => 'required|exists:parkings,id',
            'parking_spot_identifiers' => 'required|string',
            'reserved_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:reserved_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'license_plate' => 'required|string',
        ];
    }

    protected function expandSpotIdentifiers(string $identifiers): array
    {
        return array_map('trim', explode(',', $identifiers));
    }

    protected function normalizeLicensePlates(string $plates): array
    {
        return array_map(fn($p) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $p)), explode(',', $plates));
    }

    protected function explodeDateRange(Carbon $start, Carbon $end): array
    {
        $segments = [];
        $current = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($current->lte($last)) {
            $segStart = $current->eq($start->copy()->startOfDay()) ? $start->copy() : $current->copy()->setTime(0, 0);
            $segEnd = $current->eq($end->copy()->startOfDay()) ? $end->copy() : $current->copy()->setTime(23, 59);

            if ($segEnd->gt($segStart)) {
                $segments[] = ['start' => $segStart, 'end' => $segEnd];
            }

            $current->addDay();
        }

        return $segments;
    }

    protected function calculateCostAndDuration(ParkingSpot $spot, Carbon $start, Carbon $end): array
    {
        $duration = $start->diffInMinutes($end);

        if ($spot->per_day_only) {
            $days = $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1;
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round($days * $spot->price_per_day, 2),
            ];
        }

        if ($duration < 360) {
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round(ceil($duration / 60) * $spot->price_per_hour, 2),
            ];
        }

        if ($duration == 360) {
            $hourlyCost = ceil($duration / 60) * $spot->price_per_hour;
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round(min($hourlyCost, $spot->price_per_day), 2),
            ];
        }

        if ($duration > 360) {
            $hourlyCost = ceil($duration / 60) * $spot->price_per_hour;
            return [
                'duration_minutes' => $duration,
                'estimated_cost' => round(min($hourlyCost, $spot->price_per_day), 2),
            ];
        }

        return [
            'duration_minutes' => $duration,
            'estimated_cost' => round($spot->price_per_day, 2),
        ];
    }

    protected function formatReservationResponse(array $reservations, array $summary, string $message)
    {
        return response()->json([
            'message' => $message,
            'reservation ids' => collect($reservations)->pluck('id'),
            'reservation made by' => [
                'name' => $reservations[0]->user->full_name ?? ($reservations[0]->user->first_name . ' ' . $reservations[0]->user->last_name),
                'email' => $reservations[0]->user->email,
            ],
            'summary' => [
                'parking' => [
                    'id' => $reservations[0]->parkingSpot->parking->id,
                    'name' => $reservations[0]->parkingSpot->parking->name,
                    'zip_code' => $reservations[0]->parkingSpot->parking->zip_code,
                    'city' => $reservations[0]->parkingSpot->parking->city,
                ],
                'number of booked spots' => count($reservations),
                'spots' => collect($reservations)->map(function ($r, $i) use ($summary) {
                    return [
                        'id' => $r->parkingSpot->id,
                        'identifier' => $r->parkingSpot->identifier,
                        'per_day_only' => $r->parkingSpot->per_day_only,
                        'price_per_day' => $r->parkingSpot->price_per_day,
                        'price_per_hour' => $r->parkingSpot->price_per_hour,
                        'allow_electric_charge' => $r->parkingSpot->allow_electric_charge,
                        'total_cost_for_this_spot' => $summary['spot_costs'][$i] ?? 'N/A',
                    ];
                }),
                'date' => $summary['date'],
                'time' => $summary['time'],
                'duration_minutes' => $summary['duration_minutes'],
                'license_plate' => $summary['license_plates'],
                'estimated_cost' => $summary['estimated_cost'],
                'status' => $summary['status'],
            ]
        ], 201);
    }
    protected function generateReservationSlots(Carbon $startDate, Carbon $endDate, string $startTime, string $endTime): array
    {
        $slots = [];

        $current = $startDate->copy();
        $end = $endDate->copy();

        while ($current->lte($end)) {
            if ($startTime < $endTime) {
                // Journée simple (ex: 10h → 17h)
                $start = Carbon::parse($current->toDateString() . ' ' . $startTime);
                $endSlot = Carbon::parse($current->toDateString() . ' ' . $endTime);
            } else {
                // Overnight (ex: 23h → 06h le lendemain)
                $start = Carbon::parse($current->toDateString() . ' ' . $startTime);
                $endSlot = Carbon::parse($current->copy()->addDay()->toDateString() . ' ' . $endTime);
            }

            $slots[] = ['start' => $start, 'end' => $endSlot];
            $current->addDay();
        }

        return $slots;
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());

        // 🔐 Determine the user
        $authUser = Auth::user();
        $targetUserId = $validated['user_id'] ?? $authUser->id;

        if (isset($validated['user_id']) && $validated['user_id'] != $authUser->id && !$authUser->is_admin) {
            return response()->json(['error' => 'Unauthorized to create reservation for another user'], 403);
        }

        // 🧹 Normalize inputs
        $spotIdentifiers = $this->expandSpotIdentifiers($validated['parking_spot_identifiers']);
        $plates = $this->normalizeLicensePlates($validated['license_plate']);

        if (count($spotIdentifiers) !== count($plates)) {
            return response()->json(['error' => 'Number of license plates must match number of parking spots.'], 422);
        }

        // 📦 Fetch parking spots
        $spots = ParkingSpot::where('parking_id', $validated['parking_id'])
            ->whereIn('identifier', $spotIdentifiers)
            ->get();

        if (count($spots) !== count($spotIdentifiers)) {
            return response()->json(['error' => 'Some parking spots are invalid or not available.'], 422);
        }

        // 📆 Handle dates
        $startDate = Carbon::parse($validated['reserved_date']);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : $startDate;

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        if ($endDate->lessThan($startDate)) {
            return response()->json(['error' => 'End date must be after or equal to reserved date'], 422);
        }

        $slots = $this->generateReservationSlots($startDate, $endDate, $startTime, $endTime);

        $groupToken = Str::uuid()->toString();
        $allReservations = [];
        $spotCosts = [];
        $totalDuration = 0;
        $totalCost = 0;

        foreach ($spots as $index => $spot) {
            $plate = $plates[$index];
            foreach ($slots as $slot) {
                $start = $slot['start'];
                $end = $slot['end'];

                // 🔁 Check for overlaps
                $hasConflict = Reservation::where('parking_spot_id', $spot->id)
                    ->where(function ($q) use ($start, $end) {
                        $q->where(function ($sub) use ($start, $end) {
                            $sub->where('start_datetime', '<', $end)
                                ->where('end_datetime', '>', $start);
                        });
                    })
                    ->whereIn('status', ['active', 'manual_override'])
                    ->exists();

                if ($hasConflict) {
                    return response()->json(['error' => 'Spot ' . $spot->identifier . ' is already booked during ' . $start->format('Y-m-d H:i') . ' to ' . $end->format('Y-m-d H:i')], 422);
                }

                // 💰 Cost calculation
                $costData = $this->calculateCostAndDuration($spot, $start, $end);
                $spotCosts[] = $costData['estimated_cost'];
                $totalDuration += $costData['duration_minutes'];
                $totalCost += $costData['estimated_cost'];

                $reservation = Reservation::create([
                    'user_id' => $targetUserId,
                    'parking_id' => $validated['parking_id'],
                    'parking_spot_id' => $spot->id,
                    'license_plate' => $plate,
                    'start_datetime' => $start,
                    'end_datetime' => $end,
                    'status' => 'active',
                    'group_token' => $groupToken,
                ]);

                $allReservations[] = $reservation;
            }
        }

        $summary = [
            'date' => $startDate->toDateString() . ($endDate->ne($startDate) ? ' → ' . $endDate->toDateString() : ''),
            'time' => $startTime . ' → ' . $endTime,
            'duration_minutes' => $totalDuration,
            'estimated_cost' => round($totalCost, 2),
            'status' => 'active',
            'license_plates' => $plates,
            'spot_costs' => $spotCosts,
        ];

        return $this->formatReservationResponse($allReservations, $summary, 'Reservation successful.');
}
}