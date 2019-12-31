<?php

/**
 * @see       https://github.com/laminas/laminas-form for the canonical source repository
 * @copyright https://github.com/laminas/laminas-form/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-form/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Form\View\Helper;

use Laminas\Captcha;
use Laminas\Form\Element;
use Laminas\Form\View\Helper\FormElement as FormElementHelper;
use Laminas\Form\View\HelperConfig;
use Laminas\View\Helper\Doctype;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit_Framework_TestCase as TestCase;

class FormElementTest extends TestCase
{
    public $helper;
    public $renderer;

    public function setUp()
    {
        $this->helper = new FormElementHelper();

        Doctype::unsetDoctypeRegistry();

        $this->renderer = new PhpRenderer;
        $helpers = $this->renderer->getHelperPluginManager();
        $config  = new HelperConfig();
        $config->configureServiceManager($helpers);

        $this->helper->setView($this->renderer);
    }

    public function getInputElements()
    {
        return array(
            array('text'),
            array('password'),
            array('checkbox'),
            array('radio'),
            array('submit'),
            array('reset'),
            array('file'),
            array('hidden'),
            array('image'),
            array('button'),
            array('number'),
            array('range'),
            array('date'),
            array('color'),
            array('search'),
            array('tel'),
            array('email'),
            array('url'),
            array('datetime'),
            array('datetime-local'),
            array('month'),
            array('week'),
            array('time'),
        );
    }

    /**
     * @dataProvider getInputElements
     */
    public function testRendersExpectedInputElement($type)
    {
        if ($type === 'radio') {
            $element = new Element\Radio('foo');
        } elseif ($type === 'checkbox') {
            $element = new Element\Checkbox('foo');
        } elseif ($type === 'select') {
            $element = new Element\Select('foo');
        } else {
            $element = new Element('foo');
        }

        $element->setAttribute('type', $type);
        $element->setAttribute('options', array('option' => 'value'));
        $element->setAttribute('src', 'http://zend.com/img.png');
        $markup  = $this->helper->render($element);

        $this->assertContains('<input', $markup);
        $this->assertContains('type="' . $type . '"', $markup);
    }

    public function getMultiElements()
    {
        return array(
            array('radio', 'input', 'type="radio"'),
            array('multi_checkbox', 'input', 'type="checkbox"'),
            array('select', 'option', '<select'),
        );
    }

    /**
     * @dataProvider getMultiElements
     * @group multi
     */
    public function testRendersMultiElementsAsExpected($type, $inputType, $additionalMarkup)
    {
        if ($type === 'radio') {
            $element = new Element\Radio('foo');
            $this->assertEquals('radio', $element->getAttribute('type'));
        } elseif ($type === 'multi_checkbox') {
            $element = new Element\MultiCheckbox('foo');
            $this->assertEquals('multi_checkbox', $element->getAttribute('type'));
        } elseif ($type === 'select') {
            $element = new Element\Select('foo');
            $this->assertEquals('select', $element->getAttribute('type'));
        } else {
            $element = new Element('foo');
        }
        $element->setAttribute('type', $type);
        $element->setValueOptions(array(
            'value1' => 'option',
            'value2' => 'label',
            'value3' => 'last',
        ));
        $element->setAttribute('value', 'value2');
        $markup  = $this->helper->render($element);

        $this->assertEquals(3, substr_count($markup, '<' . $inputType), $markup);
        $this->assertContains($additionalMarkup, $markup);
        if ($type == 'select') {
            $this->assertRegexp('#value="value2"[^>]*?(selected="selected")#', $markup);
        }
    }

    public function testRendersCaptchaAsExpected()
    {
        $this->markTestSkipped('Unable to run form captcha tests due to dependency on test asset from laminas-captcha');
        $captcha = new Captcha\Dumb(array(
            'sessionClass' => 'LaminasTest\Captcha\TestAsset\SessionContainer',
        ));
        $element = new Element\Captcha('foo');
        $element->setCaptcha($captcha);
        $markup = $this->helper->render($element);

        $this->assertContains($captcha->getLabel(), $markup);
    }

    public function testRendersCsrfAsExpected()
    {
        $element   = new Element\Csrf('foo');
        $inputSpec = $element->getInputSpecification();
        $hash = '';

        foreach ($inputSpec['validators'] as $validator) {
            $class = get_class($validator);
            switch ($class) {
                case 'Laminas\Validator\Csrf':
                    $hash = $validator->getHash();
                    break;
                default:
                    break;
            }
        }

        $markup    = $this->helper->render($element);

        $this->assertRegexp('#<input[^>]*(type="hidden")#', $markup);
        $this->assertRegexp('#<input[^>]*(value="' . $hash . '")#', $markup);
    }

    public function testRendersTextareaAsExpected()
    {
        $element = new Element('foo');
        $element->setAttribute('type', 'textarea');
        $element->setAttribute('value', 'Initial content');
        $markup  = $this->helper->render($element);

        $this->assertContains('<textarea', $markup);
        $this->assertContains('>Initial content<', $markup);
    }

    public function testRendersCollectionAsExpected()
    {
        $element = new Element\Collection();
        $element->setLabel('foo');

        $markup  = $this->helper->render($element);
        $this->assertContains('<legend>foo</legend>', $markup);
    }

    public function testRendersButtonAsExpected()
    {
        $element = new Element\Button('foo');
        $element->setLabel('My Button');
        $markup  = $this->helper->render($element);

        $this->assertContains('<button', $markup);
        $this->assertContains('>My Button<', $markup);
    }

    public function testInvokeWithNoElementChainsHelper()
    {
        $element = new Element('foo');
        $this->assertSame($this->helper, $this->helper->__invoke());
    }
}
