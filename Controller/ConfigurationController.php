<?php


namespace ProductStatus\Controller;

use ProductStatus\Form\StatusModificationForm;
use ProductStatus\Model\ProductStatusQuery;
use ProductStatus\ProductStatus;
use ProductStatus\Form\StatusContentForm;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

class ConfigurationController extends BaseAdminController
{
    public function showModule()
    {
        return $this->render('module-configuration', ['module_code' => ProductStatus::DOMAIN_NAME]);
    }

    public function configPage()
    {
        return $this->render('module', ['module_code' => ProductStatus::DOMAIN_NAME]);
    }

    public function saveChanges()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ProductStatus::DOMAIN_NAME, AccessManager::UPDATE)) {
            return $response;
        }

        $url = '/admin/module/ProductStatus';

        $form = $this->createForm(StatusContentForm::getName());

        $errorMsg = null;

        try {
            $validForm = $this->validateForm($form);

            $productStatus = new \ProductStatus\Model\ProductStatus();

            if ($validForm->get('status-name')->getData() &&
                $validForm->get('status-code')->getData() &&
                $validForm->get('color')->getData() &&
                $validForm->get('info-text')->getData()
            )

            {
                $productStatus
                    ->setLocale($this->getSession()->getAdminEditionLang()->getLocale())
                    ->setTitle($validForm->get('status-name')->getData())
                    ->setCode($validForm->get('status-code')->getData())
                    ->setColor($validForm->get('color')->getData())
                    ->setDescription($validForm->get('info-text')->getData())
                    ->save();
            }

        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans('status created'),
                $errorMsg = $e->getMessage(),
                $form,
                $e
            );
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
            $errorMsg ? ['errorMsg' => $errorMsg] : null));
    }

    public function delete()
    {
        $errorMsg = null;
        $url = '/admin/module/ProductStatus';

      try{ $productId = $this->getRequest()->attributes->get('id');

        $statusToDelete = ProductStatusQuery::create()
            ->findOneById($productId);

        $statusToDelete->delete();

      } catch (\Exception $e) {
              $errorMsg = $e->getMessage();
      }

    return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
        $errorMsg ? ['errorMsg' => $errorMsg] : null));
    }

    public function edit()
    {
        $errorMsg = null;
        $url = '/admin/module/ProductStatus';
        $form = $this->createForm(StatusContentForm::getName());
        $validForm = $this->validateForm($form);

        try{ $productId = $this->getRequest()->attributes->get('id');

            $statusToEdit = ProductStatusQuery::create()
                ->findOneById($productId);

            $statusToEdit
                ->setLocale($this->getSession()->getAdminEditionLang()->getLocale())
                ->setTitle($validForm->get('status-name')->getData())
                ->setCode($validForm->get('status-code')->getData())
                ->setColor($validForm->get('color')->getData())
                ->setDescription($validForm->get('info-text')->getData())
                ->save();

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
            $errorMsg ? ['errorMsg' => $errorMsg] : null));
    }
}