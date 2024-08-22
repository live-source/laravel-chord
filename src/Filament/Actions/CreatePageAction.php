<?php

namespace LiveSource\Chord\Filament\Actions;

use Filament\Actions\CreateAction;
use LiveSource\Chord\Facades\Chord;
use LiveSource\Chord\Models\ChordPage;
use LiveSource\Chord\PageTypes\PageType;

class CreatePageAction extends CreateAction
{
    public string | int | null $parent_id = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('')
            ->icon('heroicon-s-plus-circle')
            ->iconButton()
            ->size('xl')
            ->modalWidth('md')
            ->form(PageType::getSettingsFormSchema())
            ->model(function () {
                if (isset($this->getFormData()['type'])) {
                    return Chord::getPageTypeClass($this->getFormData()['type']);
                }

                return ChordPage::class;
            })
            ->successRedirectUrl(function (ChordPage $record, array $arguments): ?string {
                return $record->afterCreateRedirectURL();
            });
    }
}
