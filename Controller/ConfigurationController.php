<?php


namespace ProductStatus\Controller;

use ProductStatus\Events\UpdateProductStatusEvent;
use ProductStatus\Form\EditProductStatusForm;
use ProductStatus\Model\ProductProductStatus;
use ProductStatus\Model\ProductProductStatusQuery;
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
    const URL = '/admin/module/ProductStatus';

    public function createStatus()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, ProductStatus::DOMAIN_NAME, AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(StatusContentForm::getName());
        $errorMessage = null;

        try {
            $validForm = $this->validateForm($form);
            $productStatus = new \ProductStatus\Model\ProductStatus();
            $code = mb_strtolower($validForm->get('status-code')->getData());

            if (ProductStatusQuery::create()->findOneByCode($code)) {
                $errorMessage = ProductStatus::CODE_EXIST_MESSAGE;
                $this->getSession()->getFlashBag()->add('status-exist-error', $errorMessage);

                return $this->generateRedirect(URL::getInstance()->absoluteUrl(self::URL,
                    ['errorMessage' => $errorMessage]));
            }

            $productStatus
                ->setLocale($this->getSession()->getAdminEditionLang()->getLocale())
                ->setTitle(ucfirst($validForm->get('status-name')->getData()))
                ->setCode($code)
                ->setColor($validForm->get('color')->getData())
                ->setDescription(lcfirst($validForm->get('info-text')->getData()))
                ->setBackofficeTitle(ucfirst($validForm->get('bo-status-name')->getData() ?? $validForm->get('status-name')->getData()))
                ->save();

        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans('status created'),
                $errorMessage = $e->getMessage(),
                $form,
                $e
            );
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl(self::URL,
            $errorMessage ? ['errorMessage' => $errorMessage] : null));
    }

    public function deleteStatus()
    {
        $errorMessage = null;

        try{ $productId = $this->getRequest()->attributes->get('id');

            ProductStatusQuery::create()
                ->findOneById($productId)
                ->delete();

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl(self::URL,
            $errorMessage ? ['errorMessage' => $errorMessage] : null));
    }

    public function editStatus()
    {
        $errorMessage = null;
        $form = $this->createForm(StatusContentForm::getName());
        $validForm = $this->validateForm($form);

        try{ $productId = $this->getRequest()->attributes->get('id');

            $statusToEdit = ProductStatusQuery::create()
                ->findOneById($productId);

            $statusToEdit
                ->setLocale($this->getSession()->getAdminEditionLang()->getLocale())
                ->setTitle(ucfirst($validForm->get('status-name')->getData()))
                ->setCode(mb_strtolower($validForm->get('status-code')->getData()))
                ->setColor($validForm->get('color')->getData())
                ->setDescription(lcfirst($validForm->get('info-text')->getData()))
                ->setBackofficeTitle(ucfirst($validForm->get('bo-status-name')->getData() ?? $validForm->get('status-name')->getData()))
                ->save();

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl(self::URL,
            $errorMessage ? ['errorMessage' => $errorMessage] : null));
    }

    public function editProductStatus()
    {
        $errorMessage = null;
        $form = $this->createForm(EditProductStatusForm::getName());
        $validForm = $this->validateForm($form);

        try{ $productId = $this->getRequest()->attributes->get('product_id');

            $url = "/admin/products/update?product_id=$productId&current_tab=modules#refresh_anchor";

            $statusToEdit = ProductProductStatusQuery::create()
                ->findOneByProductId($productId);

            if(!$statusToEdit) {
                $newEntry = new ProductProductStatus();

                $statusToEdit = $newEntry->setProductId($productId);
            }

            $statusToEdit
                ->setProductStatusId($validForm->get('product_status_id')->getData())
                ->save();

            $event = new UpdateProductStatusEvent();
            $event->setProduct($statusToEdit->getProduct());
            $event->setProductProductStatus($statusToEdit);
            $this->getDispatcher()->dispatch(UpdateProductStatusEvent::PRODUCT_STATUS_UPDATE, $event);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
            $errorMessage ? ['errorMessage' => $errorMessage] : null));
    }
}
