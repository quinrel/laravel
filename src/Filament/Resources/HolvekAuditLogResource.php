<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Holvek\Laravel\Models\HolvekAuditLog;

class HolvekAuditLogResource extends Resource
{
    protected static ?string $model = HolvekAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Holvek SEO';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_id')
                    ->numeric(),
                Forms\Components\TextInput::make('model_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('model_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model_id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolvekAuditLogs::route('/'),
        ];
    }
}
