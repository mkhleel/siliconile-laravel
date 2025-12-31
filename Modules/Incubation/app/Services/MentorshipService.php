<?php

declare(strict_types=1);

namespace Modules\Incubation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Incubation\Enums\SessionStatus;
use Modules\Incubation\Events\MentorshipSessionBooked;
use Modules\Incubation\Events\MentorshipSessionCancelled;
use Modules\Incubation\Events\MentorshipSessionCompleted;
use Modules\Incubation\Models\Application;
use Modules\Incubation\Models\Mentor;
use Modules\Incubation\Models\MentorshipSession;

/**
 * Service for managing mentorship sessions.
 */
class MentorshipService
{
    /**
     * Book a mentorship session.
     *
     * @param  array<string, mixed>  $data
     */
    public function bookSession(Mentor $mentor, Application $application, array $data): MentorshipSession
    {
        // Validate mentor availability
        if (! $mentor->is_active) {
            throw new \RuntimeException('This mentor is not currently available.');
        }

        if (! $mentor->canAcceptMoreSessions()) {
            throw new \RuntimeException('This mentor has reached their weekly session limit.');
        }

        // Check for scheduling conflicts
        $scheduledAt = $data['scheduled_at'];
        $durationMinutes = $data['duration_minutes'] ?? 60;

        if ($this->hasSchedulingConflict($mentor, $scheduledAt, $durationMinutes)) {
            throw new \RuntimeException('The mentor has a scheduling conflict at this time.');
        }

        return DB::transaction(function () use ($mentor, $application, $data, $durationMinutes): MentorshipSession {
            $session = MentorshipSession::create([
                'mentor_id' => $mentor->id,
                'application_id' => $application->id,
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'one_on_one',
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $durationMinutes,
                'location_type' => $data['location_type'] ?? 'online',
                'location' => $data['location'] ?? null,
                'meeting_link' => $data['meeting_link'] ?? null,
                'status' => SessionStatus::PENDING,
                'booked_by_user_id' => auth()->id(),
            ]);

            event(new MentorshipSessionBooked($session));

            Log::info('Mentorship session booked', [
                'session_id' => $session->id,
                'mentor_id' => $mentor->id,
                'application_id' => $application->id,
            ]);

            return $session;
        });
    }

    /**
     * Check if there's a scheduling conflict.
     */
    public function hasSchedulingConflict(
        Mentor $mentor,
        \DateTimeInterface $scheduledAt,
        int $durationMinutes
    ): bool {
        $endTime = (clone $scheduledAt)->modify("+{$durationMinutes} minutes");

        return MentorshipSession::where('mentor_id', $mentor->id)
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::NO_SHOW->value])
            ->where(function ($query) use ($scheduledAt, $endTime) {
                $query->whereBetween('scheduled_at', [$scheduledAt, $endTime])
                    ->orWhereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) BETWEEN ? AND ?', [
                        $scheduledAt->format('Y-m-d H:i:s'),
                        $endTime->format('Y-m-d H:i:s'),
                    ]);
            })
            ->exists();
    }

    /**
     * Confirm a session.
     */
    public function confirmSession(MentorshipSession $session): void
    {
        if ($session->status !== SessionStatus::PENDING) {
            throw new \RuntimeException('Only pending sessions can be confirmed.');
        }

        $session->update([
            'status' => SessionStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        Log::info('Mentorship session confirmed', ['session_id' => $session->id]);
    }

    /**
     * Start a session.
     */
    public function startSession(MentorshipSession $session): void
    {
        if (! in_array($session->status, [SessionStatus::PENDING, SessionStatus::CONFIRMED])) {
            throw new \RuntimeException('Session cannot be started in its current state.');
        }

        $session->update([
            'status' => SessionStatus::IN_PROGRESS,
        ]);

        Log::info('Mentorship session started', ['session_id' => $session->id]);
    }

    /**
     * Complete a session.
     *
     * @param  array<string, mixed>|null  $actionItems
     */
    public function completeSession(
        MentorshipSession $session,
        ?string $summary = null,
        ?array $actionItems = null
    ): void {
        if ($session->status->isTerminal()) {
            throw new \RuntimeException('Session has already ended.');
        }

        DB::transaction(function () use ($session, $summary, $actionItems): void {
            $session->update([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => now(),
                'summary' => $summary,
                'action_items' => $actionItems,
            ]);

            // Update mentor statistics
            $session->mentor->updateStatistics();

            event(new MentorshipSessionCompleted($session));

            Log::info('Mentorship session completed', ['session_id' => $session->id]);
        });
    }

    /**
     * Cancel a session.
     */
    public function cancelSession(
        MentorshipSession $session,
        ?string $reason = null,
        ?int $cancelledByUserId = null
    ): void {
        if (! $session->canCancel()) {
            throw new \RuntimeException('Session cannot be cancelled in its current state.');
        }

        DB::transaction(function () use ($session, $reason, $cancelledByUserId): void {
            $session->update([
                'status' => SessionStatus::CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_by_user_id' => $cancelledByUserId ?? auth()->id(),
            ]);

            event(new MentorshipSessionCancelled($session));

            Log::info('Mentorship session cancelled', [
                'session_id' => $session->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Mark a session as no-show.
     */
    public function markNoShow(MentorshipSession $session): void
    {
        if ($session->status->isTerminal()) {
            throw new \RuntimeException('Session has already ended.');
        }

        $session->update([
            'status' => SessionStatus::NO_SHOW,
        ]);

        Log::info('Mentorship session marked as no-show', ['session_id' => $session->id]);
    }

    /**
     * Add feedback to a session.
     */
    public function addFeedback(
        MentorshipSession $session,
        string $role, // 'startup' or 'mentor'
        int $rating,
        ?string $feedback = null
    ): void {
        if ($session->status !== SessionStatus::COMPLETED) {
            throw new \RuntimeException('Feedback can only be added to completed sessions.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5.');
        }

        $data = $role === 'startup'
            ? ['startup_rating' => $rating, 'startup_feedback' => $feedback]
            : ['mentor_rating' => $rating, 'mentor_feedback' => $feedback];

        $session->update($data);

        // Update mentor statistics if startup provided rating
        if ($role === 'startup') {
            $session->mentor->updateStatistics();
        }

        Log::info('Feedback added to session', [
            'session_id' => $session->id,
            'role' => $role,
            'rating' => $rating,
        ]);
    }

    /**
     * Get available time slots for a mentor on a given date.
     *
     * @return array<string>
     */
    public function getAvailableSlots(Mentor $mentor, \DateTimeInterface $date): array
    {
        $dayName = strtolower($date->format('l'));
        $availability = $mentor->availability[$dayName] ?? [];

        if (empty($availability)) {
            return [];
        }

        // Get existing bookings for the date
        $existingBookings = MentorshipSession::where('mentor_id', $mentor->id)
            ->whereDate('scheduled_at', $date->format('Y-m-d'))
            ->whereNotIn('status', [SessionStatus::CANCELLED->value, SessionStatus::NO_SHOW->value])
            ->get(['scheduled_at', 'duration_minutes']);

        $availableSlots = [];

        foreach ($availability as $timeRange) {
            [$start, $end] = explode('-', $timeRange);

            $currentTime = \Carbon\Carbon::parse($date->format('Y-m-d').' '.$start);
            $endTime = \Carbon\Carbon::parse($date->format('Y-m-d').' '.$end);

            while ($currentTime->copy()->addHour() <= $endTime) {
                $slotStart = $currentTime->copy();
                $slotEnd = $currentTime->copy()->addHour();

                // Check if slot conflicts with existing bookings
                $hasConflict = $existingBookings->contains(function ($booking) use ($slotStart, $slotEnd) {
                    $bookingStart = $booking->scheduled_at;
                    $bookingEnd = $booking->scheduled_at->copy()->addMinutes($booking->duration_minutes);

                    return $slotStart < $bookingEnd && $slotEnd > $bookingStart;
                });

                if (! $hasConflict && $slotStart > now()) {
                    $availableSlots[] = $slotStart->format('H:i');
                }

                $currentTime->addHour();
            }
        }

        return $availableSlots;
    }
}
