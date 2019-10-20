<?php

namespace App\Controller\Sale;

use Matican\Models\Inventory\ShelveModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\ProductModel;
use Matican\Models\Sale\OfferGroupModel;
use Matican\Models\Sale\PricingDeedConfirmStatusModel;
use Matican\Models\Sale\PricingDeedModel;
use Matican\Models\Sale\PricingDeedStatusModel;
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
 * @Route("/sale/pricing-deed", name="sale_pricing_deed")
 */
class PricingDeedController extends AbstractController
{
    /**
     * @Route("/list", name="_sale_pricing_deed_list")
     */
    public function fetchAll()
    {
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_all);
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_new);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_update);


        if (!$canSeeAll) {
            return $this->redirect($this->generateUrl('default'));

        }
        $allPricingDeedsRequest = new Req(Servers::Sale, Sale::PricingDeed, 'all');
        $allPricingDeedsResponse = $allPricingDeedsRequest->send();

        /**
         * @var $pricingDeeds PricingDeedModel[]
         */
        $pricingDeeds = [];
        if ($allPricingDeedsResponse->getContent()) {
            foreach ($allPricingDeedsResponse->getContent() as $pricingDeed) {
                $pricingDeeds[] = ModelSerializer::parse($pricingDeed, PricingDeedModel::class);
            }
        }

        return $this->render('sale/pricing_deed/list.html.twig', [
            'controller_name' => 'PricingDeedController',
            'pricingDeeds' => $pricingDeeds,
            'canSeeAll' => $canSeeAll,
            'canCreate' => $canCreate,
            'canUpdate' => $canUpdate,
        ]);
    }

    /**
     * @Route("/create", name="_sale_pricing_deed_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_update);

        if (!$canCreate) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $pricingDeedModel PricingDeedModel
         */
        $pricingDeedModel = ModelSerializer::parse($inputs, PricingDeedModel::class);

        if (!empty($inputs)) {
            $request = new Req(Servers::Sale, Sale::PricingDeed, 'new');
            $request->add_instance($pricingDeedModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $pricingDeedModel PricingDeedModel
                 */
                $pricingDeedModel = ModelSerializer::parse($response->getContent(), PricingDeedModel::class);
                $this->addFlash('s', $response->getMessage());
                if ($canEdit) {
                    return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricingDeedModel->getPricingDeedId()]));
                } else {
                    return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_list'));
                }
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

//        $years = [];
//        for ($i = 1950; $i <= 2019; $i++) {
//            $years[] = $i;
//        }
//
//        $months = [];
//        for ($j = 1; $j <= 12; $j++) {
//            $months[] = $j;
//        }
//
//        $days = [];
//        for ($k = 1; $k <= 31; $k++) {
//            $days[] = $k;
//        }

        return $this->render('sale/pricing_deed/create.html.twig', [
            'controller_name' => 'PricingDeedController',
//            'years' => $years,
//            'months' => $months,
//            'days' => $days,
            'pricingDeedModel' => $pricingDeedModel,
            'canCreate' => $canCreate,

        ]);
    }

    /**
     * @Route("/edit/{id}", name="_sale_pricing_deed_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_update);
        $canAddProduct = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_product);
        $canAddOfferGroup = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_offer_group_products);
        $canAddShelve = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_shelve_products);
        $canAddPricingDeed = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_pricing_deed_products);
        $canRemoveProduct = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_remove_product);
        $canGoPricing = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_accept_pricing_list);
        $canConfirm = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_apply_pricing_deed);
        $canSave = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_save_pricing_list);
        $canBack = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_ignore_pricing_list);

        if (!$canUpdate) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_read', ['id' => $id]));

        }
        $inputs = $request->request->all();

        /**
         * @var $pricingDeedModel PricingDeedModel
         */
        $pricingDeedModel = ModelSerializer::parse($inputs, PricingDeedModel::class);
        $pricingDeedModel->setPricingDeedId($id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'fetch');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
        $pricingDeedModel = ModelSerializer::parse($response->getContent(), PricingDeedModel::class);

        if (!empty($inputs)) {
            /**
             * @var $pricingDeedModel PricingDeedModel
             */
            $pricingDeedModel = ModelSerializer::parse($inputs, PricingDeedModel::class);
            $pricingDeedModel->setPricingDeedId($id);
            $request = new Req(Servers::Sale, Sale::PricingDeed, 'update');
            $request->add_instance($pricingDeedModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $pricingDeedModel PricingDeedModel
                 */
                $pricingDeedModel = ModelSerializer::parse($response->getContent(), PricingDeedModel::class);
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricingDeedModel->getPricingDeedId()]));
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


        $allOfferGroupsRequest = new Req(Servers::Sale, Sale::OfferGroup, 'all');
        $allOfferGroupResponse = $allOfferGroupsRequest->send();

        /**
         * @var $offerGroups OfferGroupModel[]
         */
        $offerGroups = [];
        foreach ($allOfferGroupResponse->getContent() as $offerGroup) {
            $offerGroups[] = ModelSerializer::parse($offerGroup, OfferGroupModel::class);
        }

        $allShelvesRequest = new Req(Servers::Inventory, Inventory::Shelve, 'all');
        $allShelvesResponse = $allShelvesRequest->send();

        /**
         * @var $shelves ShelveModel[]
         */
        $shelves = [];
        if ($allShelvesResponse->getContent()) {
            foreach ($allShelvesResponse->getContent() as $shelve) {
                $shelves[] = ModelSerializer::parse($shelve, ShelveModel::class);
            }
        }

        $allPricingDeedsRequest = new Req(Servers::Sale, Sale::PricingDeed, 'all');
        $allPricingDeedsResponse = $allPricingDeedsRequest->send();

        /**
         * @var $pricingDeeds PricingDeedModel[]
         */
        $pricingDeeds = [];
        foreach ($allPricingDeedsResponse->getContent() as $pricingDeed) {
            $pricingDeeds[] = ModelSerializer::parse($pricingDeed, PricingDeedModel::class);
        }


        /**
         * @var $selectedProducts ProductModel[]
         */
        $selectedProducts = [];
        if ($pricingDeedModel->getPricingDeedProducts()) {
            foreach ($pricingDeedModel->getPricingDeedProducts() as $product) {
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


//        $years = [];
//        for ($i = 1950; $i <= 2019; $i++) {
//            $years[] = $i;
//        }
//
//        $months = [];
//        for ($j = 1; $j <= 12; $j++) {
//            $months[] = $j;
//        }
//
//        $days = [];
//        for ($k = 1; $k <= 31; $k++) {
//            $days[] = $k;
//        }


        return $this->render('sale/pricing_deed/edit.html.twig', [
            'controller_name' => 'PricingDeedController',
//            'years' => $years,
//            'months' => $months,
//            'days' => $days,
            'pricingDeedModel' => $pricingDeedModel,
            'shelvesProducts' => $shelvesProducts,
            'offerGroups' => $offerGroups,
            'shelves' => $shelves,
            'selectedProducts' => $selectedProducts,
            'pricingDeeds' => $pricingDeeds,
            'canAddProduct' => $canAddProduct,
            'canAddOfferGroup' => $canAddOfferGroup,
            'canAddShelve' => $canAddShelve,
            'canAddPricingDeed' => $canAddPricingDeed,
            'canRemoveProduct' => $canRemoveProduct,
            'canGoPricing' => $canGoPricing,
            'canConfirm' => $canConfirm,
            'canSave' => $canSave,
            'canBack' => $canBack,
            'canUpdate' => $canUpdate,
        ]);
    }

    /**
     * @Route("/read/{id}", name="_sale_pricing_deed_read")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($id, Request $request)
    {
        $canRead = AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_fetch);
        if (!$canRead) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_list'));

        }
        $inputs = $request->request->all();

        /**
         * @var $pricingDeedModel PricingDeedModel
         */
        $pricingDeedModel = ModelSerializer::parse($inputs, PricingDeedModel::class);
        $pricingDeedModel->setPricingDeedId($id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'fetch_confirmed');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
        $pricingDeedModel = ModelSerializer::parse($response->getContent(), PricingDeedModel::class);

        /**
         * @var $selectedProducts ProductModel[]
         */
        $selectedProducts = [];
        foreach ($pricingDeedModel->getPricingDeedProducts() as $product) {
            $selectedProducts[] = ModelSerializer::parse($product, ProductModel::class);
        }


        return $this->render('sale/pricing_deed/read.html.twig', [
            'controller_name' => 'PricingDeedController',
            'pricingDeedModel' => $pricingDeedModel,
            'selectedProducts' => $selectedProducts,
            'canRead' => $canRead,
        ]);
    }

    /**
     * @Route("/confirm/{pricing_deed_id}", name="_sale_pricing_deed_confirm")
     * @param $pricing_deed_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function pricingDeedConfirm($pricing_deed_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_apply_pricing_deed)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $pricingDeedModel = new PricingDeedModel();
        $pricingDeedModel->setPricingDeedId($pricing_deed_id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'apply_pricing_deed');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_read', ['id' => $pricing_deed_id]));
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }

    /**
     * @Route("/add-product/{pricing_deed_id}/{product_id}", name="_sale_pricing_deed_add_product")
     * @param $pricing_deed_id
     * @param $product_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addProduct($pricing_deed_id, $product_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_product)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $inputs = $request->request->all();

        /**
         * @var $productModel ProductModel
         */
        $productModel = ModelSerializer::parse($inputs, ProductModel::class);
        $productModel->setProductDeedId($pricing_deed_id);
        $productModel->setProductId($product_id);
//        dd($productModel);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'add_product');
        $request->add_instance($productModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }


    /**
     * @Route("/remove-product/{pricing_deed_id}/{deed_item_id}", name="_sale_pricing_deed_remove_product")
     * @param $pricing_deed_id
     * @param $deed_item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeProduct($pricing_deed_id, $deed_item_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_remove_product)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $productModel = new ProductModel();
        $productModel->setProductDeedId($pricing_deed_id);
        $productModel->setProductPricingDeedItemId($deed_item_id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'remove_product');
        $request->add_instance($productModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }

    /**
     * @Route("/add-offer-group/{pricing_deed_id}/{offer_group_id}", name="_sale_pricing_deed_add_offer_group")
     * @param $pricing_deed_id
     * @param $offer_group_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addOfferGroup($pricing_deed_id, $offer_group_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_offer_group_products)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $pricingDeedModel = new PricingDeedModel();
        $pricingDeedModel->setPricingDeedId($pricing_deed_id);
        $pricingDeedModel->setPricingDeedOfferGroupId($offer_group_id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'add_offer_group_products');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }

    /**
     * @Route("/add-shelve/{pricing_deed_id}/{shelve_id}", name="_sale_pricing_deed_add_shelve")
     * @param $pricing_deed_id
     * @param $shelve_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addShelve($pricing_deed_id, $shelve_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_shelve_products)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $pricingDeedModel = new PricingDeedModel();
        $pricingDeedModel->setPricingDeedId($pricing_deed_id);
        $pricingDeedModel->setPricingDeedShelveId($shelve_id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'add_shelve_products');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }


    /**
     * @Route("/add-price/{pricing_deed_id}", name="_sale_pricing_deed_add_price")
     * @param $pricing_deed_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function acceptPricingDeed($pricing_deed_id, Request $request)
    {

        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_accept_pricing_list)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $inputs = $request->request->all();

        /**
         * @var $pricingDeedModel PricingDeedModel
         */
        $pricingDeedModel = ModelSerializer::parse($inputs, PricingDeedModel::class);
        $pricingDeedModel->setPricingDeedId($pricing_deed_id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'accept_pricing_list');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }

    /**
     * @Route("/back-selection/{pricing_deed_id}", name="_sale_pricing_deed_back_selection")
     * @param $pricing_deed_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function backToSelection($pricing_deed_id, Request $request)
    {

        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_ignore_pricing_list)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $inputs = $request->request->all();

        /**
         * @var $pricingDeedModel PricingDeedModel
         */
        $pricingDeedModel = ModelSerializer::parse($inputs, PricingDeedModel::class);
        $pricingDeedModel->setPricingDeedId($pricing_deed_id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'ignore_pricing_list');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }

    /**
     * @Route("/save-pricing/{pricing_deed_id}", name="_sale_pricing_deed_save_pricing")
     * @param $pricing_deed_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function savePricingList($pricing_deed_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_save_pricing_list)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $inputs = $request->request->all();

//        dd($inputs);

        $deedItemArray = [];
        $products = $inputs['productId'];
        $newCurrentPrice = $inputs['productCurrentPrice'];
        $newDiscountPrice = $inputs['productDiscountPrice'];
        foreach ($products as $key => $product) {
            $deedItemArray[] = [
                'productId' => $product,
                'productCurrentPrice' => $newCurrentPrice[$key],
                'productDiscountPrice' => $newDiscountPrice[$key]
            ];
        }

        $pricingDeedModel = new PricingDeedModel();

        /**
         * @var $productModelArray ProductModel[]
         */
        $productModelArray = [];
        foreach ($deedItemArray as $item) {
            $productModel = [];
            $productModel['productId'] = $item['productId'];
            $productModel['productNewCurrentPrice'] = $item['productCurrentPrice'];
            $productModel['productNewDiscountPrice'] = $item['productDiscountPrice'];
            $productModelArray[] = $productModel;
        }
//        dd(json_encode($productModelArray);
        $pricingDeedModel->setPricingDeedProducts(json_encode($productModelArray));
        $pricingDeedModel->setPricingDeedId($pricing_deed_id);

//        dd($pricingDeedModel);

        $request = new Req(Servers::Sale, Sale::PricingDeed, 'save_pricing_list');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }

        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }


    /**
     * @Route("/add-pricing-deed/{pricing_deed_id}/{import_pricing_deed_id}", name="_sale_pricing_deed_add_pricing_deed")
     * @param $pricing_deed_id
     * @param $import_pricing_deed_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addPricingDeed($pricing_deed_id, $import_pricing_deed_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_pricingdeed_add_pricing_deed_products)) {
            return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
        }
        $pricingDeedModel = new PricingDeedModel();
        $pricingDeedModel->setPricingDeedId($pricing_deed_id);
        $pricingDeedModel->setPricingDeedImportDeedId($import_pricing_deed_id);
        $request = new Req(Servers::Sale, Sale::PricingDeed, 'add_pricing_deed_products');
        $request->add_instance($pricingDeedModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_pricing_deed_sale_pricing_deed_edit', ['id' => $pricing_deed_id]));
    }
}