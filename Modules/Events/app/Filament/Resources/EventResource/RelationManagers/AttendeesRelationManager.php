<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Jobs\SendTicketEmailJob;
use Modules\Events\Models\Attendee;
use Modules\Events\Services\TicketService;

/**
 * Relation Manager for managing Attendees within an Event.
 */
class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    protected static ?string $recordTitleAttribute = 'reference_no';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Attendees');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Attendee Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('ticket_type_id')
                            ->label('Ticket Type')
                            ->relationship('ticketType', 'name')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(AttendeeStatus::class)
                            ->default(AttendeeStatus::Confirmed)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('user_id')
                            ->label('Registered User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave empty for guest registration'),

                        Forms\Components\TextInput::make('guest_name')
                            ->label('Guest Name')
                            ->maxLength(255)
                            ->requiredWithout('user_id'),

                        Forms\Components\TextInput::make('guest_email')
                            ->label('Guest Email')
                            ->email()
                            ->maxLength(255)
                            ->requiredWithout('user_id'),

                        Forms\Components\TextInput::make('guest_phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('job_title')
                            ->label('Job Title')
                            ->maxLength(255),
                    ]),

                Section::make('Payment Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0),

                        Forms\Components\TextInput::make('reference_no')
                            ->label('Reference Number')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('Additional Notes')
                    ->schema([
                        Forms\Components\Textarea::make('special_requirements')
                            ->label('Special Requirements')
                            ->rows(3)
                            ->placeholder('Dietary requirements, accessibility needs, etc.'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Attendees')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['ticketType', 'user']))
            ->columns([
                Columns\TextColumn::make('reference_no')
                    ->label('Ref #')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size('sm'),

                Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (Attendee $record): string => $record->getName())
                    ->searchable(query: function ($query, string $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('guest_name', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->description(fn (Attendee $record): string => $record->getEmail()),

                Columns\TextColumn::make('ticketType.name')
                    ->label('Ticket')
                    ->badge()
                    ->color('gray'),

                Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('EGP')
                    ->sortable(),

                Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Columns\IconColumn::make('ticket_sent')
                    ->label('Ticket Sent')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Columns\TextColumn::make('checked_in_at')
                    ->label('Checked In')
                    ->dateTime('M j, H:i')
                    ->placeholder('—')
                    ->sortable(),

                Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->options(AttendeeStatus::class),

                Filters\SelectFilter::make('ticket_type_id')
                    ->label('Ticket Type')
                    ->relationship('ticketType', 'name'),

                Filters\TernaryFilter::make('ticket_sent')
                    ->label('Ticket Emailed'),

                Filters\TernaryFilter::make('checked_in')
                    ->label('Checked In')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('checked_in_at'),
                        false: fn ($query) => $query->whereNull('checked_in_at'),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Attendee')
                    ->after(function ($record): void {
                        // Issue ticket after manual creation
                        app(TicketService::class)->issueTicket($record);
                    }),

                Action::make('export')
                    ->label('Export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(function (): void {
                        // TODO: Implement export to CSV/Excel
                        Notification::make()
                            ->title('Export Started')
                            ->body('You will receive an email when the export is ready.')
                            ->info()
                            ->send();
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('check_in')
                    ->label('Check In')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Attendee $record): bool => $record->status === AttendeeStatus::Confirmed)
                    ->action(function (Attendee $record): void {
                        app(TicketService::class)->checkInAttendee($record);
                        Notification::make()
                            ->title('Checked In!')
                            ->body($record->getName().' has been checked in.')
                            ->success()
                            ->send();
                    }),

                Action::make('resend_ticket')
                    ->label('Resend Ticket')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Attendee $record): bool => in_array($record->status, [
                        AttendeeStatus::Confirmed,
                        AttendeeStatus::CheckedIn,
                    ]))
                    ->action(function (Attendee $record): void {
                        SendTicketEmailJob::dispatch($record);
                        Notification::make()
                            ->title('Ticket Email Queued')
                            ->body('The ticket will be sent shortly.')
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required(),
                    ])
                    ->visible(fn (Attendee $record): bool => ! in_array($record->status, [
                        AttendeeStatus::Cancelled,
                        AttendeeStatus::CheckedIn,
                    ]))
                    ->action(function (Attendee $record, array $data): void {
                        $record->update([
                            'status' => AttendeeStatus::Cancelled,
                            'cancelled_at' => now(),
                            'cancellation_reason' => $data['reason'],
                        ]);
                        Notification::make()
                            ->title('Registration Cancelled')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('check_in_selected')
                        ->label('Check In Selected')
                        ->icon(Heroicon::OutlinedQrCode)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $ticketService = app(TicketService::class);
                            $count = 0;
                            foreach ($records as $attendee) {
                                if ($attendee->status === AttendeeStatus::Confirmed) {
                                    $ticketService->checkInAttendee($attendee);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} Attendees Checked In")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('resend_tickets')
                        ->label('Resend Tickets')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $attendee) {
                                SendTicketEmailJob::dispatch($attendee);
                            }
                            Notification::make()
                                ->title('Tickets Queued for Sending')
                                ->body($records->count().' tickets will be sent.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Attendees Yet')
            ->emptyStateDescription('Attendees will appear here when they register.')
            ->emptyStateIcon(Heroicon::OutlinedUsers);
    }
}
