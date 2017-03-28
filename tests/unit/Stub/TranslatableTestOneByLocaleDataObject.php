<?php

namespace SilverStripe\Translatable\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TranslatableTestOneByLocaleDataObject extends DataObject implements TestOnly
{
    private static $db = [
        'TranslatableProperty' => 'Text'
    ];

    private static $table_name = 'TranslatableTestOneByLocaleDataObject';
}
