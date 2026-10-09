<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Holvek\Laravel\Models\HolvekGscMetric;
use Holvek\Laravel\Models\HolvekMeta;

class HolvekMetaResource extends Resource
{
    protected static ?string $model = HolvekMeta::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Holvek SEO';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('model_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('model_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('canonical_url')
                    ->url()
                    ->maxLength(255),
                Forms\Components\KeyValue::make('hreflang_links')
                    ->keyLabel('Locale Code (e.g. en-US)')
                    ->valueLabel('Localized URL')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('robots')
                    ->maxLength(255)
                    ->default('index, follow'),
                Forms\Components\TextInput::make('target_keyword')
                    ->maxLength(255),
                Forms\Components\Textarea::make('custom_schema')
                    ->columnSpanFull(),
                Forms\Components\Fieldset::make('Google Search Console (Last 30 Days)')
                    ->schema([
                        Forms\Components\Placeholder::make('clicks')
                            ->content(function ($record) {
                                if (! $record || ! $record->canonical_url) {
                                    return 'N/A';
                                }

                                return HolvekGscMetric::where('url', $record->canonical_url)
                                    ->where('date', '>=', now()->subDays(30))
                                    ->sum('clicks') ?: '0';
                            }),
                        Forms\Components\Placeholder::make('impressions')
                            ->content(function ($record) {
                                if (! $record || ! $record->canonical_url) {
                                    return 'N/A';
                                }

                                return HolvekGscMetric::where('url', $record->canonical_url)
                                    ->where('date', '>=', now()->subDays(30))
                                    ->sum('impressions') ?: '0';
                            }),
                        Forms\Components\Placeholder::make('avg_position')
                            ->content(function ($record) {
                                if (! $record || ! $record->canonical_url) {
                                    return 'N/A';
                                }
                                $avg = HolvekGscMetric::where('url', $record->canonical_url)
                                    ->where('date', '>=', now()->subDays(30))
                                    ->avg('position');

                                return $avg ? number_format((float) $avg, 2) : 'N/A';
                            }),
                    ])
                    ->columns(3)
                    ->hidden(fn ($record) => $record === null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model_id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('robots')
                    ->searchable(),
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
            'index' => Pages\ListHolvekMetas::route('/'),
            'create' => Pages\CreateHolvekMeta::route('/create'),
            'edit' => Pages\EditHolvekMeta::route('/{record}/edit'),
        ];
    }
}
