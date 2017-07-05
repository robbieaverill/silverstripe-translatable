<?php

namespace SilverStripe\Translatable\Model\Translatable;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxField_Readonly;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\FormTransformation;
use SilverStripe\ORM\DataObject;

/**
 * Transform a formfield to a "translatable" representation,
 * consisting of the original formfield plus a readonly-version
 * of the original value, wrapped in a CompositeField.
 *
 * @param DataObject $original Needs the original record as we populate
 *                   the readonly formfield with the original value
 *
 * @package translatable
 * @subpackage misc
 */
class Transformation extends FormTransformation
{
    /**
     * @var DataObject
     */
    private $original = null;

    public function __construct(DataObject $original)
    {
        $this->original = $original;
        parent::__construct();
    }

    /**
     * Returns the original DataObject attached to the Transformation
     *
     * @return DataObject
     */
    public function getOriginal()
    {
        return $this->original;
    }

    public function transformFormField(FormField $field)
    {
        $newfield = $field->performReadOnlyTransformation();
        $fn = 'transform' . get_class($field);
        return $this->hasMethod($fn) ? $this->$fn($newfield, $field) : $this->baseTransform($newfield, $field);
    }

    /**
     * Transform a translatable CheckboxField to show the field value from the default language
     * in the label.
     *
     * @param FormField $nonEditableField The readonly field to contain the original value
     * @param FormField $originalField The original editable field containing the translated value
     * @return CheckboxField The field with a modified label
     */
    protected function transformCheckboxField(CheckboxField_Readonly $nonEditableField, CheckboxField $originalField)
    {
        $label = $originalField->Title();
        $fieldName = $originalField->getName();
        $value = ($this->original->$fieldName)
            ? _t('Translatable_Transform.CheckboxValueYes', 'Yes')
            : _t('Translatable_Transform.CheckboxValueNo', 'No');
        $originalLabel = _t(
            'Translatable_Transform.OriginalCheckboxLabel',
            'Original: {value}',
            'Addition to a checkbox field label showing the original value of the translatable field.',
            array('value'=>$value)
        );
        $originalField->setTitle($label . ' <span class="originalvalue">(' . $originalLabel . ')</span>');
        return $originalField;
    }

    /**
     * Transform a translatable field to show the field value from the default language
     * DataObject below the translated field.
     *
     * This is a fallback function which handles field types that aren't transformed by
     * $this->transform{FieldType} functions.
     *
     * @param FormField $nonEditableField The readonly field to contain the original value
     * @param FormField $originalField The original editable field containing the translated value
     * @return CompositeField The transformed field
     */
    protected function baseTransform($nonEditableField, $originalField)
    {
        $fieldname = $originalField->getName();

        $nonEditableField_holder = new CompositeField($nonEditableField);
        $nonEditableField_holder->setName($fieldname.'_holder');
        $nonEditableField_holder->addExtraClass('originallang_holder');
        $nonEditableField->setValue($this->original->$fieldname);
        $nonEditableField->setName($fieldname.'_original');
        $nonEditableField->addExtraClass('originallang');
        $nonEditableField->setTitle(_t(
            'Translatable_Transform.OriginalFieldLabel',
            'Original {title}',
            'Label for the original value of the translatable field.',
            array('title'=>$originalField->Title())
        ));

        $nonEditableField_holder->insertBefore($originalField, $fieldname.'_original');
        return $nonEditableField_holder;
    }
}
