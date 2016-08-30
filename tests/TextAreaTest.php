<?php
namespace FormHandler\Tests;

use FormHandler\Form;
use PHPUnit\Framework\TestCase;

/**
 * Test Passfield.
 * User: teye
 * Date: 23-08-16
 * Time: 16:23
 */
class TextAreaTest extends TestCase
{
    public function testTextAreaTest()
    {
        $form = new Form();
        $field = $form -> textArea('msg');

        $field -> setPlaceholder('Enter a message');
        $this -> assertEquals('Enter a message', $field -> getPlaceholder());
        $this -> assertEquals('msg', $field -> getName());

        $field -> setCols(10);
        $field -> setRows(10);
        $this -> assertEquals([10, 10], [$field -> getRows(), $field -> getCols()]);

        $this -> assertFalse($field -> isDisabled());
        $this -> assertFalse($field -> isReadonly());

        $field -> setDisabled(true);
        $field -> setReadonly(true);

        $this -> assertTrue($field -> isDisabled());
        $this -> assertTrue($field -> isReadonly());

        $field -> setMaxlength(500);
        $this -> assertEquals(500, $field -> getMaxlength());

        $field -> setValue('Piet');
        $this -> assertEquals('Piet', $field -> getValue());

        $this->expectOutputRegex(
            "/<textarea cols=\"(\d+)\" rows=\"(\d+)\" name=\"(.*?)\" ".
            "disabled=\"disabled\" maxlength=\"(\d+)\" readonly=\"readonly\" ".
            "placeholder=\"(.*?)\">(.*?)<\/textarea>/i",
            'Check html tag'
        );
        echo $field;
    }
}