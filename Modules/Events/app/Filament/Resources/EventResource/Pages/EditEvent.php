<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Filament\Resources\EventResource;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('publish')
                ->label('Publish')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === EventStatus::Draft)
                ->action(function (): void {
                    $this->record->update(['status' => EventStatus::Published]);
                    Notification::make()
                        ->title('Event Published')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('preview')
                ->label('Preview')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn (): string => route('events.show', $this->record->slug))
                ->openUrlInNewTab(),

            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Sync currency across ticket types
        if (isset($data['ticketTypes'])) {
            foreach ($data['ticketTypes'] as &$ticketType) {
                $ticketType['currency'] = $data['currency'] ?? 'EGP';
                $ticketType['is_free'] = ($ticketType['price'] ?? 0) == 0;
            }
        }

        return $data;
    }
}
