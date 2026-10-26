<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Quinrel\Laravel\Data\GscInspectionDiagnosis;
use Quinrel\Laravel\Models\QuinrelGscMetric;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Indexing\GoogleIndexingService;
use Quinrel\Laravel\Services\Media\AiAltTextGenerator;
use Quinrel\Laravel\Services\Tracking\GscIndexStatus;

class QuinrelMetaResource extends Resource
{
    protected static ?string $model = QuinrelMeta::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Quinrel SEO';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('QuinrelMetaTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Metadata & Schema')
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
                                    ->rules(['nullable', 'json'])
                                    ->formatStateUsing(fn (mixed $state): ?string => is_array($state) ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (is_string($state) ? $state : null))
                                    ->dehydrateStateUsing(fn (mixed $state): ?array => is_string($state) && trim($state) !== '' ? (array) json_decode($state, true) : (is_array($state) ? $state : null))
                                    ->columnSpanFull(),
                            ])->columns(2),
                        Forms\Components\Tabs\Tab::make('Index Status & Remediation')
                            ->schema([
                                Forms\Components\Fieldset::make('Google Search Console (Last 30 Days)')
                                    ->schema([
                                        Forms\Components\Placeholder::make('clicks')
                                            ->content(function (?QuinrelMeta $record) {
                                                if (! $record || ! $record->canonical_url) {
                                                    return 'N/A';
                                                }

                                                return (string) (QuinrelGscMetric::where('url', $record->canonical_url)
                                                    ->where('date', '>=', now()->subDays(30))
                                                    ->sum('clicks') ?: '0');
                                            }),
                                        Forms\Components\Placeholder::make('impressions')
                                            ->content(function (?QuinrelMeta $record) {
                                                if (! $record || ! $record->canonical_url) {
                                                    return 'N/A';
                                                }

                                                return (string) (QuinrelGscMetric::where('url', $record->canonical_url)
                                                    ->where('date', '>=', now()->subDays(30))
                                                    ->sum('impressions') ?: '0');
                                            }),
                                        Forms\Components\Placeholder::make('avg_position')
                                            ->content(function (?QuinrelMeta $record) {
                                                if (! $record || ! $record->canonical_url) {
                                                    return 'N/A';
                                                }
                                                $avg = QuinrelGscMetric::where('url', $record->canonical_url)
                                                    ->where('date', '>=', now()->subDays(30))
                                                    ->avg('position');

                                                return $avg ? number_format((float) $avg, 2) : 'N/A';
                                            }),
                                    ])
                                    ->columns(3),
                                Forms\Components\Placeholder::make('index_action_plan')
                                    ->label('Live Inspection & Action Plan')
                                    ->content(function (?QuinrelMeta $record): string {
                                        if (! $record || ! $record->canonical_url) {
                                            return 'Save a canonical URL to enable Google Index Status inspection.';
                                        }

                                        /** @var array{diagnosis?: GscInspectionDiagnosis, actions?: array<int, array{severity: string, title: string, remediation: string}>}|null $plan */
                                        $plan = Cache::get('quinrel:gsc-diagnosis:'.md5($record->canonical_url));
                                        if (! is_array($plan) || ! isset($plan['diagnosis']) || ! ($plan['diagnosis'] instanceof GscInspectionDiagnosis)) {
                                            return 'No cached inspection report yet. Click "Inspect Live URL Now" to query Google Search Console.';
                                        }

                                        $diagnosis = $plan['diagnosis'];
                                        $summary = "[{$diagnosis->healthLabel()}] Coverage: {$diagnosis->coverageState} | Indexing: {$diagnosis->indexingState}";

                                        $actions = $plan['actions'] ?? [];
                                        if ($actions === []) {
                                            return $summary;
                                        }

                                        $titles = array_map(
                                            fn (array $a): string => "[{$a['severity']}] {$a['title']}: {$a['remediation']}",
                                            $actions
                                        );

                                        return $summary."\n".implode("\n", $titles);
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->hidden(fn (?QuinrelMeta $record) => $record === null),
                    ])
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('canonical_url')
                    ->searchable()
                    ->toggleable(),
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
                Tables\Actions\Action::make('adopt_google_canonical')
                    ->label('Adopt Google Canonical')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->visible(fn (QuinrelMeta $record): bool => ! empty($record->canonical_url))
                    ->action(function (QuinrelMeta $record): void {
                        app(GoogleIndexingService::class)->adoptGoogleCanonical($record);
                    }),
                Tables\Actions\Action::make('inspect_live_url')
                    ->label('Inspect Live URL Now')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn (QuinrelMeta $record): bool => ! empty($record->canonical_url))
                    ->action(function (QuinrelMeta $record): void {
                        if (! $record->canonical_url) {
                            return;
                        }
                        $key = 'quinrel:gsc-inspect:'.md5($record->canonical_url);
                        if (! RateLimiter::tooManyAttempts($key, 5)) {
                            RateLimiter::hit($key, 60);
                            app(GscIndexStatus::class)->inspectAndPlan($record->canonical_url, $record);
                        }
                    }),
                Tables\Actions\Action::make('generate_ai_alt_text')
                    ->label('Generate Alt Text with AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->action(function (QuinrelMeta $record): void {
                        app(AiAltTextGenerator::class)->autofixMetaRecord($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEmbeddedFormSchema(string $relationship = 'quinrelMeta'): Forms\Components\Section
    {
        return Forms\Components\Section::make('SEO & Discovery (Quinrel)')
            ->relationship($relationship)
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('target_keyword')
                    ->maxLength(255),
                Forms\Components\TextInput::make('canonical_url')
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('robots')
                    ->maxLength(255)
                    ->default('index, follow'),
                Forms\Components\KeyValue::make('hreflang_links')
                    ->keyLabel('Locale Code (e.g. en-US)')
                    ->valueLabel('Localized URL')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('custom_schema')
                    ->rules(['nullable', 'json'])
                    ->formatStateUsing(fn (mixed $state): ?string => is_array($state) ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (is_string($state) ? $state : null))
                    ->dehydrateStateUsing(fn (mixed $state): ?array => is_string($state) && trim($state) !== '' ? (array) json_decode($state, true) : (is_array($state) ? $state : null))
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuinrelMetas::route('/'),
            'create' => Pages\CreateQuinrelMeta::route('/create'),
            'edit' => Pages\EditQuinrelMeta::route('/{record}/edit'),
        ];
    }
}
