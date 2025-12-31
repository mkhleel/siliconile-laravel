<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Resources\NetworkSyncLogResource\Pages;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Network\Enums\SyncAction;
use Modules\Network\Filament\Resources\NetworkSyncLogResource;

class ViewNetworkSyncLog extends ViewRecord
{
    protected static string $resource = NetworkSyncLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // Main Log Information (2/3 width)
                        Section::make(__('Log Details'))
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->columnSpan(2)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('id')
                                            ->label('ID')
                                            ->badge()
                                            ->color('gray'),

                                        TextEntry::make('created_at')
                                            ->label(__('Date & Time'))
                                            ->dateTime(),

                                        TextEntry::make('member.member_code')
                                            ->label(__('Member Code'))
                                            ->copyable(),

                                        TextEntry::make('member.user.name')
                                            ->label(__('Member Name')),
                                    ]),
                            ]),

                        // Status Sidebar (1/3 width)
                        Section::make(__('Status'))
                            ->icon(Heroicon::OutlinedSignal)
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('action')
                                    ->label(__('Action'))
                                    ->badge()
                                    ->formatStateUsing(fn (SyncAction $state) => $state->label())
                                    ->color(fn (SyncAction $state) => match ($state) {
                                        SyncAction::CREATED, SyncAction::ENABLED => 'success',
                                        SyncAction::DISABLED, SyncAction::KICKED, SyncAction::DELETED => 'danger',
                                        SyncAction::UPDATED, SyncAction::PASSWORD_RESET => 'warning',
                                    }),

                                TextEntry::make('status')
                                    ->label(__('Result'))
                                    ->badge()
                                    ->color(fn (string $state) => $state === 'success' ? 'success' : 'danger'),

                                TextEntry::make('router_ip')
                                    ->label(__('Router IP'))
                                    ->icon(Heroicon::OutlinedServerStack),
                            ]),
                    ]),

                // Error Section (full width, conditional)
                Section::make(__('Error Information'))
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->iconColor('danger')
                    ->schema([
                        TextEntry::make('error_message')
                            ->label(__('Error Message'))
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->status === 'failed'),

                // Metadata Section (full width, conditional)
                Section::make(__('Additional Metadata'))
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->schema([
                        TextEntry::make('metadata')
                            ->label(__('JSON Data'))
                            ->json()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => ! empty($record->metadata)),
            ]);
    }
}
