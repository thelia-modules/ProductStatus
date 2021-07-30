<?php


namespace ProductStatus\Form;

use ProductStatus\ProductStatus;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class StatusContentForm extends BaseForm
{
    protected function buildForm()
    {
        $translator = Translator::getInstance();
        $this->formBuilder
            ->add(
                'status-name',
                'text',
                [
                    'required' => false,
                    'label' => $translator->trans('The status name', [], ProductStatus::DOMAIN_NAME),
                    'label_attr' => ['for' => 'status-name'],
                ]
            )

            ->add(
                'status-code',
                'text',
                [
                    'required' => false,
                    'label' => $translator->trans('The status code', [], ProductStatus::DOMAIN_NAME),
                    'label_attr' => ['for' => 'status-code'],
                ]
            )

            ->add(
            'info-text',
            'textarea',
            [
                'required' => false,
                'label' => $translator->trans('The status description', [], ProductStatus::DOMAIN_NAME),
                'label_attr' => ['for' => 'info-text'],
                ]
            )

            ->add(
                'color',
                'text',
                [
                    'required' => false,
                    'label' => Translator::getInstance()->trans('Status color', [], ProductStatus::DOMAIN_NAME),
                    'label_attr' => [
                        'for' => 'title',
                        'help' => Translator::getInstance()->trans('Choose a color', [], ProductStatus::DOMAIN_NAME),
                    ],
                    'attr' => [
                        'placeholder' => '#dbbf7c',
                    ],
                ]
            );
    }


    public function getName() : string
    {
        return 'productstatus_content';
    }
}