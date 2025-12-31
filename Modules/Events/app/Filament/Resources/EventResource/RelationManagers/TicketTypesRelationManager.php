<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Events\Enums\TicketTypeStatus;

/**
 * Relation Manager for managing Ticket Types within an Event.
 */
class TicketTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketTypes';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Ticket Types');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Ticket Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Early Bird, VIP, Student'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(TicketTypeStatus::class)
                            ->default(TicketTypeStatus::Active)
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Available Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Leave empty for unlimited'),

                        Forms\Components\DateTimePicker::make('sale_start_date')
                            ->label('Sale Starts')
                            ->seconds(false)
                            ->native(false),

                        Forms\Components\DateTimePicker::make('sale_end_date')
                            ->label('Sale Ends')
                            ->seconds(false)
                            ->native(false),

                        Forms\Components\TextInput::make('min_per_order')
                            ->label('Minimum Per Order')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),

                        Forms\Components\TextInput::make('max_per_order')
                            ->label('Maximum Per Order')
                            ->numeric()
                            ->default(10)
                            ->minValue(1),
                    ]),

                Section::make('Additional Options')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('What\'s included with this ticket?')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_hidden')
                            ->label('Hidden Ticket')
                            ->helperText('Only accessible via direct link'),

                        Forms\Components\Toggle::make('requires_promo_code')
                            ->label('Requires Promo Code')
                            ->helperText('Must enter code to see/purchase'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ticket Types')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Columns\TextColumn::make('name')
                    ->label('Ticket Name')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('EGP')
                    ->sortable(),

                Columns\TextColumn::make('quantity')
                    ->label('Total')
                    ->placeholder('∞')
                    ->alignCenter(),

                Columns\TextColumn::make('quantity_sold')
                    ->label('Sold')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Columns\TextColumn::make('available_quantity')
                    ->label('Available')
                    ->getStateUsing(fn ($record) => $record->getAvailableQuantity)
                    ->badge()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : 'info')
                    ->alignCenter(),

                Columns\TextColumn::make('sale_start_date')
                    ->label('Sale Period')
                    ->dateTime('M j')
                    ->description(fn ($record) => $record->sale_end_date?->format('to M j') ?? 'No end date'),

                Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Columns\IconColumn::make('is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options(TicketTypeStatus::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['currency'] = $this->getOwnerRecord()->currency ?? 'EGP';
                        $data['is_free'] = ($data['price'] ?? 0) == 0;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function ($record): void {
                        if ($record->attendees()->exists()) {
                            throw new \Exception('Cannot delete ticket type with existing attendees.');
                        }
                    }),

                Action::make('toggle_status')
                    ->label(fn ($record) => $record->status === TicketTypeStatus::Active ? 'Disable' : 'Enable')
                    ->icon(fn ($record) => $record->status === TicketTypeStatus::Active
                        ? Heroicon::OutlinedPause
                        : Heroicon::OutlinedPlay
                    )
                    ->color(fn ($record) => $record->status === TicketTypeStatus::Active ? 'warning' : 'success')
                    ->action(function ($record): void {
                        $record->update([
                            'status' => $record->status === TicketTypeStatus::Active
                                ? TicketTypeStatus::Paused
                                : TicketTypeStatus::Active,
                        ]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Ticket Types')
            ->emptyStateDescription('Add ticket types to allow registrations.')
            ->emptyStateIcon(Heroicon::OutlinedTicket);
    }
}
