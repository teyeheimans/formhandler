<?php
namespace FormHandler\Tests\Field;

use FormHandler\Form;

/**
 * Created by PhpStorm.
 * User: teye
 * Date: 23-08-16
 * Time: 16:23
 */
class TextFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testTextField()
    {
        $form = new Form();
        $field = $form->textField('name');
        $field->setPlaceholder('Enter your name');

        $this->assertEquals('Enter your name', $field->getPlaceholder());
        $this->assertEquals('name', $field->getName());

        $this->assertEquals('text', $field->getType());
        $field->setType('tel');
        $this->assertEquals('tel', $field->getType());

        $field->setSize(2);
        $this->assertEquals(2, $field->getSize());

        $this->assertFalse($field->isDisabled());
        $this->assertFalse($field->isReadonly());

        $field->setDisabled(true);
        $field->setReadonly(true);

        $this->assertTrue($field->isDisabled());
        $this->assertTrue($field->isReadonly());

        $field->setValue('Piet');
        $this->assertEquals('Piet', $field->getValue());

        $field->setMaxlength(10);
        $this->assertEquals(10, $field->getMaxlength());

        $field->setAttribute('data-country', 'nl');
        $this->assertEquals('nl', $field->getAttribute('data-country'));
    }
}
