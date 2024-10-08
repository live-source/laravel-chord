<?php

namespace LiveSource\Chord\Filament\Actions;

use Filament\Tables\Actions\EditAction;
use LiveSource\Chord\Filament\Resources\PageResource;
use LiveSource\Chord\Models\ChordPage;

class EditPageSettingsTableAction extends EditAction
{
    public static function getDefaultName(): ?string
    {
        return 'settings';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Settings')
            ->icon('heroicon-o-cog-6-tooth')
            ->modalHeading('Edit Page Settings')
            ->modalWidth('sm')
            ->hidden(fn (ChordPage $record) => ! $record->hasContentForm())
            ->form(fn (ChordPage $record) => PageResource::getSettingsFormSchema($this))
            ->recordTitle(fn (ChordPage $record) => $record->title)
            ->modalHeading(fn (ChordPage $record) => 'Configure ' . $record->title);

    }
}
