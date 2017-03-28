<?php

namespace SilverStripe\Translatable\Tests\Stub;

use Page;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;

class TranslatableTestPage extends Page implements TestOnly
{
    // static $extensions is inherited from SiteTree,
    // we don't need to explicitly specify the fields

    private static $db = [
        'TranslatableProperty' => 'Text'
    ];

    private static $has_one = [
        'TranslatableObject' => TranslatableTestDataObject::class
    ];

    private static $table_name = 'TranslatableTestPage';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.Main',
            new TextField('TranslatableProperty')
        );
        $fields->addFieldToTab(
            'Root.Main',
            new DropdownField('TranslatableObjectID')
        );

        $this->applyTranslatableFieldsUpdate($fields, 'updateCMSFields');

        return $fields;
    }
}
