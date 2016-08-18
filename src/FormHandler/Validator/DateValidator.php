<?php
namespace FormHandler\Validator;

/**
 * This validator will validate a field and make sure it is a proper date.
 */
class DateValidator extends AbstractValidator
{

    protected $required;

    /**
     * Var to remember if the value was valid or not
     *
     * @var boolean
     */
    protected $valid = null;

    /**
     *
     * @param string $functionName
     */
    public function __construct($required = true, $message = null)
    {
        if ($message === null) {
            $message = dgettext('d2frame', 'This value is incorrect.');
        }

        $this->setRequired($required);
        $this->setErrorMessage($message);
    }

    /**
     * Check if the given field is valid or not.
     *
     * @return boolean
     */
    public function isValid()
    {
        $value = $this->field->getValue();

        if ($this->valid === null) {

            if ($value == '' && $this->required == false) {
                $this->valid = true;
                return $this->valid;
            }

            $parsed_date = date_parse($value);

            if ($parsed_date['warning_count'] == 0 && $parsed_date['error_count'] == 0 && isset($parsed_date['year']) && isset($parsed_date['month'])) {
                $this->valid = true;
                return $this->valid;
            }

            $this->valid = false;
            return $this->valid;
        }

        return $this->valid;
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