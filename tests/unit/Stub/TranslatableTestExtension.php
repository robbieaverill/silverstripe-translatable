<?php

namespace SilverStripe\Translatable\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class TranslatableTestExtension extends DataExtension implements TestOnly
{
    private static $db = [
        'TranslatableDecoratedProperty' => 'Text'
    ];
}
