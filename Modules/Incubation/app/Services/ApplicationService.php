<?php

declare(strict_types=1);

namespace Modules\Incubation\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Events\ApplicationAccepted;
use Modules\Incubation\Events\ApplicationRejected;
use Modules\Incubation\Events\ApplicationStatusChanged;
use Modules\Incubation\Events\ApplicationSubmitted;
use Modules\Incubation\Models\Application;
use Modules\Incubation\Models\Cohort;

/**
 * Service for managing applications in the incubation pipeline.
 */
class ApplicationService
{
    /**
     * Submit a new application.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitApplication(Cohort $cohort, array $data, ?UploadedFile $pitchDeck = null): Application
    {
        if (! $cohort->isAcceptingApplications()) {
            throw new \RuntimeException('This cohort is not accepting applications.');
        }

        return DB::transaction(function () use ($cohort, $data, $pitchDeck): Application {
            // Handle pitch deck upload
            $pitchDeckPath = null;
            if ($pitchDeck) {
                $pitchDeckPath = $this->uploadPitchDeck($pitchDeck);
            }

            $application = Application::create([
                'cohort_id' => $cohort->id,
                'startup_name' => $data['startup_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'founders_data' => $data['founders_data'],
                'problem_statement' => $data['problem_statement'],
                'solution' => $data['solution'],
                'industry' => $data['industry'] ?? null,
                'business_model' => $data['business_model'] ?? null,
                'stage' => $data['stage'] ?? null,
                'traction' => $data['traction'] ?? null,
                'funding_raised' => $data['funding_raised'] ?? null,
                'funding_currency' => $data['funding_currency'] ?? 'SDG',
                'pitch_deck_url' => $data['pitch_deck_url'] ?? null,
                'pitch_deck_path' => $pitchDeckPath,
                'video_pitch_url' => $data['video_pitch_url'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'why_apply' => $data['why_apply'] ?? null,
                'social_links' => $data['social_links'] ?? null,
                'additional_notes' => $data['additional_notes'] ?? null,
                'source' => $data['source'] ?? 'website',
                'referral_source' => $data['referral_source'] ?? null,
                'status' => ApplicationStatus::SUBMITTED,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            // Create initial status history
            $application->statusHistory()->create([
                'from_status' => null,
                'to_status' => ApplicationStatus::SUBMITTED->value,
                'changed_by_user_id' => $application->user_id,
                'notes' => 'Application submitted',
            ]);

            event(new ApplicationSubmitted($application));

            Log::info('Application submitted', [
                'application_id' => $application->id,
                'cohort_id' => $cohort->id,
                'startup_name' => $application->startup_name,
            ]);

            return $application;
        });
    }

    /**
     * Upload a pitch deck file.
     */
    public function uploadPitchDeck(UploadedFile $file): string
    {
        $filename = sprintf(
            'pitch-decks/%s-%s.%s',
            now()->format('Y/m'),
            uniqid(),
            $file->getClientOriginalExtension()
        );

        // Store in private disk
        Storage::disk('local')->put($filename, $file->get());

        return $filename;
    }

    /**
     * Move application to screening stage.
     */
    public function moveToScreening(Application $application, ?int $userId = null, ?string $notes = null): bool
    {
        return $this->transitionStatus(
            $application,
            ApplicationStatus::SCREENING,
            $userId,
            $notes
        );
    }

    /**
     * Schedule an interview for the application.
     */
    public function scheduleInterview(
        Application $application,
        \DateTimeInterface $scheduledAt,
        ?string $location = null,
        ?string $meetingLink = null,
        ?int $userId = null
    ): bool {
        if (! $application->canTransitionTo(ApplicationStatus::INTERVIEW_SCHEDULED)) {
            return false;
        }

        return DB::transaction(function () use ($application, $scheduledAt, $location, $meetingLink, $userId): bool {
            $application->update([
                'interview_scheduled_at' => $scheduledAt,
                'interview_location' => $location ?? 'Online',
                'interview_meeting_link' => $meetingLink,
            ]);

            return $this->transitionStatus(
                $application,
                ApplicationStatus::INTERVIEW_SCHEDULED,
                $userId,
                "Interview scheduled for {$scheduledAt->format('M j, Y H:i')}"
            );
        });
    }

    /**
     * Mark interview as completed.
     */
    public function completeInterview(Application $application, ?string $notes = null, ?int $userId = null): bool
    {
        if (! $application->canTransitionTo(ApplicationStatus::INTERVIEWED)) {
            return false;
        }

        return DB::transaction(function () use ($application, $notes, $userId): bool {
            if ($notes) {
                $application->update(['interview_notes' => $notes]);
            }

            return $this->transitionStatus(
                $application,
                ApplicationStatus::INTERVIEWED,
                $userId,
                $notes
            );
        });
    }

    /**
     * Accept an application.
     */
    public function accept(Application $application, ?int $userId = null, ?string $notes = null): bool
    {
        if (! $application->canTransitionTo(ApplicationStatus::ACCEPTED)) {
            return false;
        }

        // Check cohort capacity
        if (! $application->cohort->hasCapacity()) {
            throw new \RuntimeException('Cohort has reached maximum capacity.');
        }

        return DB::transaction(function () use ($application, $userId, $notes): bool {
            $result = $this->transitionStatus(
                $application,
                ApplicationStatus::ACCEPTED,
                $userId,
                $notes
            );

            if ($result) {
                $application->update([
                    'decision_at' => now(),
                    'decided_by_user_id' => $userId,
                ]);

                $application->cohort->incrementAcceptedCount();

                event(new ApplicationAccepted($application));

                Log::info('Application accepted', [
                    'application_id' => $application->id,
                    'cohort_id' => $application->cohort_id,
                ]);
            }

            return $result;
        });
    }

    /**
     * Reject an application.
     */
    public function reject(Application $application, ?int $userId = null, ?string $reason = null): bool
    {
        if (! $application->canTransitionTo(ApplicationStatus::REJECTED)) {
            return false;
        }

        return DB::transaction(function () use ($application, $userId, $reason): bool {
            $result = $this->transitionStatus(
                $application,
                ApplicationStatus::REJECTED,
                $userId,
                $reason
            );

            if ($result) {
                $application->update([
                    'decision_at' => now(),
                    'decided_by_user_id' => $userId,
                    'rejection_reason' => $reason,
                ]);

                event(new ApplicationRejected($application));

                Log::info('Application rejected', [
                    'application_id' => $application->id,
                    'reason' => $reason,
                ]);
            }

            return $result;
        });
    }

    /**
     * Update evaluation score.
     *
     * @param  array<string, int>  $scores
     */
    public function updateScore(Application $application, array $scores, ?string $notes = null): void
    {
        // Calculate average score
        $averageScore = count($scores) > 0
            ? array_sum($scores) / count($scores) * 10  // Convert to 0-100 scale
            : null;

        $application->update([
            'evaluation_scores' => $scores,
            'score' => $averageScore,
            'internal_notes' => $notes ?? $application->internal_notes,
        ]);

        Log::info('Application score updated', [
            'application_id' => $application->id,
            'scores' => $scores,
            'average' => $averageScore,
        ]);
    }

    /**
     * Transition application status with history tracking.
     */
    protected function transitionStatus(
        Application $application,
        ApplicationStatus $newStatus,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        if (! $application->canTransitionTo($newStatus)) {
            Log::warning('Invalid status transition attempted', [
                'application_id' => $application->id,
                'from' => $application->status->value,
                'to' => $newStatus->value,
            ]);

            return false;
        }

        $oldStatus = $application->status;

        $application->update([
            'previous_status' => $oldStatus->value,
            'status' => $newStatus,
        ]);

        $application->statusHistory()->create([
            'from_status' => $oldStatus->value,
            'to_status' => $newStatus->value,
            'changed_by_user_id' => $userId ?? auth()->id(),
            'notes' => $notes,
        ]);

        event(new ApplicationStatusChanged($application, $oldStatus, $newStatus));

        return true;
    }

    /**
     * Onboard an accepted application (create member record).
     *
     * @return array{member_id: int|null, subscription_id: int|null}
     */
    public function onboardApplication(Application $application): array
    {
        if ($application->status !== ApplicationStatus::ACCEPTED) {
            throw new \RuntimeException('Only accepted applications can be onboarded.');
        }

        if ($application->onboarded_member_id) {
            throw new \RuntimeException('Application has already been onboarded.');
        }

        // This will be handled by an event listener in the Membership module
        // Here we just mark it as ready for onboarding
        $application->update([
            'onboarded_at' => now(),
        ]);

        Log::info('Application ready for onboarding', [
            'application_id' => $application->id,
        ]);

        // Return placeholder - actual member creation happens via event
        return [
            'member_id' => null,
            'subscription_id' => null,
        ];
    }
}
