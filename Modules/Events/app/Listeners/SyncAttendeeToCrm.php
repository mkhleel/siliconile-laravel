<?php

declare(strict_types=1);

namespace Modules\Events\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Events\Events\BookingCreated;

/**
 * SyncAttendeeToCrm Listener
 *
 * Syncs guest attendees to CRM Leads when they register for events.
 */
class SyncAttendeeToCrm implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        foreach ($event->attendees as $attendee) {
            // Only sync guests (non-registered users)
            if ($attendee->user_id !== null) {
                continue;
            }

            if (! $attendee->guest_email) {
                continue;
            }

            $this->syncToCrm($attendee);
        }
    }

    /**
     * Sync attendee to CRM as a lead.
     */
    private function syncToCrm($attendee): void
    {
        // Check if CRM module exists and Lead model is available
        if (! class_exists('Modules\\Crm\\Models\\Lead')) {
            Log::info('CRM module not available, skipping sync', [
                'attendee_id' => $attendee->id,
            ]);

            return;
        }

        try {
            $leadClass = 'Modules\\Crm\\Models\\Lead';

            // Check if lead already exists
            $existingLead = $leadClass::where('email', $attendee->guest_email)->first();

            if ($existingLead) {
                // Update attendee with CRM lead ID
                $attendee->update(['crm_lead_id' => $existingLead->id]);

                // Optionally add a note about the event registration
                if (method_exists($existingLead, 'addNote')) {
                    $existingLead->addNote(
                        __('Registered for event: :event', [
                            'event' => $attendee->event->title,
                        ])
                    );
                }

                return;
            }

            // Create new lead
            $lead = $leadClass::create([
                'name' => $attendee->guest_name,
                'email' => $attendee->guest_email,
                'phone' => $attendee->guest_phone,
                'company' => $attendee->company_name,
                'job_title' => $attendee->job_title,
                'source' => 'event_registration',
                'status' => 'new',
                'metadata' => [
                    'first_event' => $attendee->event->title,
                    'first_event_id' => $attendee->event_id,
                ],
            ]);

            // Update attendee with CRM lead ID
            $attendee->update(['crm_lead_id' => $lead->id]);

            Log::info('Guest synced to CRM', [
                'attendee_id' => $attendee->id,
                'lead_id' => $lead->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync attendee to CRM', [
                'attendee_id' => $attendee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
