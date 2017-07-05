<?php

namespace SilverStripe\Translatable\Forms;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Forms\FormField;
use SilverStripe\View\HTML;

/**
 * TEMPORARY
 *
 * Removed from 4.0 - added temporarily to restore functionality while upgrading to SS4
 */
class TemporaryInlineFormAction extends FormField
{
    /**
     * Create a new action button.
     * @param action The method to call when the button is clicked
     * @param title The label on the button
     * @param extraClass A CSS class to apply to the button in addition to 'action'
     */
    public function __construct($action, $title = '', $extraClass = '')
    {
        $this->extraClass = ' '.$extraClass;
        parent::__construct($action, $title);
    }

    public function performReadonlyTransformation()
    {
        return $this->castedCopy('InlineFormAction_ReadOnly');
    }

    /**
     * @param array $properties
     * @return HTMLText
     */
    public function Field($properties = [])
    {
        return DBField::create_field(
            'HTMLText',
            HTML::createTag('input', [
                'type' => 'submit',
                'name' => sprintf('action_%s', $this->getName()),
                'value' => $this->title,
                'id' => $this->ID(),
                'class' => sprintf('action%s', $this->extraClass),
            ])
        );
    }

    public function Title()
    {
        return false;
    }
}
