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
                'required' => true,
                'label' => $translator->trans('The status name in FrontOffice', [], ProductStatus::DOMAIN_NAME),
                'label_attr' => [
                    'help' => Translator::getInstance()->trans('Title of the status. Will be displayed in frontOffice', [], ProductStatus::DOMAIN_NAME),
                ],
            ]
        )

            ->add(
                'bo-status-name',
                'text',
                [
                    'required' => false,
                    'label' => $translator->trans('The status name in BackOffice', [], ProductStatus::DOMAIN_NAME),
                    'label_attr' => [
                        'help' => Translator::getInstance()->trans('Title of the status displayed in back-office. Leave empty to set the front-office title', [], ProductStatus::DOMAIN_NAME),
                    ],
                ]
            )

            ->add(
            'status-code',
            'text',
            [
                'required' => true,
                'label' => $translator->trans('The status code', [], ProductStatus::DOMAIN_NAME),
                'label_attr' => [
                    'help' => Translator::getInstance()->trans('It must be unique', [], ProductStatus::DOMAIN_NAME),
                ],
            ]
        )

            ->add(
            'info-text',
            'textarea',
            [
                'required' => false,
                'label' => $translator->trans('The status description', [], ProductStatus::DOMAIN_NAME),
                'label_attr' => [
                    'help' => Translator::getInstance()->trans('The text displayed in frontOffice', [], ProductStatus::DOMAIN_NAME),
                ],
            ]
        )

            ->add(
            'color',
            'text',
            [
                'required' => true,
                'label' => Translator::getInstance()->trans('Status color', [], ProductStatus::DOMAIN_NAME),
                'label_attr' => [
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