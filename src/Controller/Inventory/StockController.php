<?php

namespace App\Controller\Inventory;

use Matican\ModelSerializer;
use Matican\Models\Repository\ItemModel;
use Matican\Models\Repository\ItemProductsModel;
use Matican\Models\Repository\ProductModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;
use Matican\Core\Entities\Inventory as InventoryEntity;

/**
 * @Route("/inventory/stock", name="inventory_stock")
 */
class StockController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::inventory_inventory_all_item_products);
        $canRead = AuthUser::if_is_allowed(ServerPermissions::inventory_inventory_get_item_products);

        if ($canSeeAll) {


            $request = new Req(Servers::Inventory, InventoryEntity::Inventory, 'all_item_products');
            $response = $request->send();

            /**
             * @var $itemProducts ItemProductsModel[]
             */
            $itemProducts = [];
            foreach ($response->getContent() as $itemProduct) {
                $itemProducts[] = ModelSerializer::parse($itemProduct, ItemProductsModel::class);
            }

//        dd($itemProducts);


            return $this->render('inventory/inventory_stock/list.html.twig', [
                'controller_name' => 'StockController',
                'itemProducts' => $itemProducts,
                'canSeeAll' => $canSeeAll,
                'canRead' => $canRead,
            ]);
        } else {
            return $this->render('inventory/inventory_stock/list.html.twig', [
                'controller_name' => 'StockController',
                'itemProducts' => [],
            ]);
        }
    }

    /**
     * @Route("/read/{item_id}", name="_read")
     * @param $item_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($item_id, Request $request)
    {

        $canRead = AuthUser::if_is_allowed(ServerPermissions::inventory_inventory_get_item_products);

        if (AuthUser::if_is_allowed(ServerPermissions::repository_item_fetch)) {
            if ($canRead) {


                $inputs = $request->request->all();

                /**
                 * @var $itemModel ItemModel
                 */
                $itemModel = ModelSerializer::parse($inputs, ItemModel::class);
                $itemModel->setItemID($item_id);
                $request = new Req(Servers::Repository, Repository::Item, 'fetch');
                $request->add_instance($itemModel);
                $response = $request->send();
                /**
                 * @var $itemModel ItemModel
                 */
                $itemModel = ModelSerializer::parse($response->getContent(), ItemModel::class);


                $newItemModel = new ItemModel();
                $newItemModel->setItemID($item_id);
                $productsRequest = new Req(Servers::Inventory, InventoryEntity::Inventory, 'get_item_products');
                $productsRequest->add_instance($newItemModel);
                $productsResponse = $productsRequest->send();

//        dd($productsResponse);

                /**
                 * @var $products ProductModel[]
                 */
                $products = [];
                if ($productsResponse->getContent()) {
                    foreach ($productsResponse->getContent() as $product) {
                        $products[] = ModelSerializer::parse($product, ProductModel::class);
                    }
                }


                return $this->render('inventory/inventory_stock/read.html.twig', [
                    'controller_name' => 'StockController',
                    'itemModel' => $itemModel,
                    'products' => $products,
                    'canRead' => $canRead,
                ]);

            } else {
                return $this->redirect($this->generateUrl('inventory_stock_list'));
            }
        } else {
            return $this->redirect($this->generateUrl('inventory_deed_inventory_deed_list'));
        }
    }
}
