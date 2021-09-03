<?php


namespace ProductStatus\Controller;

use ProductStatus\Form\EditProductStatusForm;
use ProductStatus\Model\ProductProductStatus;
use ProductStatus\Model\ProductProductStatusQuery;
use ProductStatus\Model\ProductStatusQuery;
use ProductStatus\ProductStatus;
use ProductStatus\Form\StatusContentForm;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

class ConfigurationController extends BaseAdminController
{
    public function saveChanges()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ProductStatus::DOMAIN_NAME, AccessManager::UPDATE)) {
            return $response;
        }

        $url = '/admin/module/ProductStatus';
        $form = $this->createForm(StatusContentForm::getName());
        $errorMsg = null;

        if (null !== $response = $this->checkAuth(
                [AdminResources::MODULE],
                [ProductStatus::DOMAIN_NAME],
                AccessManager::UPDATE)) {
            return $response;
        }

        try {
            $validForm = $this->validateForm($form);
            $productStatus = new \ProductStatus\Model\ProductStatus();

            $code = lcfirst($validForm->get('status-code')->getData());

            if (ProductStatusQuery::create()->findOneByCode($code)) {
                $errorMsg = ProductStatus::CODE_EXIST_MESSAGE;
                $this->getSession()->getFlashBag()->add('status-exist-error', $errorMsg);

                return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
                    ['errorMsg' => $errorMsg]));
            }

            $productStatus
                ->setLocale($this->getSession()->getAdminEditionLang()->getLocale())
                ->setTitle(ucfirst($validForm->get('status-name')->getData()))
                ->setCode(lcfirst($validForm->get('status-code')->getData()))
                ->setColor($validForm->get('color')->getData())
                ->setDescription(lcfirst($validForm->get('info-text')->getData()))
                ->save();

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
                ->setTitle(ucfirst($validForm->get('status-name')->getData()))
                ->setCode(lcfirst($validForm->get('status-code')->getData()))
                ->setColor($validForm->get('color')->getData())
                ->setDescription(lcfirst($validForm->get('info-text')->getData()))
                ->save();

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
            $errorMsg ? ['errorMsg' => $errorMsg] : null));
    }

    public function editProductStatus(Request $request)
    {
        $errorMsg = null;
        $form = $this->createForm(EditProductStatusForm::getName());
        $validForm = $this->validateForm($form);

        try{ $productId = $this->getRequest()->attributes->get('product_id');

            $url = "/admin/products/update?product_id=$productId&current_tab=modules";

            $statusToEdit = ProductProductStatusQuery::create()
                ->findOneByProductId($productId);

            if(!$statusToEdit) {
                $newEntryIfThereIsNone = new ProductProductStatus();
               $statusToEdit = $newEntryIfThereIsNone->setProductId($productId);
            }

            $statusToEdit
                ->setProductStatusId($validForm->get('product_status_id')->getData())
                ->save();

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
            $errorMsg ? ['errorMsg' => $errorMsg] : null));
    }
}