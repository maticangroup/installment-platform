<?php

namespace App\Controller\Inventory;

use Matican\Models\Inventory\InventoryDeedModel;
use Matican\Models\Inventory\InventoryDeedStatusModel;
use Matican\Models\Inventory\InventoryModel;
use Matican\Models\Inventory\InventoryProductsModel;
use Matican\Models\Inventory\ShelveModel;
use Matican\Models\Inventory\ShelveProductsModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\ItemModel;
use Matican\Models\Repository\ItemProductsModel;
use Matican\Models\Repository\ProductModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Grpc\Server;
use Matican\Core\Entities\Inventory;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;
use Symfony\Component\Serializer\Tests\Model;

/**
 * @Route("/inventory/deed", name="inventory_deed")
 */
class InventoryDeedController extends AbstractController
{
    /**
     * @Route("/list", name="_inventory_deed_list")
     */
    public function fetchAll()
    {

        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_all);
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_new);

        if ($canSeeAll) {
            $request = new Req(Servers::Inventory, Inventory::Deed, 'all');
            $response = $request->send();

            /**
             * @var $deeds InventoryDeedModel[]
             */
            $deeds = [];
            if ($response->getContent()) {
                foreach ($response->getContent() as $deed) {
                    $deeds[] = ModelSerializer::parse($deed, InventoryDeedModel::class);
                }
            }
            return $this->render('inventory/inventory_deed/list.html.twig', [
                'controller_name' => 'InventoryDeedController',
                'deeds' => $deeds,
                'canSeeAll' => $canSeeAll,
                'canCreate' => $canCreate,
            ]);
        }
        return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create'));
    }

    /**
     * @Route("/create", name="_inventory_deed_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_all);
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_new);

        if ($canCreate) {
            $inputs = $request->request->all();

            /**
             * @var $deedModel InventoryDeedModel
             */
            $deedModel = ModelSerializer::parse($inputs, InventoryDeedModel::class);

            if (!empty($inputs)) {

                $request = new Req(Servers::Inventory, Inventory::Deed, 'new');


                if ($inputs['deedFrom'] == 'none') {

                    $deedModel->setInventoryDeedFromId('not_set');

                } elseif (strpos($inputs['deedFrom'], 'inventory') !== false) {

                    $deedModel->setInventoryDeedFromInventoryId(explode('_', $inputs['deedFrom'])[1]);

                } elseif (strpos($inputs['deedFrom'], 'shelve') !== false) {

                    $deedModel->setInventoryDeedFromShelveId(explode('_', $inputs['deedFrom'])[1]);

                }

                if (strpos($inputs['deedTo'], 'inventory') !== false) {

                    $deedModel->setInventoryDeedToInventoryId(explode('_', $inputs['deedTo'])[1]);

                } elseif (strpos($inputs['deedTo'], 'shelve') !== false) {

                    $deedModel->setInventoryDeedToShelveId(explode('_', $inputs['deedTo'])[1]);

                }


                $request->add_instance($deedModel);
                $response = $request->send();

                if ($deedModel->getInventoryDeedFromId() != 'none') {
                    if ($response->getStatus() == ResponseStatus::successful) {
                        $this->addFlash('s', $response->getMessage());
                        $deedModel = ModelSerializer::parse($response->getContent(), InventoryDeedModel::class);
                        return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_product_deed', ['deed_id' => $deedModel->getInventoryDeedId()]));
                    } else {
                        $this->addFlash('f', $response->getMessage());
                    }

                } else {
                    if ($response->getStatus() == ResponseStatus::successful) {
                        $this->addFlash('s', $response->getMessage());
                        $deedModel = ModelSerializer::parse($response->getContent(), InventoryDeedModel::class);
                        return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_to_inventory_deed', ['deed_id' => $deedModel->getInventoryDeedId()]));
                    } else {
                        $this->addFlash('f', $response->getMessage());
                    }
                }


            }

            $inventoriesRequest = new Req(Servers::Inventory, Inventory::Inventory, 'all');
            $inventoriesResponse = $inventoriesRequest->send();

            /**
             * @var $inventories InventoryModel[]
             */
            $inventories = [];
            foreach ($inventoriesResponse->getContent() as $inventory) {
                $inventories[] = ModelSerializer::parse($inventory, InventoryModel::class);
            }

            $shelvesRequest = new Req(Servers::Inventory, Inventory::Shelve, 'all');
            $shelvesResponse = $shelvesRequest->send();

            /**
             * @var $shelves ShelveModel[]
             */
            $shelves = [];
            foreach ($shelvesResponse->getContent() as $shelve) {
                $shelves[] = ModelSerializer::parse($shelve, ShelveModel::class);
            }

            return $this->render('inventory/inventory_deed/create.html.twig', [
                'controller_name' => 'InventoryDeedController',
                'inventories' => $inventories,
                'shelves' => $shelves,
                'deedModel' => $deedModel,
                'canSeeAll' => $canSeeAll,
                'canCreate' => $canCreate,
            ]);
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_list'));
        }
    }

    /**
     * @Route("/add-product/{deed_id}/{is_transfer}", name="_inventory_deed_create_add_product")
     * @param $deed_id
     * @param $is_transfer
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addItem($deed_id, $is_transfer, Request $request)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_add_product_to_deed)) {
            $inputs = $request->request->all();

            /**
             * @var $productModel ProductModel
             */
            $productModel = ModelSerializer::parse($inputs, ProductModel::class);
            $productModel->setProductDeedId($deed_id);
//        dd($productModel);
            $request = new Req(Servers::Inventory, Inventory::Deed, 'add_product_to_deed');
            $request->add_instance($productModel);
            $response = $request->send();
//        dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
            if ($is_transfer) {
                return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_product_deed', ['deed_id' => $deed_id]));
            } else {
                return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_to_inventory_deed', ['deed_id' => $deed_id]));
            }
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_to_inventory_deed', ['deed_id' => $deed_id]));
        }
    }

    /**
     * @Route("/add-product-transfer/{deed_id}/{product_id}", name="_inventory_deed_create_add_product_transfer")
     * @param $deed_id
     * @param $product_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addProduct($deed_id, $product_id, Request $request)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_add_product_to_deed)) {
            $inputs = $request->request->all();

            /**
             * @var $productModel ProductModel
             */
            $productModel = ModelSerializer::parse($inputs, ProductModel::class);
            $productModel->setProductDeedId($deed_id);
            $productModel->setProductId($product_id);
            $request = new Req(Servers::Inventory, Inventory::Deed, 'add_product_to_deed');
            $request->add_instance($productModel);
            $response = $request->send();
//        dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_product_deed', ['deed_id' => $deed_id]));
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_product_deed', ['deed_id' => $deed_id]));
        }
    }


    /**
     * @Route("/remove-product/{product_id}/{deed_id}/{is_transfer}", name="_inventory_deed_create_remove_product")
     * @param $product_id
     * @param $deed_id
     * @param $is_transfer
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeItem($product_id, $deed_id, $is_transfer)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_remove_product_from_deed)) {

            $productModel = new ProductModel();
            $productModel->setProductId($product_id);
            $productModel->setProductDeedId($deed_id);
            $request = new Req(Servers::Inventory, Inventory::Deed, 'remove_product_from_deed');
            $request->add_instance($productModel);
            $response = $request->send();
//        dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }

            if ($is_transfer) {
                return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_product_deed', ['deed_id' => $deed_id]));
            } else {
                return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_to_inventory_deed', ['deed_id' => $deed_id]));
            }
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_create_to_inventory_deed', ['deed_id' => $deed_id]));
        }

    }


    /**
     * @Route("/create-to-inventory-deed/{deed_id}", name="_inventory_deed_create_to_inventory_deed")
     * @param $deed_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function createToInventoryDeed($deed_id, Request $request)
    {
        /**
         * @todo  Authorization is missing here
         */
        $inputs = $request->request->all();

        /**
         * @var $deedModel InventoryDeedModel
         */
        $deedModel = ModelSerializer::parse($inputs, InventoryDeedModel::class);
        $deedModel->setInventoryDeedId($deed_id);
        $request = new Req(Servers::Inventory, Inventory::Deed, 'fetch');
        $request->add_instance($deedModel);
        $response = $request->send();
//        dd($response);
        $deedModel = ModelSerializer::parse($response->getContent(), InventoryDeedModel::class);

//        dd($deedModel);
        /**
         * @var $deedStatusModel InventoryDeedStatusModel
         */
        $deedStatusModel = ModelSerializer::parse($deedModel->getInventoryDeedStatus(), InventoryDeedStatusModel::class);


        if ($deedStatusModel->getInventoryDeedStatusMachineName() == 'accepted') {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_read', ['deed_id' => $deed_id]));
        }

//        $request = new Req(Servers::Inventory, Inventory::Deed, 'import_to_inventory');
//        $request->add_instance($deedModel);

        $allItemsRequest = new Req(Servers::Repository, Repository::Item, 'all');
        $allItemsResponse = $allItemsRequest->send();

        /**
         * @var $items ItemModel[]
         */
        $items = [];
        foreach ($allItemsResponse->getContent() as $item) {
            $items[] = ModelSerializer::parse($item, ItemModel::class);
        }

        $itemModel = [];

        if (!empty($inputs)) {
            $itemModel = ModelSerializer::parse($inputs, ItemModel::class);
            $request = new Req(Servers::Repository, Repository::Item, 'fetch');
            $request->add_instance($itemModel);
            $response = $request->send();
            /**
             * @var $itemModel ItemModel
             */
            $itemModel = ModelSerializer::parse($response->getContent(), ItemModel::class);
        }

        /**
         * @var $products ProductModel[]
         */
        $products = [];
        if ($deedModel->getInventoryDeedProducts()) {
            foreach ($deedModel->getInventoryDeedProducts() as $product) {
                $products[] = ModelSerializer::parse($product, ProductModel::class);
            }
        }


        return $this->render('inventory/inventory_deed/create-to-inventory.html.twig', [
            'controller_name' => 'InventoryDeedController',
            'deedModel' => $deedModel,
            'items' => $items,
            'selectedItemModel' => $itemModel,
            'products' => $products,
        ]);
    }

    /**
     * @Route("/create-product-deed/{deed_id}", name="_inventory_deed_create_product_deed")
     * @param $deed_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function createProductDeed($deed_id, Request $request)
    {
        /**
         * @todo  Authorization is missing here
         */
        $inputs = $request->request->all();

        /**
         * @var $deedModel InventoryDeedModel
         */
        $deedModel = ModelSerializer::parse($inputs, InventoryDeedModel::class);
        $deedModel->setInventoryDeedId($deed_id);
        $request = new Req(Servers::Inventory, Inventory::Deed, 'fetch');
        $request->add_instance($deedModel);
        $response = $request->send();
//        dd($response);
        /**
         * @var $deedModel InventoryDeedModel
         */
        $deedModel = ModelSerializer::parse($response->getContent(), InventoryDeedModel::class);

//        dd($deedModel);
        /**
         * @var $deedStatusModel InventoryDeedStatusModel
         */
        $deedStatusModel = ModelSerializer::parse($deedModel->getInventoryDeedStatus(), InventoryDeedStatusModel::class);

        if ($deedStatusModel->getInventoryDeedStatusMachineName() == 'accepted') {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_read', ['deed_id' => $deed_id]));
        }
//        dd($deedModel);

        $selectedProductsIds = [];
        if ($deedModel->getInventoryDeedProducts()) {
            foreach ($deedModel->getInventoryDeedProducts() as $product) {
                $selectedProductsIds[] = $product->productId;
            }
        }

        /**
         * @var $products ProductModel[]
         */
        $products = [];

        if ($deedModel->getInventoryDeedFromShelveId()) {
            $shelveProductsModel = new ShelveProductsModel();
            $shelveProductsModel->setShelveId($deedModel->getInventoryDeedFromShelveId());

            $productsRequest = new Req(Servers::Inventory, Inventory::Shelve, 'get_shelve_products');
            $productsRequest->add_instance($shelveProductsModel);
            $productsResponse = $productsRequest->send();

            /**
             * @var $shelveProductsModel ShelveProductsModel
             */
            $shelveProductsModel = $productsResponse->getContent();


            /**
             * @var $products ProductModel[]
             */
            $products = $shelveProductsModel['products'];

            $shelveProductsArray = [];
            if ($products) {
                foreach ($products as $product) {
                    /**
                     * @var $productModel ProductModel
                     */
                    $productModel = ModelSerializer::parse($product, ProductModel::class);
                    if (in_array($productModel->getProductId(), $selectedProductsIds)) {
                        $productModel->setProductIsDisabled(true);
                    }
                    $shelveProductsArray[] = $productModel;
                }
            }
            $products = $shelveProductsArray;
        } elseif ($deedModel->getInventoryDeedFromInventoryId()) {
            $inventoryProductsModel = new InventoryProductsModel();
            $inventoryProductsModel->setInventoryId($deedModel->getInventoryDeedFromInventoryId());

            $productsRequest = new Req(Servers::Inventory, Inventory::Inventory, 'get_inventory_products');
            $productsRequest->add_instance($inventoryProductsModel);
            $productsResponse = $productsRequest->send();

//            dd($productsResponse);
            /**
             * @var $inventoryProductsModel InventoryProductsModel
             */
            $inventoryProductsModel = $productsResponse->getContent();

            $products = [];
            if (isset($inventoryProductsModel['products'])) {


                /**
                 * @var $products ProductModel[]
                 */
                $products = $inventoryProductsModel['products'];

                $inventoryProductsArray = [];
                if ($products) {
                    foreach ($products as $product) {
                        /**
                         * @var $productModel ProductModel
                         */
                        $productModel = ModelSerializer::parse($product, ProductModel::class);
                        if (in_array($productModel->getProductId(), $selectedProductsIds)) {
                            $productModel->setProductIsDisabled(true);
                        }
                        $inventoryProductsArray[] = $productModel;
                    }
                }

                $products = $inventoryProductsArray;
            }
        }


//


        /**
         * @var $selectedProducts ProductModel[]
         */
        $selectedProducts = [];
        if ($deedModel->getInventoryDeedProducts()) {
            foreach ($deedModel->getInventoryDeedProducts() as $selectedProduct) {
                $selectedProducts[] = ModelSerializer::parse($selectedProduct, ProductModel::class);
            }
        }


        return $this->render('inventory/inventory_deed/create-product-deed.html.twig', [
            'controller_name' => 'InventoryDeedController',
            'deedModel' => $deedModel,
            'products' => $products,
            'selectedProducts' => $selectedProducts,
        ]);
    }


    /**
     * @Route("/edit", name="_inventory_deed_edit")
     */
    public function edit()
    {
        return $this->render('inventory/inventory_deed/edit.html.twig', [
            'controller_name' => 'InventoryDeedController',
        ]);
    }

    /**
     * @Route("/read/{deed_id}", name="_inventory_deed_read")
     * @param $deed_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($deed_id, Request $request)
    {
        $canRead = AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_fetch);

        if ($canRead) {


            $inputs = $request->request->all();

            /**
             * @var $deedModel InventoryDeedModel
             */
            $deedModel = ModelSerializer::parse($inputs, InventoryDeedModel::class);
            $deedModel->setInventoryDeedId($deed_id);
            $request = new Req(Servers::Inventory, Inventory::Deed, 'fetch');
            $request->add_instance($deedModel);
            $response = $request->send();
            $deedModel = ModelSerializer::parse($response->getContent(), InventoryDeedModel::class);

            /**
             * @var $products ProductModel[]
             */
            $products = [];
            foreach ($deedModel->getInventoryDeedProducts() as $product) {
                $products[] = ModelSerializer::parse($product, ProductModel::class);
            }


            /**
             * @var $deedStatusModel InventoryDeedStatusModel
             */
            $deedStatusModel = ModelSerializer::parse($deedModel->getInventoryDeedStatus(), InventoryDeedStatusModel::class);


            return $this->render('inventory/inventory_deed/read.html.twig', [
                'controller_name' => 'InventoryDeedController',
                'deedModel' => $deedModel,
                'products' => $products,
                'deedStatusModel' => $deedStatusModel,
                'canRead' => $canRead,
            ]);
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_list'));
        }
    }

    /**
     * @Route("/confirm/{deed_id}", name="_inventory_deed_confirm")
     * @param $deed_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function InventoryDeedConfirm($deed_id)
    {
        if (AuthUser::if_is_allowed(ServerPermissions::inventory_transferdeed_confirm_deed)) {


            $inventoryDeedStatusModel = new InventoryDeedStatusModel();
            $inventoryDeedStatusModel->setInventoryDeedId($deed_id);
            $request = new Req(Servers::Inventory, Inventory::Deed, 'confirm_deed');
            $request->add_instance($inventoryDeedStatusModel);
            $response = $request->send();
//        dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }

            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_read', ['deed_id' => $deed_id]));
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_list'));
        }
    }
}
