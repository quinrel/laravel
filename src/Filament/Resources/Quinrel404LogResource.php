<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Quinrel\Laravel\Models\Quinrel404Log;
use Quinrel\Laravel\Services\Technical\Remediation\AutoRedirectCreator;

class Quinrel404LogResource extends Resource
{
    protected static ?string $model = Quinrel404Log::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Quinrel SEO';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('url')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('referrer')
                    ->maxLength(255),
                Forms\Components\TextInput::make('hits')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('referrer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hits')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('create_suggested_redirect')
                    ->label('Create Suggested Redirect')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->action(function (Quinrel404Log $record): void {
                        app(AutoRedirectCreator::class)->createFrom404($record);
                    }),
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
            'index' => Pages\ListQuinrel404Logs::route('/'),
        ];
    }
}
