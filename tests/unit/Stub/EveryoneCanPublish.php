<?php

namespace SilverStripe\Translatable\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class EveryoneCanPublish extends DataExtension implements TestOnly
{
    public function canPublish($member = null)
    {
        return true;
    }
}
