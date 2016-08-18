<?php
namespace FormHandler\Validator;

/**
 */
class SelectFieldValidator extends AbstractValidator
{

    protected $min = null;

    protected $max = null;

    protected $required = true;

    /**
     * Create a new Select Field validator
     *
     * Possible default values can be given directly (all are optional)
     *
     * @param int $min
     * @param int $max
     * @param boolean $required
     * @param string $message
     */
    public function __construct($min = null, $max = null, $required = true, $message = null)
    {
        if ($message === null) {
            $message = dgettext('d2frame', 'This value is incorrect.');
        }

        $this->setMax($max);
        $this->setMin($min);
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

        // ArrayObject? Get normal array
        if ($value instanceof ArrayObject) {
            $value = $value->getArrayCopy();
        }
        // We got an object? Strange.. return false
        if (is_object($value)) {
            return false;
        }
        // Convert value to array if needed
        if (! is_array($value)) {
            $value = array(
                $value
            );
        }

        $value = array_filter($value);

        // required but not given
        if ($this->required && empty($value)) {
            return false;
        }  // if the field is not required and the value is empty, then it's also valid
else
            if (! $this->required && empty($value)) {
                return true;
            }

        $count = count($value);

        // check if the value is not to low.
        if ($this->min !== null) {
            if ($count < $this->min) {
                return false;
            }
        }

        // check if the value is not to high.
        if ($this->max !== null) {
            if ($count > $this->max) {
                return false;
            }
        }

        return true;
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

    /**
     * Set the max length number which the value of this field can be.
     * The $max number itsself is also allowed.
     * Set to null to have no max.
     *
     * @param int $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * Set the minimum value of this field.
     * The $min value
     * is also allowed.
     * Set to null to have no min.
     *
     * @param int $min
     */
    public function setMin($min)
    {
        $this->min = $min;
    }

    /**
     * Return the max allowed value
     *
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Return the min allowed value.
     *
     * @return int
     */
    public function getMin()
    {
        return $this->min;
    }
}