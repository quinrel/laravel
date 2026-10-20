<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Quinrel\Laravel\Models\QuinrelRedirect;

class QuinrelRedirectResource extends Resource
{
    protected static ?string $model = QuinrelRedirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static ?string $navigationGroup = 'Quinrel SEO';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('source_url')
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        fn (Forms\Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                            if ($get('is_regex') && is_string($value)) {
                                $pattern = '#^'.str_replace('#', '\#', $value).'$#';
                                if (@preg_match($pattern, '') === false) {
                                    $fail('The source URL is not a valid regular expression.');
                                }
                            }
                        },
                    ]),
                Forms\Components\TextInput::make('target_url')
                    ->required()
                    ->maxLength(255)
                    ->notIn([fn (Forms\Get $get) => $get('source_url')]),
                Forms\Components\Select::make('status_code')
                    ->options([
                        '301' => '301 - Permanent',
                        '302' => '302 - Temporary',
                        '307' => '307 - Temporary Redirect',
                        '308' => '308 - Permanent Redirect',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_regex')
                    ->default(false),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_code')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_chain')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-check-circle')
                    ->falseColor('success'),
                Tables\Columns\IconColumn::make('is_loop')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->trueColor('danger')
                    ->falseIcon('')
                    ->falseColor(''),
                Tables\Columns\IconColumn::make('is_regex')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('flatten_chain')
                    ->label('Flatten Chain')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('success')
                    ->visible(fn (QuinrelRedirect $record): bool => $record->is_chain && ! $record->is_loop)
                    ->action(function (QuinrelRedirect $record) {
                        if (is_array($record->chain_details) && ! empty($record->chain_details)) {
                            $target = end($record->chain_details);
                            $record->update([
                                'target_url' => $target,
                                'is_chain' => false,
                                'chain_details' => null,
                            ]);
                        }
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
            'index' => Pages\ListQuinrelRedirects::route('/'),
            'create' => Pages\CreateQuinrelRedirect::route('/create'),
            'edit' => Pages\EditQuinrelRedirect::route('/{record}/edit'),
        ];
    }
}
