<?php


namespace ProductStatus\Form;

use ProductStatus\Model\ProductStatusQuery;
use ProductStatus\ProductStatus;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class EditProductStatusForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add(
                'product_status_id',
                IntegerType::class,
                [
                    'required' => false,
                ]
            );
    }

    public static function getName() : string
    {
        return 'edit_product_status';
    }

}