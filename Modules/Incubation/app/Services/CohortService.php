<?php

declare(strict_types=1);

namespace Modules\Incubation\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Incubation\Enums\CohortStatus;
use Modules\Incubation\Events\CohortOpened;
use Modules\Incubation\Events\CohortStatusChanged;
use Modules\Incubation\Models\Cohort;

/**
 * Service for managing cohorts.
 */
class CohortService
{
    /**
     * Create a new cohort.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCohort(array $data): Cohort
    {
        return DB::transaction(function () use ($data): Cohort {
            $cohort = Cohort::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'application_start_date' => $data['application_start_date'] ?? null,
                'application_end_date' => $data['application_end_date'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'capacity' => $data['capacity'] ?? 10,
                'status' => CohortStatus::DRAFT,
                'eligibility_criteria' => $data['eligibility_criteria'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'program_manager' => $data['program_manager'] ?? null,
                'program_manager_user_id' => $data['program_manager_user_id'] ?? null,
                'cover_image' => $data['cover_image'] ?? null,
            ]);

            // Create default milestones if provided
            if (isset($data['milestones']) && is_array($data['milestones'])) {
                $this->createMilestones($cohort, $data['milestones']);
            }

            Log::info('Cohort created', ['cohort_id' => $cohort->id, 'name' => $cohort->name]);

            return $cohort;
        });
    }

    /**
     * Create milestones for a cohort.
     *
     * @param  array<array<string, mixed>>  $milestones
     */
    public function createMilestones(Cohort $cohort, array $milestones): void
    {
        foreach ($milestones as $index => $milestone) {
            $cohort->milestones()->create([
                'name' => $milestone['name'],
                'description' => $milestone['description'] ?? null,
                'category' => $milestone['category'] ?? null,
                'target_date' => $milestone['target_date'] ?? null,
                'week_number' => $milestone['week_number'] ?? null,
                'sort_order' => $milestone['sort_order'] ?? $index,
                'requirements' => $milestone['requirements'] ?? null,
                'is_required' => $milestone['is_required'] ?? false,
            ]);
        }
    }

    /**
     * Open a cohort for applications.
     */
    public function openForApplications(Cohort $cohort): bool
    {
        if ($cohort->status !== CohortStatus::DRAFT) {
            throw new \RuntimeException('Only draft cohorts can be opened for applications.');
        }

        return DB::transaction(function () use ($cohort): bool {
            $oldStatus = $cohort->status;

            $cohort->update([
                'status' => CohortStatus::OPEN_FOR_APPLICATIONS,
            ]);

            event(new CohortOpened($cohort));
            event(new CohortStatusChanged($cohort, $oldStatus, CohortStatus::OPEN_FOR_APPLICATIONS));

            Log::info('Cohort opened for applications', ['cohort_id' => $cohort->id]);

            return true;
        });
    }

    /**
     * Close applications and move to reviewing stage.
     */
    public function closeApplications(Cohort $cohort): bool
    {
        if ($cohort->status !== CohortStatus::OPEN_FOR_APPLICATIONS) {
            throw new \RuntimeException('Only open cohorts can be closed for applications.');
        }

        return $this->transitionStatus($cohort, CohortStatus::REVIEWING);
    }

    /**
     * Activate a cohort (program starts).
     */
    public function activate(Cohort $cohort): bool
    {
        if ($cohort->status !== CohortStatus::REVIEWING) {
            throw new \RuntimeException('Only reviewing cohorts can be activated.');
        }

        return $this->transitionStatus($cohort, CohortStatus::ACTIVE);
    }

    /**
     * Complete a cohort.
     */
    public function complete(Cohort $cohort): bool
    {
        if ($cohort->status !== CohortStatus::ACTIVE) {
            throw new \RuntimeException('Only active cohorts can be completed.');
        }

        return $this->transitionStatus($cohort, CohortStatus::COMPLETED);
    }

    /**
     * Archive a cohort.
     */
    public function archive(Cohort $cohort): bool
    {
        if ($cohort->status !== CohortStatus::COMPLETED) {
            throw new \RuntimeException('Only completed cohorts can be archived.');
        }

        return $this->transitionStatus($cohort, CohortStatus::ARCHIVED);
    }

    /**
     * Transition cohort status.
     */
    protected function transitionStatus(Cohort $cohort, CohortStatus $newStatus): bool
    {
        if (! $cohort->status->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $cohort->status;

        $cohort->update(['status' => $newStatus]);

        event(new CohortStatusChanged($cohort, $oldStatus, $newStatus));

        Log::info('Cohort status changed', [
            'cohort_id' => $cohort->id,
            'from' => $oldStatus->value,
            'to' => $newStatus->value,
        ]);

        return true;
    }

    /**
     * Duplicate a cohort (for creating new cycles).
     */
    public function duplicateCohort(Cohort $sourceCohort, string $newName, array $overrides = []): Cohort
    {
        return DB::transaction(function () use ($sourceCohort, $newName, $overrides): Cohort {
            $data = [
                'name' => $newName,
                'slug' => Str::slug($newName),
                'description' => $sourceCohort->description,
                'capacity' => $sourceCohort->capacity,
                'eligibility_criteria' => $sourceCohort->eligibility_criteria,
                'benefits' => $sourceCohort->benefits,
                'program_manager' => $sourceCohort->program_manager,
                'program_manager_user_id' => $sourceCohort->program_manager_user_id,
                ...$overrides,
            ];

            $newCohort = $this->createCohort($data);

            // Duplicate milestones
            foreach ($sourceCohort->milestones as $milestone) {
                $newCohort->milestones()->create([
                    'name' => $milestone->name,
                    'description' => $milestone->description,
                    'category' => $milestone->category,
                    'week_number' => $milestone->week_number,
                    'sort_order' => $milestone->sort_order,
                    'requirements' => $milestone->requirements,
                    'is_required' => $milestone->is_required,
                ]);
            }

            Log::info('Cohort duplicated', [
                'source_id' => $sourceCohort->id,
                'new_id' => $newCohort->id,
            ]);

            return $newCohort;
        });
    }

    /**
     * Get cohort statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(Cohort $cohort): array
    {
        $applications = $cohort->applications();

        return [
            'total_applications' => $applications->count(),
            'submitted' => $applications->clone()->where('status', 'submitted')->count(),
            'screening' => $applications->clone()->where('status', 'screening')->count(),
            'interview_scheduled' => $applications->clone()->where('status', 'interview_scheduled')->count(),
            'interviewed' => $applications->clone()->where('status', 'interviewed')->count(),
            'accepted' => $applications->clone()->where('status', 'accepted')->count(),
            'rejected' => $applications->clone()->where('status', 'rejected')->count(),
            'withdrawn' => $applications->clone()->where('status', 'withdrawn')->count(),
            'capacity' => $cohort->capacity,
            'available_spots' => $cohort->available_spots,
            'acceptance_rate' => $applications->count() > 0
                ? round(($cohort->accepted_count / $applications->count()) * 100, 1)
                : 0,
        ];
    }
}
