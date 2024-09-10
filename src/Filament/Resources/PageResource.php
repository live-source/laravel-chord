<?php

namespace LiveSource\Chord\Filament\Resources;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use LiveSource\Chord\Enums\Menu;
use LiveSource\Chord\Facades\Chord;
use LiveSource\Chord\Facades\ModifyChord;
use LiveSource\Chord\Filament\Actions\EditPageSettingsTableAction;
use LiveSource\Chord\Filament\Actions\EditPageTableAction;
use Livesource\Chord\Filament\Actions\PublishPageBulkAction;
use LiveSource\Chord\Filament\Actions\PublishPageTableAction;
use LiveSource\Chord\Filament\Actions\UnpublishPageTableAction;
use LiveSource\Chord\Filament\Actions\ViewChildPagesTableAction;
use LiveSource\Chord\Models\ChordPage;

class PageResource extends Resource
{
    protected static ?string $model = ChordPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Page';

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function getSettingsFormSchema(): array
    {
        $pageTypes = Chord::getPageTypeOptionsForSelect();

        return [
            Grid::make(['default' => 1])
                ->schema([
                    Select::make('type')
                        ->options($pageTypes)
                        ->default(array_key_first($pageTypes) ?? null)
                        ->selectablePlaceholder(false)
                        ->required(),
                    // todo make this only show folder options
                    SelectTree::make('parent_id')
                        ->placeholder('Top level')
                        ->relationship('parent', 'title', 'parent_id')
                        ->label('Parent'),
                    TextInput::make('title')
                        ->required()
                        ->generateSlug(),
                    TextInput::make('slug')
                        ->required()
                        ->live(onBlur: false)
                        ->afterStateUpdated(function (string $operation, $state, Set $set) {
                            $set('slug', str($state)->slug());
                        })
                        ->unique(modifyRuleUsing: function (Unique $rule, $get) {
                            return $rule->where('parent_id', $get('parent_id'))->ignore($get('uuid'));
                        }),
                    CheckboxList::make('show_in_menus')
                        ->options(Menu::class)
                        ->afterStateHydrated(function ($component, $state, $context) {
                            if (! filled($state) && $context === 'create') {
                                $component->state([Menu::Header, Menu::Footer]);
                            }
                        }),
                ]),
        ];
    }

    public static function form(Form $form): Form
    {
        $form->schema([
            Grid::make(['default' => 1])
                ->key('main')
                ->schema([
                    TextInput::make('title')->required(),
                ]),
        ]);

        ModifyChord::apply('contentForm', $form);

        return $form;
    }

    public static function table(Table $table): Table
    {
        $orderColumn = static::$model::getOrderColumnName();

        return $table
            ->reorderable($orderColumn)
            ->defaultSort($orderColumn)
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')->formatStateUsing(fn (string $state) => str($state)->headline()),
                Tables\Columns\TextColumn::make('path')
                    ->url(fn (ChordPage $record) => $record->getLink(true))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'revised' => 'warning',
                        'published' => 'success',
                    }),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created')
                    ->prefix('By: ')
                    ->description(fn (ChordPage $record) => 'On: '.$record->created_at),
                Tables\Columns\TextColumn::make('editor.name')
                    ->label('Updated')
                    ->prefix('By: ')
                    ->description(fn (ChordPage $record) => 'On: '.$record->updated_at),
                Tables\Columns\TextColumn::make('publisher.name')
                    ->label('Published')
                    ->prefix('By: ')
                    ->description(fn (ChordPage $record) => 'On: '.$record->published_at),
                //Tables\Columns\TextColumn::make('revisions_count')->label('Revisions')->numeric(),
            ])
            ->emptyStateHeading(function (Table $table) {
                if ($table->hasSearch()) {

                    return 'No pages found for search';
                }

                return 'No pages';
            })
            ->configure()
            ->filters([
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    EditPageTableAction::make(),
                    EditPageSettingsTableAction::make(),
                    Tables\Actions\Action::make('revisions')
                        ->label('History')
                        ->url(fn (ChordPage $record) => PageResource::getUrl('revisions', ['record' => $record->uuid]))
                        ->icon('heroicon-o-clock'),
                    Tables\Actions\ActionGroup::make([
                        PublishPageTableAction::make(),
                        UnpublishPageTableAction::make(),
                    ])->dropdown(false),
                ]),

                ViewChildPagesTableAction::make('children'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    PublishPageBulkAction::make(),
                ]),
            ]);
    }

    public static function revisionsTable(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id'),
            Tables\Columns\TextColumn::make('title'),
            Tables\Columns\IconColumn::make('is_published')->boolean(),
            Tables\Columns\IconColumn::make('is_current')->boolean(),
            Tables\Columns\TextColumn::make('creator.name')
                ->label('Created')
                ->prefix('By: ')
                ->description(fn (ChordPage $record) => 'On: '.$record->created_at),
            Tables\Columns\TextColumn::make('editor.name')
                ->label('Updated')
                ->prefix('By: ')
                ->description(fn (ChordPage $record) => 'On: '.$record->updated_at),
            Tables\Columns\TextColumn::make('publisher.name')
                ->label('Published')
                ->prefix('By: ')
                ->description(fn (ChordPage $record) => 'On: '.$record->published_at),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //RevisionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => PageResource\Pages\ListPages::route('/'),
            'children' => PageResource\Pages\ListPages::route('/{parent}'),
            'edit' => PageResource\Pages\EditPage::route('/{record}/edit/{revision?}'),
            'revisions' => PageResource\Pages\ListRevisions::route('/{record?}/revisions'),
        ];
    }
}
