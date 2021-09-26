<?php

/*
 * This file is part of the Flash Sale plugin
 *
 * Copyright(c) ECCUBE VN LAB. All Rights Reserved.
 *
 * https://www.facebook.com/groups/eccube.vn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\FlashSale\Test\Form\Type;

use Plugin\FlashSale\Entity\Condition\ProductClassIdCondition;
use Plugin\FlashSale\Entity\FlashSale;
use Plugin\FlashSale\Entity\Promotion\ProductClassPricePercentPromotion;
use Plugin\FlashSale\Entity\Rule\ProductClassRule;
use Plugin\FlashSale\Form\Type\Admin\FlashSaleType;
use Eccube\Tests\Form\Type\AbstractTypeTestCase;
use Plugin\FlashSale\Service\Operator\InOperator;

class FlashSaleTypeTest extends AbstractTypeTestCase
{
    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    public function formData()
    {
        $faker = $this->getFaker();
        $rules[] = [
            'id' => '',
            'type' => ProductClassRule::TYPE,
            'operator' => InOperator::TYPE,
            'promotion' => [
                'id' => '',
                'type' => ProductClassPricePercentPromotion::TYPE,
                'value' => 30,
            ],
            'conditions' => [
                [
                    'id' => '',
                    'type' => ProductClassIdCondition::TYPE,
                    'operator' => InOperator::TYPE,
                    'value' => 99,
                ],
            ],
        ];

        $flash_sale_admin = [
            'name' => $faker->name(),
            'description' => $faker->text(),
            'from_time' => [
                'date' => [
                    'year' => date('Y') + 1,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 0,
                    'minute' => 0,
                ],
            ],
            'to_time' => [
                'date' => [
                    'year' => date('Y') + 1,
                    'month' => 1,
                    'day' => 1,
                ],
                'time' => [
                    'hour' => 23,
                    'minute' => 59,
                ],
            ],
            'rules' => json_encode($rules),
            'status' => FlashSale::STATUS_ACTIVATED,
        ];

        return $flash_sale_admin;
    }

    public function setUp()
    {
        parent::setUp();

        $this->form = $this->formFactory
            ->createBuilder(FlashSaleType::class, null, ['csrf_protection' => false])
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData());
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidData_name_MaxLength()
    {
        $data = $this->formData();
        $data['name'] = str_repeat('A', $this->eccubeConfig['eccube_mtext_len'] + 1);
        $this->form->submit($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidData_Name_Blank()
    {
        $data = $this->formData();
        $data['name'] = '';
        $this->form->submit($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidData_Status_length()
    {
        $data = $this->formData();
        $data['status'] = 11;
        $this->form->submit($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidData_Status_text()
    {
        $data = $this->formData();
        $data['status'] = 'status';
        $this->form->submit($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidData_FromTime_ToTime()
    {
        $data = $this->formData();
        $data['from_time'] = [
            'date' => [
                'year' => date('Y') + 1,
                'month' => 1,
                'day' => 1,
            ],
            'time' => [
                'hour' => 0,
                'minute' => 0,
            ],
        ];
        $data['to_time'] = [
            'date' => [
                'year' => date('Y') + 0,
                'month' => 1,
                'day' => 1,
            ],
            'time' => [
                'hour' => 23,
                'minute' => 59,
            ],
        ];
        $this->form->submit($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidData_Rules_null()
    {
        $data = $this->formData();
        $data['rules'] = '';
        $this->form->submit($data);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidData_Rules_Param()
    {
        $data = $this->formData();
        $rules[] = [
            'id' => '',
            'type' => 'product_class',
            'operator' => 'in',
            'promotion' => [
                'id' => '',
                'type' => 'amount',
                'attribute' => 'percent',
                'value' => 30,
            ],
        ];
        $data['rules'] = json_encode($rules);
        $this->form->submit($data);
        $this->assertFalse($this->form->isValid());
    }
}
