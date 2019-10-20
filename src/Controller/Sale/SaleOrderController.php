<?php

namespace App\Controller\Sale;

use Matican\ModelSerializer;
use Matican\Models\Repository\PersonModel;
use Matican\Models\Repository\ProductModel;
use Matican\Models\Sale\OrderModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Inventory;
use Matican\Core\Entities\Repository;
use Matican\Core\Entities\Sale;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Matican\Models\Repository\Person;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/sale/order", name="sale_order")
 */
class SaleOrderController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {

        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::sale_order_all);
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::sale_order_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::sale_order_fetch);

        if (!$canSeeAll) {
            return $this->redirect($this->generateUrl('default'));
        }
        $allOrdersRequest = new Req(Servers::Sale, Sale::Order, 'all');
        $allOrdersResponse = $allOrdersRequest->send();

        /**
         * @var $orders OrderModel[]
         */
        $orders = [];
        if ($allOrdersResponse->getContent()) {
            foreach ($allOrdersResponse->getContent() as $order) {
                $orders[] = ModelSerializer::parse($order, OrderModel::class);
            }
        }


        return $this->render('sale/sale_order/list.html.twig', [
            'controller_name' => 'SaleOrderController',
            'orders' => $orders,
            'canSeeAll' => $canSeeAll,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * @Route("/create", name="_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::sale_order_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::sale_order_accept_order_list);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::sale_order_update);

        if (!$canCreate) {
            return $this->redirect($this->generateUrl('sale_order_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($inputs, OrderModel::class);

        if (!$inputs) {
            /**
             * @var $orderModel OrderModel
             */
            $orderModel = ModelSerializer::parse($inputs, OrderModel::class);
            $request = new Req(Servers::Sale, Sale::Order, 'new');
            $request->add_instance($orderModel);
            $response = $request->send();

            if ($response->getStatus() == ResponseStatus::successful) {
                /**
                 * @var $orderModel OrderModel
                 */
                $orderModel = ModelSerializer::parse($response->getContent(), OrderModel::class);
                $this->addFlash('s', $response->getMessage());
                if ($canEdit) {
                    return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $orderModel->getOrderId()]));
                } else {
                    return $this->redirect($this->generateUrl('sale_order_list'));
                }
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }


        $allPersonsRequest = new Req(Servers::Repository, Repository::Person, 'all');
        $allPersonsResponse = $allPersonsRequest->send();

        /**
         * @var $persons PersonModel[]
         */
        $persons = [];
        if ($allPersonsResponse->getContent()) {
            foreach ($allPersonsResponse->getContent() as $person) {
                $persons[] = ModelSerializer::parse($person, PersonModel::class);
            }
        }

        return $this->render('sale/sale_order/create.html.twig', [
            'controller_name' => 'SaleOrderController',
            'orderModel' => $orderModel,
            'persons' => $persons,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {

        $canRead = AuthUser::if_is_allowed(ServerPermissions::sale_order_fetch);
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::sale_order_update);
        $canAddProduct = AuthUser::if_is_allowed(ServerPermissions::sale_order_add_product);
        $canRemoveProduct = AuthUser::if_is_allowed(ServerPermissions::sale_order_remove_product);
        $canGoForFinal = AuthUser::if_is_allowed(ServerPermissions::sale_order_accept_order_list);
        $canSaveSerial = AuthUser::if_is_allowed(ServerPermissions::sale_order_save_order);
        $canBack = AuthUser::if_is_allowed(ServerPermissions::sale_order_ignore_order_list);
        $canConfirm = AuthUser::if_is_allowed(ServerPermissions::sale_order_confirm_order);
        $canReject = AuthUser::if_is_allowed('reject_order');

        if (!$canRead) {
            return $this->redirect($this->generateUrl('sale_order_read', ['id' => $id]));
        }
        $inputs = $request->request->all();
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($inputs, OrderModel::class);
        $orderModel->setOrderId($id);
        $request = new Req(Servers::Sale, Sale::Order, 'fetch');
        $request->add_instance($orderModel);
        $response = $request->send();
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($response->getContent(), OrderModel::class);

        if ($orderModel->getOrderStatus() == 'confirmed') {
            return $this->redirect($this->generateUrl('sale_order_read', ['id' => $orderModel->getOrderId()]));
        }

        $allPersonsRequest = new Req(Servers::Repository, Repository::Person, 'all');
        $allPersonsResponse = $allPersonsRequest->send();

        /**
         * @var $persons PersonModel[]
         */
        $persons = [];
        if ($allPersonsResponse->getContent()) {
            foreach ($allPersonsResponse->getContent() as $person) {
                $persons[] = ModelSerializer::parse($person, PersonModel::class);
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
        if ($orderModel->getOrderProducts()) {
            foreach ($orderModel->getOrderProducts() as $product) {
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


        if (!empty($inputs)) {
            $orderModel = ModelSerializer::parse($inputs, OrderModel::class);
            $orderModel->setOrderId($id);
            $request = new Req(Servers::Sale, Sale::Order, 'update');
            $request->add_instance($orderModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $orderModel = ModelSerializer::parse($response->getContent(), OrderModel::class);
                $this->addFlash('s', $response->getMessage());
                return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $orderModel->getOrderId()]));
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }


        return $this->render('sale/sale_order/edit.html.twig', [
            'controller_name' => 'SaleOrderController',
            'orderModel' => $orderModel,
            'persons' => $persons,
            'shelvesProducts' => $shelvesProducts,
            'selectedProducts' => $selectedProducts,
            'canUpdate' => $canUpdate,
            'canAddProduct' => $canAddProduct,
            'canRemoveProduct' => $canRemoveProduct,
            'canGoForFinal' => $canGoForFinal,
            'canSaveSerial' => $canSaveSerial,
            'canBack' => $canBack,
            'canConfirm' => $canConfirm,
            'canReject' => $canReject,
        ]);
    }


    /**
     * @Route("/read/{id}", name="_read")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($id, Request $request)
    {
        $canRead = AuthUser::if_is_allowed(ServerPermissions::sale_order_fetch);
        if (!$canRead) {
            return $this->redirect($this->generateUrl('sale_order_list'));
        }
        $inputs = $request->request->all();
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($inputs, OrderModel::class);
        $orderModel->setOrderId($id);
        $request = new Req(Servers::Sale, Sale::Order, 'fetch');
        $request->add_instance($orderModel);
        $response = $request->send();
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($response->getContent(), OrderModel::class);


        return $this->render('sale/sale_order/read.html.twig', [
            'controller_name' => 'SaleOrderController',
            'orderModel' => $orderModel,
            'canRead' => $canRead,
        ]);
    }

    /**
     * @Route("/add-product/{order_id}/{product_id}", name="_add_product")
     * @param $order_id
     * @param $product_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addProduct($order_id, $product_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_order_add_product)) {
            return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
        }
        $inputs = $request->request->all();
        /**
         * @var $productModel ProductModel
         */
        $productModel = ModelSerializer::parse($inputs, ProductModel::class);
        $productModel->setProductOrderId($order_id);
        $productModel->setProductId($product_id);
        $request = new Req(Servers::Sale, Sale::Order, 'add_product');
        $request->add_instance($productModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
    }

    /**
     * @Route("/remove-product/{order_id}/{order_item_id}", name="_remove_product")
     * @param $order_id
     * @param $order_item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeProduct($order_id, $order_item_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_order_remove_product)) {
            return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));

        }
        $productModel = new ProductModel();
        $productModel->setProductOrderId($order_id);
        $productModel->setProductOrderItemId($order_item_id);
        $request = new Req(Servers::Sale, Sale::Order, 'remove_product');
        $request->add_instance($productModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
    }


    /**
     * @Route("/go-final-order/{order_id}", name="_go_final_order")
     * @param $order_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function goFinalOrder($order_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_order_accept_order_list)) {
            return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
        }
        $inputs = $request->request->all();
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($inputs, OrderModel::class);
        $orderModel->setOrderId($order_id);
        $request = new Req(Servers::Sale, Sale::Order, 'accept_order_list');
        $request->add_instance($orderModel);
        $response = $request->send();
        return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
    }


    /**
     * @Route("/back-selection/{order_id}", name="_back_selection")
     * @param $order_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function backToSelection($order_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_order_ignore_order_list)) {
            return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
        }
        $inputs = $request->request->all();
        /**
         * @var $orderModel OrderModel
         */
        $orderModel = ModelSerializer::parse($inputs, OrderModel::class);
        $orderModel->setOrderId($order_id);
        $request = new Req(Servers::Sale, Sale::Order, 'ignore_order_list');
        $request->add_instance($orderModel);
        $response = $request->send();
        return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
    }


    /**
     * @Route("/save-order/{order_id}", name="_save_order")
     * @param $order_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function saveOrder($order_id, Request $request)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_order_save_order)) {
            return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
        }
        $inputs = $request->request->all();
        $orderItemArray = [];
        $products = $inputs['productId'];
        $productSerials = $inputs['productSerial'];
        $productQuantities = $inputs['productQuantity'];
        $productOrderItemIds = $inputs['productOrderItemId'];
        foreach ($products as $key => $product) {
            $orderItemArray[] = [
                'productId' => $product,
                'productSerial' => $productSerials[$key],
                'productQuantity' => $productQuantities[$key],
                'productOrderItemId' => $productOrderItemIds[$key],
            ];
        }

        $orderModel = new OrderModel();

        /**
         * @var $productModelArray ProductModel[]
         */
        $productModelArray = [];
        foreach ($orderItemArray as $item) {
            $productModel = [];
            $productModel['productId'] = $item['productId'];
            $productModel['productSerial'] = $item['productSerial'];
            $productModel['productQuantity'] = $item['productQuantity'];
            $productModel['productOrderItemId'] = $item['productOrderItemId'];
            $productModelArray[] = $productModel;
        }
        $orderModel->setOrderProducts(json_encode($productModelArray));
        $orderModel->setOrderId($order_id);
        $request = new Req(Servers::Sale, Sale::Order, 'save_order');
        $request->add_instance($orderModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('s', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
    }

    /**
     * @Route("/confirm/{order_id}", name="_confirm")
     * @param $order_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function orderConfirm($order_id)
    {
        if (!AuthUser::if_is_allowed(ServerPermissions::sale_order_confirm_order)) {
            return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
        }
        $orderModel = new OrderModel();
        $orderModel->setOrderId($order_id);
        $request = new Req(Servers::Sale, Sale::Order, 'confirm_order');
        $request->add_instance($orderModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
    }

    /**
     * @Route("/reject/{order_id}", name="_reject")
     * @param $order_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function orderReject($order_id)
    {
        /**
         * @todo This controller maybe is not used
         */
        $orderModel = new OrderModel();
        $orderModel->setOrderId($order_id);
        $request = new Req(Servers::Sale, Sale::Order, 'reject_order');
        $request->add_instance($orderModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
            return $this->redirect($this->generateUrl('sale_order_read', ['id' => $order_id]));
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('sale_order_edit', ['id' => $order_id]));
    }

    /**
     * @Route("/add-coupon", name="_add_coupon")
     */
    public function addCoupon()
    {

    }

}
