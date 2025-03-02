<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Pengaturan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Akun'),
                Forms\Components\Select::make('type')
                    ->options([
                        'bank' => 'Bank',
                        'cash' => 'Tunai',
                        'e-wallet' => 'E-Wallet',
                        'credit card' => 'Kartu Kredit',
                    ])
                    ->required()
                    ->label('Tipe'),
                Forms\Components\TextInput::make('initial_balance')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->label('Saldo Awal'),
                Forms\Components\TextInput::make('current_balance')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->label('Saldo Saat Ini'),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->default('IDR')
                    ->maxLength(255)
                    ->label('Mata Uang'),
                Forms\Components\TextInput::make('icon')
                    ->maxLength(255)
                    ->label('Ikon'),
                Forms\Components\ColorPicker::make('color')
                    ->label('Warna'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->label('Deskripsi'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true)
                    ->label('Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama Akun'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('current_balance')
                    ->money('IDR')
                    ->sortable()
                    ->label('Saldo Saat Ini'),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->label('Mata Uang'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat Pada'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Diperbarui Pada'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'bank' => 'Bank',
                        'cash' => 'Tunai',
                        'e-wallet' => 'E-Wallet',
                        'credit card' => 'Kartu Kredit',
                    ])
                    ->label('Tipe'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
