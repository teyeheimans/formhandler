<?php

namespace FormHandler\Field;

class TextField extends AbstractFormField
{
    protected $maxlength;
    protected $readonly;
    protected $size;
    protected $value;
    protected $type = 'text';
    protected $placeholder;

    // common used (HTML5) types
    const TYPE_COLOR     = 'color';
    const TYPE_DATE     = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATETIME_LOCAL ='datetime-local';
    const TYPE_EMAIL    = 'email';
    const TYPE_MONTH    = 'month';
    const TYPE_NUMBER    = 'number';
    const TYPE_RANGE    = 'range';
    const TYPE_SEARCH    = 'search';
    const TYPE_TEL        = 'tel';
    const TYPE_TEXT        = 'text';
    const TYPE_TIME        = 'time';
    const TYPE_URL        = 'url';
    const TYPE_WEEK        = 'week';

    public function __construct(Form &$form, $name = '')
    {
        $this -> form = $form;
        $this -> form -> addField($this);

        if (!empty($name)) {
            $this -> setName($name);
        }
    }

    /**
     * Set the name
     *
     * @param string $name
     * @return TextField
     */
    public function setName($name)
    {
        $this -> name = $name;
        $this -> setValue($this -> form -> getFieldValue($this -> name));
        return $this;
    }

    /**
     * Set the max length of this field and return the TextField reference
     *
     * @param int $maxlength
     * @return TextField
     */
    public function setMaxlength($maxlength)
    {
        $this -> maxlength = (integer)$maxlength;
        return $this;
    }

    /**
     * Set the value for type. In HTML5 new types are allowed, for example "number", "email", etc.
     * Default is still "text".
     * @return TextField
     */
    public function setType($value)
    {
        $this -> type = $value;
        return $this;
    }

    /**
     * Get the value for type. Default text
     * @return string
     */
    public function getType()
    {
        return $this -> type;
    }


    /**
     * Return the max length of this field
     *
     * @return int
     */
    public function getMaxlength()
    {
        return $this -> maxlength;
    }

    /**
     * Set if this field is readonly and return the TextField reference
     *
     * @param bool $readonly
     * @return TextField
     */
    public function setReadonly($readonly)
    {
        $this -> readonly = $readonly;
        return $this;
    }

    /**
     * Return the readonly status of this field
     *
     * @return bool
     */
    public function isReadonly()
    {
        return $this -> readonly;
    }

    /**
     * Set the size of the field and return the TextField reference
     *
     * @param int $size
     * @return TextField
     */
    public function setSize($size)
    {
        $this -> size = $size;
        return $this;
    }

    /**
     * Return the size of the field
     *
     * @return int
     */
    public function getSize()
    {
        return $this -> size;
    }

    /**
     * Set the value for this field and return the TextField reference
     *
     * @param string $value
     * @return TextField
     */
    public function setValue($value)
    {
        // trim the value we dont want leading and trailing spaces
        $this -> value = trim($value);
        return $this;
    }

    /**
     * Return the value for this field
     *
     * @return string
     */
    public function getValue()
    {
        return $this -> value;
    }

    /**
     * Set the value for placeholder
     * @param string $value
     * @return TextField
     */
    public function setPlaceholder($value)
    {
        $this -> placeholder = $value;
        return $this;
    }

    /**
     * Get the value for placeholder
     * @return string
     */
    public function getPlaceholder()
    {
        return $this -> placeholder;
    }

    /**
     * Return string representation of this field
     *
     * @return string
     */
    public function render()
    {
        $str = '<input type="'. $this -> getType().'"';

        if (!empty($this -> name)) {
            $str .= ' name="'. $this -> name .'"';
        }

        if ($this -> value != '') {
            $str .= ' value="'. htmlspecialchars($this -> value) .'"';
        }

        if (!empty($this -> size)) {
            $str .= ' size="'. $this -> size .'"';
        }

        if ($this -> disabled !== null && $this -> disabled) {
            $str .= ' disabled="disabled"';
        }

        if (!empty($this -> maxlength)) {
            $str .= ' maxlength="'. $this -> maxlength .'"';
        }

        if ($this -> readonly !== null && $this -> readonly) {
            $str .= ' readonly="readonly"';
        }

        if ($this -> placeholder) {
            $str .= ' placeholder="'. htmlspecialchars($this -> placeholder) .'"';
        }

        $str .= parent::render();
        $str .= ' />';

        return $str;
    }
}