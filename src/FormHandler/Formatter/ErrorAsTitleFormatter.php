<?php
namespace FormHandler\Formatter;

/**
 * ErrorAsTitleFormatter class.
 *
 * This class renders all the fields and elements.
 *
 * For checkboxes and radio buttons, it renders the <label> tags
 * directly after the field.
 *
 * All form fields will be checked if they are invalid. If so,
 * the error messages will be added in the title tag of the field.
 * Error messages are seperated by a <br />.
 *
 * Hidden fields are automatically placed directly after the <form> tag,
 * surrounded by <ins> tag to make sure the html is valid.
 */
class ErrorAsTitleFormatter extends PlainFormatter
{

    /**
     * Format the element and return it's new layout
     *
     * @param Element $element
     */
    public function format(Element $element)
    {
        // if the element is a form field, add the errors in the title tag
        if ($element instanceof AbstractFormField && $element->getForm()->isSubmitted() && ! $element->isValid()) {
            $errors = $element->getErrorMessages();
            // if there are any errors to show...
            if ($errors) {
                $element->setTitle(implode("<br />\n", $errors));
            }
        }

        // if a method exists for this element, then use that one
        $className = get_class($element);
        $className = strtolower(substr($className, 0, 1)) . substr($className, 1);

        if (method_exists($this, $className)) {
            $html = $this->$className($element);
        }  // if form field
else
            if ($element instanceof AbstractFormField) {
                $html = $this->formField($element);
            }  // in case that the form class was overwritten...
else
                if ($element instanceof Form && method_exists($this, 'form')) {
                    $html = $this->form($element);
                }  // a "normal" element, like a submitbutton or such
else {
                    $html = $element->render();
                }

        return $html;
    }
}