<?php

namespace LiveSource\Chord\Models;

use LiveSource\Chord\Concerns\HasContentBlocks;
use Parental\HasParent as HasInheritance;

class ContentPage extends ChordPage
{
    use HasContentBlocks;
    use HasInheritance;

    protected static string $defaultLayout = 'site.page.content-page';
}
