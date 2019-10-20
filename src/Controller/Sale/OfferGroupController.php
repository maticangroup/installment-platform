<?php

namespace App\Controller\Sale;

use Matican\ModelSerializer;
use Matican\Models\Repository\ProductModel;
use Matican\Models\Sale\OfferGroupModel;
use Matican\Models\Sale\OfferGroupStatusModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Inventory;
use Matican\Core\Entities\Sale;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/sale/offer-group", name="sale_offer_group")
 */
class OfferGroupController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fetchAll()
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_all);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_update);
        $canRead = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_fetch);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_change_status);

        if (!$canSeeAll) {
            return $this->redirect($this->generateUrl('default'));
        }


        $request = new Req(Servers::Sale, Sale::OfferGroup, 'all');
        $response = $request->send();

        /**
         * @var $offerGroups OfferGroupModel[]
         */
        $offerGroups = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $offerGroup) {
                $offerGroups[] = ModelSerializer::parse($offerGroup, OfferGroupModel::class);
            }
        }

        return $this->render('sale/offer_group/list.html.twig', [
            'controller_name' => 'OfferGroupController',
            'offerGroups' => $offerGroups,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canUpdate' => $canUpdate,
            'canRead' => $canRead,
            'canChangeStatus' => $canChangeStatus,
        ]);
    }


    /**
     * @Route("/create", name="_sale_offer_group_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {

        $canCreate = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_all);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_update);
        $canRead = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_fetch);
        $canChangeStatus = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_change_status);

        if (!$canCreate) {
            return $this->redirect($this->generateUrl('default'));
        }

        $inputs = $request->request->all();
        /**
         * @var $offerGroupModel OfferGroupModel
         */
        $offerGroupModel = ModelSerializer::parse($inputs, OfferGroupModel::class);
        if (!empty($inputs)) {
            $request = new Req(Servers::Sale, Sale::OfferGroup, 'new');
            $request->add_instance($offerGroupModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $offerGroupModel OfferGroupModel
                 */
                $offerGroupModel = ModelSerializer::parse($response->getContent(), OfferGroupModel::class);
                $this->addFlash('s', $response->getStatus());
                return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_edit', ['id' => $offerGroupModel->getOfferGroupId()]));
            } else {
                $this->addFlash('f', $response->getStatus());
            }
        }

        $allOfferGroupsRequest = new Req(Servers::Sale, Sale::OfferGroup, 'all');
        $allOfferGroupResponse = $allOfferGroupsRequest->send();
        /**
         * @var $offerGroups OfferGroupModel[]
         */
        $offerGroups = [];
        foreach ($allOfferGroupResponse->getContent() as $offerGroup) {
            $offerGroups[] = ModelSerializer::parse($offerGroup, OfferGroupModel::class);
        }
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_all)) {
            $offerGroups = [];
        }

        return $this->render('sale/offer_group/create.html.twig', [
            'controller_name' => 'OfferGroupController',
            'offerGroupModel' => $offerGroupModel,
            'offerGroups' => $offerGroups,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canUpdate' => $canUpdate,
            'canRead' => $canRead,
            'canChangeStatus' => $canChangeStatus,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="_sale_offer_group_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_update);
        $canAddProduct = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_add_product);
        $canRemoveProduct = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_remove_product);

        if (!$canUpdate) {
            return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_create'));
        }
        $inputs = $request->request->all();

        /**
         * @var $offerGroupModel OfferGroupModel
         */
        $offerGroupModel = ModelSerializer::parse($inputs, OfferGroupModel::class);
        $offerGroupModel->setOfferGroupId($id);
        $request = new Req(Servers::Sale, Sale::OfferGroup, 'fetch');
        $request->add_instance($offerGroupModel);
        $response = $request->send();
        $offerGroupModel = ModelSerializer::parse($response->getContent(), OfferGroupModel::class);

        if (!empty($inputs)) {
            if (!AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_update)) {
                return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_edit', ['id' => $offerGroupModel->getOfferGroupId()]));

            }
            /**
             * @var $offerGroupModel OfferGroupModel
             */
            $offerGroupModel = ModelSerializer::parse($inputs, OfferGroupModel::class);
            $offerGroupModel->setOfferGroupId($id);
            $request = new Req(Servers::Sale, Sale::OfferGroup, 'update');
            $request->add_instance($offerGroupModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $offerGroupModel OfferGroupModel
                 */
                $offerGroupModel = ModelSerializer::parse($response->getContent(), OfferGroupModel::class);
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_edit', ['id' => $offerGroupModel->getOfferGroupId()]));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        $allShelvesProductsRequest = new Req(Servers::Inventory, Inventory::Shelve, 'get_shelves_products');
        $allShelvesProductsResponse = $allShelvesProductsRequest->send();

        /**
         * @var $shelvesProducts ProductModel[]
         */
        $shelvesProducts = [];
        if ($allShelvesProductsResponse->getContent()) {
            foreach ($allShelvesProductsResponse->getContent() as $product) {
                $shelvesProducts[] = ModelSerializer::parse($product, ProductModel::class);
            }
        }

        /**
         * @var $selectedProducts ProductModel[]
         */
        $selectedProducts = [];
        if ($offerGroupModel->getOfferGroupProducts()) {
            foreach ($offerGroupModel->getOfferGroupProducts() as $product) {
                $selectedProducts[] = ModelSerializer::parse($product, ProductModel::class);
            }
        }

        $selectedProductsIds = [];
        foreach ($selectedProducts as $product) {
            $selectedProductsIds[] = $product->getProductId();
        }
        foreach ($shelvesProducts as $product) {
            if (in_array($product->getProductId(), $selectedProductsIds)) {
                $product->setProductIsDisabled(true);
            }
        }

        if (!AuthUser::if_is_allowed(ServerPermissions::inventory_shelve_get_shelves_products)) {
            $shelvesProducts = [];
        }
        return $this->render('sale/offer_group/edit.html.twig', [
            'controller_name' => 'OfferGroupController',
            'offerGroupModel' => $offerGroupModel,
            'shelvesProducts' => $shelvesProducts,
            'selectedProducts' => $selectedProducts,
            'canUpdate' => $canUpdate,
            'canAddProduct' => $canAddProduct,
            'canRemoveProduct' => $canRemoveProduct,
        ]);
    }

    /**
     * @Route("/read/{id}", name="_sale_offer_group_read")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($id, Request $request)
    {

        $canRead = AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_fetch);

        if (!$canRead) {
            return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_create'));
        }
        $inputs = $request->request->all();

        /**
         * @var $offerGroupModel OfferGroupModel
         */
        $offerGroupModel = ModelSerializer::parse($inputs, OfferGroupModel::class);
        $offerGroupModel->setOfferGroupId($id);
        $request = new Req(Servers::Sale, Sale::OfferGroup, 'fetch');
        $request->add_instance($offerGroupModel);
        $response = $request->send();
        $offerGroupModel = ModelSerializer::parse($response->getContent(), OfferGroupModel::class);


        /**
         * @var $selectedProducts ProductModel[]
         */
        $selectedProducts = [];
        if ($offerGroupModel->getOfferGroupProducts()) {
            foreach ($offerGroupModel->getOfferGroupProducts() as $product) {
                $selectedProducts[] = ModelSerializer::parse($product, ProductModel::class);
            }
        }


        return $this->render('sale/offer_group/read.html.twig', [
            'controller_name' => 'OfferGroupController',
            'offerGroupModel' => $offerGroupModel,
            'selectedProducts' => $selectedProducts,
            'canRead' => $canRead,
        ]);
    }

    /**
     * @Route("/status/{offer_group_id}/{machine_name}", name="_sale_offer_group_status")
     * @param $offer_group_id
     * @param $machine_name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function changeOfferGroupAvailability($offer_group_id, $machine_name)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::inventory_shelve_change_status)) {
            return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_create'));
        }
        $offerGroupStatusModel = new OfferGroupStatusModel();
        if ($machine_name == 'deactive') {
            $offerGroupStatusModel->setOfferGroupId($offer_group_id);
            $offerGroupStatusModel->setOfferGroupStatusMachineName('active');
        } else {
            $offerGroupStatusModel->setOfferGroupId($offer_group_id);
            $offerGroupStatusModel->setOfferGroupStatusMachineName('deactive');
        }

        $request = new Req(Servers::Sale, Sale::OfferGroup, 'change_status');
        $request->add_instance($offerGroupStatusModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_offer_group_list'));
    }

    /**
     * @Route("/add-product/{offer_group_id}/{product_id}", name="_sale_offer_group_add_product")
     * @param $offer_group_id
     * @param $product_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addProduct($offer_group_id, $product_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_add_product)) {
            return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_edit', ['id' => $offer_group_id]));

        }
        $inputs = $request->request->all();

        /**
         * @var $productModel ProductModel
         */
        $productModel = ModelSerializer::parse($inputs, ProductModel::class);
        $productModel->setProductOfferGroupId($offer_group_id);
        $productModel->setProductId($product_id);
        $request = new Req(Servers::Sale, Sale::OfferGroup, 'add_product');
        $request->add_instance($productModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_edit', ['id' => $offer_group_id]));
    }

    /**
     * @Route("/remove-product/{offer_group_id}/{product_id}", name="_sale_offer_group_remove_product")
     * @param $offer_group_id
     * @param $product_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeProduct($offer_group_id, $product_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_offergroup_remove_product)) {
            return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_edit', ['id' => $offer_group_id]));

        }
        $productModel = new ProductModel();
        $productModel->setProductId($product_id);
        $productModel->setProductOfferGroupId($offer_group_id);
        $request = new Req(Servers::Sale, Sale::OfferGroup, 'remove_product');
        $request->add_instance($productModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_offer_group_sale_offer_group_edit', ['id' => $offer_group_id]));
    }
}
