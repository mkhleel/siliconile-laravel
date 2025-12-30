<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\MemberResource\Pages;

use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Membership\Filament\Resources\MemberResource;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Member Overview')
                    ->schema([
                        Components\TextEntry::make('member_code')
                            ->label('Member Code')
                            ->copyable(),
                        Components\TextEntry::make('user.name')
                            ->label('Name'),
                        Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable(),
                        Components\TextEntry::make('member_type')
                            ->badge(),
                        Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean(),
                        Components\TextEntry::make('created_at')
                            ->label('Member Since')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Corporate Information')
                    ->schema([
                        Components\TextEntry::make('company_name'),
                        Components\TextEntry::make('company_vat_number')
                            ->label('VAT Number'),
                        Components\TextEntry::make('company_address'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->isCorporate()),

                Section::make('Profile')
                    ->schema([
                        Components\TextEntry::make('bio')
                            ->columnSpanFull(),
                        Components\TextEntry::make('interests')
                            ->badge()
                            ->separator(','),
                    ])
                    ->collapsible(),
            ]);
    }
}
