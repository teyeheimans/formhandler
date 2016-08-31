<?php
namespace FormHandler\Field;

use FormHandler\Form;

/**
 * Create a checkbox.
 *
 * With this class you can create a checkbox on the given form.
 */
class CheckBox extends AbstractFormField
{
    /**
     * Is this field checked or not.
     * @var bool
     */
    protected $checked;

    /**
     * The value of this field.
     * @var string
     */
    protected $value;

    /**
     * The label of this field
     * NOTE: This value is only used if it's also used by the Formatter!
     * @var string
     */
    protected $label;

    /**
     * CheckBox constructor.
     * @param Form $form
     * @param string $name
     * @param string $value
     */
    public function __construct(Form &$form, $name = '', $value = '1')
    {
        $this->form = $form;
        $this->form->addField($this);

        if (!empty($value)) {
            $this->setValue($value);
        }

        if (!empty($name)) {
            $this->setName($name);
        }
    }

    /**
     * Set the name of this field
     *
     * @param string $name
     * @return CheckBox
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->setCheckedBasedOnValue();

        return $this;
    }

    /**
     * Set the label used for this field.
     * The label will NOT be HTML escaped, so please be aware to do this yourself!
     *
     * Please note: this is just a "container" for the label text.
     * The Formatter class will generate the label for us!
     *
     * @param string $label
     * @return CheckBox
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get the label for this field.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Specifies that an input element should be preselected when the page loads
     *
     * @param bool $checked
     * @return CheckBox
     */
    public function setChecked($checked)
    {
        $this->clearCache();
        $this->checked = $checked;
        return $this;
    }

    /**
     * Return if this input element should be preselected when the page loads
     *
     * @return bool
     */
    public function isChecked()
    {
        return $this->checked;
    }

    /**
     * Set the value for this field and return the CheckBox reference
     *
     * @param string $value
     * @return CheckBox
     */
    public function setValue($value)
    {
        parent::setValue($value);
        $this->setCheckedBasedOnValue();
        return $this;
    }

    /**
     * Return string representation of this field
     *
     * @return string
     */
    public function render()
    {
        $str = '<input type="checkbox"';

        if (!empty($this->name)) {
            $str .= ' name="' . $this->name . '"';

            /*
             * Why is this???
             * if( $this -> form -> getMethod() == Form::METHOD_POST )
             * {
             * if( isset( $_POST[$this->name]) )
             * {
             * $this -> setChecked( $_POST[$this->name] == $this -> value );
             * }
             * }
             * else if( isset( $_GET[$this->name]) )
             * {
             * $this -> setChecked( $_GET[$this->name] == $this -> value );
             * }
             */
        }

        if ($this->checked) {
            $str .= ' checked="checked"';
        }

        if ($this->disabled) {
            $str .= ' disabled="disabled"';
        }

        if (!empty($this->value)) {
            $str .= ' value="' . htmlentities($this->value, ENT_QUOTES, 'UTF-8') . '"';
        }

        $str .= parent::render();
        $str .= ' />';

        return $str;
    }

    /**
     * Set this field to checked if the value matches
     */
    protected function setCheckedBasedOnValue()
    {
        $value = $this->form->getFieldValue($this->name);
        if (is_array($value)) {
            // is this ever used?
            $this->setChecked(in_array($this->getValue(), $value));
        } else {
            $this->setChecked($this->getValue() == $value);
        }
    }
}
