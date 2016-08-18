<?php
namespace FormHandler\Validator;

/**
 */
class BankNumberValidator extends AbstractValidator
{

    protected $required = true;

    /**
     * Create a new email validator
     *
     * @param string $regex
     * @param boolean $required
     * @param string $message
     */
    public function __construct($required = true, $message = null)
    {
        if ($message === null) {
            $message = dgettext('d2frame', 'Invalid banknumber.');
        }

        $this->setErrorMessage($message);
        $this->setRequired($required);
    }

    /**
     * Check if the given field is valid or not.
     *
     * @return boolean
     */
    public function isValid()
    {
        $value = $this->field->getValue();

        if (is_array($value) || is_object($value)) {
            throw new Exception("This validator only works on scalar types!");
        }

        // required but not given
        if ($this->required && $value == null) {
            return false;
        }  // if the field is not required and the value is empty, then it's also valid
else
            if (! $this->required && $value == "") {
                return true;
            }

        if (! preg_match('/^(\d)+$/', $value))
            return false;

        $length = strlen($value);
        $total = 0;
        $count = 9;

        for ($i = 0; $i < $length; $i ++) {
            $temp = substr($value, $i, 1);
            $total = $total + ($temp * $count);
            $count --;
        }

        $postbank = ($count > 2 && $count <= 7);

        if (($total % 11) == 0 || $postbank)
            return true;
        return false;
    }

    /**
     * Add javascript validation for this field.
     *
     * @param
     *            AbstractFormField &$field
     * @return string
     */
    public function addJavascriptValidation(AbstractFormField &$field)
    {
        static $addedJavascriptFunction = false;

        $script = '';
        if (! $addedJavascriptFunction) {
            $script .= 'function d2BankNumberValidator( field ) {' . PHP_EOL;
            $script .= '    var value = $(field).val();' . PHP_EOL;
            $script .= '    if( !$(field).hasClass("required")) {' . PHP_EOL;
            $script .= '        // the field is not required. Skip the validation if the field is empty.' . PHP_EOL;
            $script .= '        if( $.trim( value ) == "" ) { ' . PHP_EOL;
            $script .= '            $(field).removeClass("invalid");' . PHP_EOL;
            $script .= '            return true;' . PHP_EOL;
            $script .= '        }' . PHP_EOL;
            $script .= '    }' . PHP_EOL;
            $script .= '    // only allow numbers' . PHP_EOL;
            $script .= '    if( !/^(\d)+$/.test( value )) {' . PHP_EOL;
            $script .= '        $(field).addClass("invalid");' . PHP_EOL;
            $script .= '        return false;' . PHP_EOL;
            $script .= '    }' . PHP_EOL;
            $script .= '    // check bank account numbers' . PHP_EOL;
            $script .= '    if( value.length == 9) {' . PHP_EOL;
            $script .= '        var e = 0;' . PHP_EOL;
            $script .= '        for (var i = 0; i < 9; i++) {' . PHP_EOL;
            $script .= '            e += (9 - i) * value.charAt( i );' . PHP_EOL;
            $script .= '        }' . PHP_EOL;
            $script .= '        if( e % 11 != 0 ) {' . PHP_EOL;
            $script .= '            $(field).addClass("invalid");' . PHP_EOL;
            $script .= '            return false;' . PHP_EOL;
            $script .= '        }' . PHP_EOL;
            $script .= '    }' . PHP_EOL;
            $script .= '    // if not 9 numbers long, it should be a "postbank" account, check if the length is correct' . PHP_EOL;
            $script .= '    else if( !(value.length > 2 && value.length <= 7) ) {' . PHP_EOL;
            $script .= '        $(field).addClass("invalid");' . PHP_EOL;
            $script .= '        return; false' . PHP_EOL;
            $script .= '    }' . PHP_EOL;
            $script .= '    $(field).removeClass("invalid");' . PHP_EOL;
            $script .= '    return true;' . PHP_EOL;
            $script .= '}' . PHP_EOL;

            $addedJavascriptFunction = true;
        }

        if ($this->required) {
            $field->addClass('required');
        }

        $form = $field->getForm();
        if (! $form->getId()) {
            $form->setId(uniqid(get_class($form)));
        }

        if (! $field->getId()) {
            $field->setId(uniqid(get_class($field)));
        }

        $script .= '$(document).ready( function() {' . PHP_EOL;
        if (! ($field instanceof HiddenField)) {
            $script .= '    $("#' . $field->getId() . '").blur(function() {' . PHP_EOL;
            $script .= '        d2BankNumberValidator( this );' . PHP_EOL;
            $script .= '    });' . PHP_EOL;
        }
        $script .= '    $("form#' . $form->getId() . '").bind( "validate", function( event ) {' . PHP_EOL;
        $script .= '        if( !d2BankNumberValidator( $("#' . $field->getId() . '") ) ) {' . PHP_EOL;
        $script .= '            return false;' . PHP_EOL;
        $script .= '        } else {' . PHP_EOL;
        $script .= '            return event.result;' . PHP_EOL;
        $script .= '        }' . PHP_EOL;
        $script .= '    });' . PHP_EOL;
        $script .= '});' . PHP_EOL;

        return $script;
    }

    /**
     * Set if this field is required or not.
     *
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = (bool) $required;
    }

    /**
     * Get if this field is required or not.
     *
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }
}