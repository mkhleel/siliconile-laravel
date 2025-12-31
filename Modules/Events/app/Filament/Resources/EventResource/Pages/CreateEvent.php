<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Events\Filament\Resources\EventResource;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default currency for ticket types based on event
        if (isset($data['ticketTypes'])) {
            foreach ($data['ticketTypes'] as &$ticketType) {
                $ticketType['currency'] = $data['currency'] ?? 'EGP';
                $ticketType['is_free'] = ($ticketType['price'] ?? 0) == 0;
            }
        }

        return $data;
    }
}
