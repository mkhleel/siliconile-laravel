<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Resources\NetworkSyncLogResource\Pages;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Network\Enums\SyncAction;
use Modules\Network\Filament\Resources\NetworkSyncLogResource;

class ViewNetworkSyncLog extends ViewRecord
{
    protected static string $resource = NetworkSyncLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Log Details'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),

                        TextEntry::make('created_at')
                            ->label(__('Date'))
                            ->dateTime(),

                        TextEntry::make('member.member_code')
                            ->label(__('Member Code')),

                        TextEntry::make('member.user.name')
                            ->label(__('Member Name')),

                        TextEntry::make('action')
                            ->label(__('Action'))
                            ->badge()
                            ->formatStateUsing(fn (SyncAction $state) => $state->label()),

                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn (string $state) => $state === 'success' ? 'success' : 'danger'),

                        TextEntry::make('router_ip')
                            ->label(__('Router IP')),
                    ]),

                Section::make(__('Error Information'))
                    ->schema([
                        TextEntry::make('error_message')
                            ->label(__('Error Message'))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->status === 'failed'),

                Section::make(__('Metadata'))
                    ->schema([
                        TextEntry::make('metadata')
                            ->label(__('Additional Data'))
                            ->json()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => ! empty($record->metadata)),
            ]);
    }
}
