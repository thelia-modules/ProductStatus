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
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

class ConfigurationController extends BaseAdminController
{
    public function retrieve($object, $alternateQuery = null)
    {
       $retrievedObject = ProductStatusQuery::create()->findOne($object) ;

        if ($alternateQuery = true)
        {
            $retrievedObject = ProductProductStatusQuery::create()->findOne($object);
        }
        return $retrievedObject;
    }

    public function saveChanges(Session $session)
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

            if ($this->retrieve($code)) {
                $errorMsg = ProductStatus::CODE_EXIST_MESSAGE;
                $session->getFlashBag()->add('status-exist-error', $errorMsg);

                return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
                    ['errorMsg' => $errorMsg]));
            }

            $productStatus
                ->setLocale($session->getAdminEditionLang()->getLocale())
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

    public function delete(Request $request)
    {
        $errorMsg = null;
        $url = '/admin/module/ProductStatus';

        try{ $productId = $request->attributes->get('id');

            $this->retrieve($productId)->delete();

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
        }

    return $this->generateRedirect(URL::getInstance()->absoluteUrl($url,
        $errorMsg ? ['errorMsg' => $errorMsg] : null));
    }

    public function edit(Request $request, Session $session)
    {
        $errorMsg = null;
        $url = '/admin/module/ProductStatus';
        $form = $this->createForm(StatusContentForm::getName());
        $validForm = $this->validateForm($form);

        try{ $productId = $request->attributes->get('id');

            $statusToEdit = $this->retrieve($productId);

            $statusToEdit
                ->setLocale($session->getAdminEditionLang()->getLocale())
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

        try{ $productId = $request->attributes->get('product_id');

            $url = "/admin/products/update?product_id=$productId&current_tab=modules#refresh_anchor";

            $statusToEdit = $this->retrieve($productId, true);

            if(!$statusToEdit) {
                $newEntry = new ProductProductStatus();
               $statusToEdit = $newEntry->setProductId($productId);
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