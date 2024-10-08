<?php

declare(strict_types=1);

namespace LiveSource\Chord\Concerns;

use Illuminate\Support\Str;
use Indra\Revisor\Facades\Revisor;
use Parental\HasParent;

trait HasInheritance
{
    use HasParent;

    /**
     * Overrides Model::getTable to return the appropriate
     * table (draft, version, published) based on
     * the current RevisorMode
     */
    public function getTable(): string
    {
        return Revisor::getSuffixedTableNameFor($this->getBaseTable(), $this->getRevisorMode());
    }

    /**
     * Get the base table name for the model
     */
    public function getBaseTable(): string
    {
        return $this->baseTable ?? Str::snake(Str::pluralStudly(class_basename($this->getParentClass())));
    }
}
