<?php
namespace FormHandler\Field;

/**
 * With this class you can handle an UploadField.
 *
 * When you add this to a form, the form's enctype will be set to multipart/form-data automatically.
 * Also, a hidden field called MAX_FILE_SIZE is added to the form object to let the browser
 * know the max files we can handle.
 *
 * Validation can be done with the UploadValidator. For more information about the UploadValidator,
 * {@see form/validator/UploadValidator.php}
 *
 * After uploading, the getValue() method returns an array like this:
 *
 * <code>
 * Array
 * (
 * [name] => Map3.xlsx
 * [type] => application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
 * [tmp_name] => C:\Windows\Temp\php7675.tmp
 * [error] => 0
 * [size] => 11784
 * )
 * </code>
 *
 * You can enable multiple upload files like this:
 * <code>
 * $form -> uploadField('file')
 * -> setMultiple( true );
 * </code>
 *
 * When setting an uploadfield to allow multiple file uploads, it's name will automatically be changed to
 * include two square brackets. So the name of the field in the example above will become "file[]".
 *
 * After submitting a form with an uploadfield accepting multiple files, you will receive a result
 * from the getValue() method like this:
 *
 * <code>
 * Array
 * (
 * [name] => Array
 * (
 * [0] => Map3.xlsx
 * [1] => payments-AT.xml
 * [2] => status.xml
 * )
 *
 * [type] => Array
 * (
 * [0] => application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
 * [1] => text/xml
 * [2] => text/xml
 * )
 *
 * [tmp_name] => Array
 * (
 * [0] => C:\Windows\Temp\phpA65C.tmp
 * [1] => C:\Windows\Temp\phpA66D.tmp
 * [2] => C:\Windows\Temp\phpA66E.tmp
 * )
 *
 * [error] => Array
 * (
 * [0] => 0
 * [1] => 0
 * [2] => 0
 * )
 *
 * [size] => Array
 * (
 * [0] => 11784
 * [1] => 17934
 * [2] => 9968
 * )
 *
 * )
 * </code>
 *
 * After uploading a file, you can use the UploadHelper {@see form/helpers/UploadHelper.php} for the
 * most common actions (like moving a uploaded file, do some image mutations, etc).
 */
class UploadField extends AbstractFormField
{

    protected $size;

    protected $value;

    protected $accept;

    protected $multiple = false;
 // allow multiple files to be uploaded by 1 uploadfield?
    public function __construct(Form &$form, $name = '')
    {
        $this->form = $form;
        $this->form->setEnctype(Form::ENCTYPE_MULTIPART);
        $this->form->addField($this);

        if (! empty($name)) {
            $this->setName($name);
        }
    }

    /**
     * Returns true if the form was submited and there was a file uploaded.
     *
     * @return boolean
     */
    public function isUploaded()
    {
        if ($this->form->isSubmitted() && is_array($this->value) && $this->value['error'] == UPLOAD_ERR_OK) {
            return true;
        }

        return false;
    }

    /**
     * Set the name
     *
     * @param string $name
     * @return UploadField
     */
    public function setName($name)
    {
        $this->name = $name;
        if (isset($_FILES) && array_key_exists($name, $_FILES)) {
            $this->setValue($_FILES[$name]);
        }
        return $this;
    }

    /**
     * Specifies the types of files that can be submitted through a file upload
     * Example: text/html, image/jpeg, audio/mpeg, video/quicktime, text/css, and text/javascript
     *
     * @param string $mimeType
     */
    public function setAccept($mimeType)
    {
        $this->accept = $mimeType;
        return $this;
    }

    /**
     * Get the types of files that can be submitted through a file upload
     *
     * @return string
     */
    public function getAccept()
    {
        return $this->accept;
    }

    /**
     * Set the size of the field and return the TextField reference
     *
     * @param int $size
     * @return TextField
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Return the size of the field
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * allow multiple files to be uploaded by 1 uploadfield?
     * Set the value for multiple
     *
     * @return UploadField
     */
    public function setMultiple($value)
    {
        $this->multiple = $value;
        return $this;
    }

    /**
     * allow multiple files to be uploaded by 1 uploadfield?
     * Get the value for multiple
     *
     * @return string
     */
    public function getMultiple()
    {
        return $this->multiple;
    }

    /**
     * Set the value for this field and return the TextField reference
     *
     * @param string $value
     * @return TextField
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Return the value for this field
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Return string representation of this field
     *
     * @return string
     */
    public function render()
    {
        $str = '<input type="file"';

        if (! empty($this->name)) {
            $str .= ' name="' . $this->name;

            // Can we upload multiple files?
            if ($this->multiple) {
                // then make sure to make an array of the result
                if (! preg_match('/\[\]/', $this->name)) {
                    $str .= '[]';
                }
            }

            $str .= '"';
        }

        if (! empty($this->size)) {
            $str .= ' size="' . $this->size . '"';
        }

        if (! empty($this->accept)) {
            $str .= ' accept="' . $this->accept . '"';
        }

        if ($this->multiple) {
            $str .= ' multiple=""';
        }

        if ($this->disabled !== null && $this->disabled) {
            $str .= ' disabled="disabled"';
        }

        $str .= parent::render();
        $str .= ' />';

        return $str;
    }
}