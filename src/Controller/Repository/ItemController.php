<?php

namespace App\Controller\Repository;

use App\Cache;
use Matican\Authentication\AuthUser;
use Matican\Models\Media\ImageModel;
use Matican\ModelSerializer;
use Matican\Models\Repository\BarcodeModel;
use Matican\Models\Repository\BrandModel;
use Matican\Models\Repository\BrandSuppliersModel;
use Matican\Models\Repository\CompanyModel;
use Matican\Models\Repository\GuaranteeModel;
use Matican\Models\Repository\ItemCategoriesModel;
use Matican\Models\Repository\ItemCategoryModel;
use Matican\Models\Repository\ItemCategorySpecKeyModel;
use Matican\Models\Repository\ItemColorModel;
use Matican\Models\Repository\ItemModel;
use Matican\Models\Repository\ItemTypeModel;
use Matican\Models\Repository\SpecKeyModel;
use Matican\Models\Repository\SpecKeyValueModel;
use App\Params;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\Core\Transaction\Response;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/repository/item", name="repository_item")
 */
class ItemController extends AbstractController
{
    /**
     * @Route("/list", name="_repository_item_list")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fetchAll()
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_item_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_item_fetch);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_color_all);
        $canDuplicate = AuthUser::if_is_allowed('repository_item_duplicate');

        if ($canSeeAll) {
            if (Cache::is_cached(Servers::Repository, Repository::Item, 'all')) {
                $responseContent = Cache::get_cached(Servers::Repository, Repository::Item, 'all');
            } else {
                Cache::cache_action(Servers::Repository, Repository::Item, 'all');
                $request = new Req(Servers::Repository, Repository::Item, 'all');
                $response = $request->send();
                $responseContent = $response->getContent();
            }

            /**
             * @var $items ItemModel[]
             */
            $items = [];
            foreach ($responseContent as $item) {
                $items[] = ModelSerializer::parse($item, ItemModel::class);
            }
            return $this->render('repository/item/list.html.twig', [
                'controller_name' => 'ItemController',
                'items' => $items,
                'canCreate' => $canCreate,
                'canEdit' => $canEdit,
                'canSeeAll' => $canSeeAll,
                'canDuplicate' => $canDuplicate,
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_item_repository_item_create'));
        }
    }

    /**
     * @Route("/create", name="_repository_item_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_item_new);

        if ($canCreate) {

            $inputs = $request->request->all();

            /**
             * @var $itemModel ItemModel
             */
            $itemModel = ModelSerializer::parse($inputs, ItemModel::class);
//        dd($itemModel);
            if (!empty($inputs)) {
//            dd($inputs);
                $request = new Req(Servers::Repository, Repository::Item, 'new');
                $request->add_instance($itemModel);
                $response = $request->send();
//            dd($response);
                if ($response->getStatus() == ResponseStatus::successful) {
                    /**
                     * @var $itemModel ItemModel
                     */
                    Cache::cache_action(Servers::Repository, Repository::Item, 'all');
                    $itemModel = ModelSerializer::parse($response->getContent(), ItemModel::class);
                    $this->addFlash('s', $response->getMessage());

                    return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $itemModel->getItemID()]));
                }
                $this->addFlash('f', $response->getMessage());
            }
            $allBrandsRequest = new Req(Servers::Repository, Repository::Brand, 'all');
            $allBrandsResponse = $allBrandsRequest->send();

            /**
             * @var $brands BrandModel[]
             */
            $brands = [];
            foreach ($allBrandsResponse->getContent() as $brand) {
                $brands[] = ModelSerializer::parse($brand, BrandModel::class);
            }

            $allItemTypesRequest = new Req(Servers::Repository, Repository::Item, 'get_types');
            $allItemTypesResponse = $allItemTypesRequest->send();

            /**
             * @var $itemTypes ItemTypeModel[]
             */
            $itemTypes = [];
            foreach ($allItemTypesResponse->getContent() as $itemType) {
                $itemTypes[] = ModelSerializer::parse($itemType, ItemTypeModel::class);
            }
            Cache::cache_action(Servers::Repository, Repository::Item, 'all');
            return $this->render('repository/item/create.html.twig', [
                'controller_name' => 'ItemController',
                'itemModel' => $itemModel,
                'brands' => $brands,
                'itemTypes' => $itemTypes,
                'canCreate' => $canCreate
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_item_repository_item_list'));
        }

    }

    /**
     * @Route("/edit/{id}", name="_repository_item_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $referrer = null;
//        Cache::cache_action(Servers::Repository, Repository::Brand, 'all');
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != "") {
            $referrer = $_SERVER['HTTP_REFERER'];
        }

        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::repository_item_update);

        if ($canUpdate) {
            $inputs = $request->request->all();


            if (Cache::is_record_cached(Servers::Repository, Repository::Item, 'fetch', $id)) {
                $responseContent = Cache::get_cached_record(Servers::Repository, Repository::Item, 'fetch', $id);
            } else {
                /**
                 * @var $itemModel ItemModel
                 */
                $itemModel = ModelSerializer::parse($inputs, ItemModel::class);
                $itemModel->setItemID($id);
                $request = new Req(Servers::Repository, Repository::Item, 'fetch');
                $request->add_instance($itemModel);
                $response = $request->send();
                $responseContent = $response->getContent();
                Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $id, $responseContent);
            }

            $itemModel = ModelSerializer::parse($responseContent, ItemModel::class);
            if (!empty($inputs)) {
                $itemModel = ModelSerializer::parse($inputs, ItemModel::class);
                $itemModel->setItemID($id);
                $request = new Req(Servers::Repository, Repository::Item, 'update');
                $request->add_instance($itemModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());

                    /*
      * Cache
      */
                    $itemModel = new ItemModel();
                    $itemModel->setItemID($id);
                    $cacheRequest = new Req(Servers::Repository, Repository::Item, 'fetch');
                    $cacheRequest->add_instance($itemModel);
                    $response = $cacheRequest->send();
                    $responseContent = $response->getContent();


                    Cache::cache_action(Servers::Repository, Repository::Item, 'all');
                    Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $id, $responseContent);

//                    dd($response);

                    return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $id]));
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }


            if (Cache::is_cached(Servers::Repository, Repository::Brand, 'all')) {
                $allBrandsResponseContent = Cache::get_cached(Servers::Repository, Repository::Brand, 'all');
            } else {
                Cache::cache_action(Servers::Repository, Repository::Brand, 'all');

                $allBrandsRequest = new Req(Servers::Repository, Repository::Brand, 'all');
                $allBrandsResponse = $allBrandsRequest->send();
                $allBrandsResponseContent = $allBrandsResponse->getContent();
            }


            /**
             * @var $brands BrandModel[]
             */
            $brands = [];
            foreach ($allBrandsResponseContent as $brand) {
                $brands[] = ModelSerializer::parse($brand, BrandModel::class);
            }

//            if (Cache::is_cached(Servers::Repository, Repository::Item, 'get_types')) {
//
//                $allItemTypesResponseContent = Cache::get_cached(
//                    Servers::Repository,
//                    Repository::Item,
//                    'get_types');
//
//            } else {
//                Cache::cache_action(Servers::Repository, Repository::Item, 'get_types');
//                $allItemTypesRequest = new Req(Servers::Repository, Repository::Item, 'get_types');
//                $allItemTypesResponse = $allItemTypesRequest->send();
//                $allItemTypesResponseContent = $allItemTypesResponse->getContent();
//            }
//
//            /**
//             * @var $itemTypes ItemTypeModel[]
//             */
//            $itemTypes = [];
//
//            if ($allItemTypesResponseContent) {
//                foreach ($allItemTypesResponseContent as $itemType) {
//                    $itemTypes[] = ModelSerializer::parse($itemType, ItemTypeModel::class);
//                }
//            }

            if (Cache::is_cached(Servers::Repository, Repository::Color, 'all')) {
                $allColorsResponseContent = Cache::get_cached(Servers::Repository, Repository::Color, 'all');
            } else {
                Cache::cache_action(Servers::Repository, Repository::Color, 'all');

                $allColorsRequest = new Req(Servers::Repository, Repository::Color, 'all');
                $allColorsResponse = $allColorsRequest->send();
                $allColorsResponseContent = $allColorsResponse->getContent();
            }


            /**
             * @var $colors ItemColorModel[]
             */
            $colors = [];
            if ($allColorsResponseContent) {
                foreach ($allColorsResponseContent as $color) {
                    $colors[] = ModelSerializer::parse($color, ItemColorModel::class);
                }
            }

            if (Cache::is_cached(Servers::Repository, Repository::Guarantee, 'all')) {
                $guaranteeResponseContent = Cache::get_cached(Servers::Repository, Repository::Guarantee, 'all');
            } else {
                Cache::cache_action(Servers::Repository, Repository::Guarantee, 'all');
                $guaranteeRequest = new Req(Servers::Repository, Repository::Guarantee, 'all');
                $guaranteeResponse = $guaranteeRequest->send();
                $guaranteeResponseContent = $guaranteeResponse->getContent();
            }

            /**
             * @var $guarantees GuaranteeModel[]
             */
            $guarantees = [];
            foreach ($guaranteeResponseContent as $guarantee) {
                $guarantees[] = ModelSerializer::parse($guarantee, GuaranteeModel::class);
            }

            $brandSuppliersModel = new BrandSuppliersModel();
            $brandSuppliersModel->setBrandId($itemModel->getItemBrandId());
            $supplierRequest = new Req(Servers::Repository, Repository::Brand, 'get_suppliers');
            $supplierRequest->add_instance($brandSuppliersModel);
            $supplierResponse = $supplierRequest->send();
            $supplierContent = $supplierResponse->getContent();


            /**
             * @var $suppliers CompanyModel[]
             */
            $suppliers = [];
            if ($supplierContent) {
                if ($supplierContent['brandSuppliers']) {
                    foreach ($supplierContent['brandSuppliers'] as $supplier) {
                        $suppliers[] = ModelSerializer::parse($supplier, CompanyModel::class);
                    }
                }
            }
            if (Cache::is_cached(Servers::Repository, Repository::ItemCategory, 'all')) {

                $allItemCategoriesResponseContent = Cache::get_cached(Servers::Repository, Repository::ItemCategory, 'all');
            } else {
                Cache::cache_action(Servers::Repository, Repository::ItemCategory, 'all');
                $allItemCategoriesRequest = new Req(Servers::Repository, Repository::ItemCategory, 'all');
                $allItemCategoriesResponse = $allItemCategoriesRequest->send();
                $allItemCategoriesResponseContent = $allItemCategoriesResponse->getContent();


            }


            $itemCategories = json_decode(json_encode($allItemCategoriesResponseContent), true);


            foreach ($itemCategories as $key => $itemCategory) {
//
                /**
                 * @var $itemCategoryModel ItemCategoryModel
                 */
//                $itemCategoryModel = ModelSerializer::parse($itemCategory, ItemCategoryModel::class);
                if ($itemModel->getItemCategoriesIds()) {
                    if (in_array($itemCategory['category'][0]['itemCategoryID'], $itemModel->getItemCategoriesIds())) {
                        $itemCategories[$key]['category']['is_checked'] = true;
                    }
                }
//
            }

//        print_r(json_encode($itemModel->getItemSpecGroupsKeys())); die('s');

            $specGroupsKeys = json_decode(json_encode($itemModel->getItemSpecGroupsKeys()), true);
//        print_r($specGroupsKeys); die;

            /**
             * @var $itemImages ImageModel[]
             */
            $itemImages = [];
            if ($itemModel->getItemImages()) {
                foreach ($itemModel->getItemImages() as $itemImage) {
                    $itemImages[] = ModelSerializer::parse($itemImage, ImageModel::class);
                }
            }

            return $this->render('repository/item/edit.html.twig', [
                'controller_name' => 'ItemController',
                'itemModel' => $itemModel,
                'brands' => $brands,
//                'itemTypes' => $itemTypes,
                'colors' => $colors,
                'guarantees' => $guarantees,
                'suppliers' => $suppliers,
                'itemCategories' => $itemCategories,
                'specGroupKeys' => $specGroupsKeys,
                'canUpdate' => $canUpdate,
                'itemImages' => $itemImages,
                'referrer' => $referrer,
            ]);
        } else {
            return $this->redirect($this->generateUrl('repository_item_repository_item_list'));
        }


    }


    /**
     * @Route("/duplicate/{id}", name="_duplicate")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function duplicate($id)
    {
        $itemModel = new ItemModel();
        $itemModel->setItemID($id);
        $request = new Req(Servers::Repository, Repository::Item, 'duplicate');
        $request->add_instance($itemModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        Cache::cache_action(Servers::Repository, Repository::Item, 'all');
        return $this->redirect($this->generateUrl('repository_item_repository_item_list'));
    }


    /**
     * @Route("/add_barcode/{item_id}", name="_repository_item_add_barcode")
     * @param $item_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addBarcode($item_id, Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $barcodeModel BarcodeModel
         */
        $barcodeModel = ModelSerializer::parse($inputs, BarcodeModel::class);
        $barcodeModel->setItemId($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'add_barcode');
        $request->add_instance($barcodeModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }

        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));


    }

    /**
     * @Route("/remove_barcode/{barcode_id}/{item_id}", name="_repository_item_remove_barcode")
     * @param $barcode_id
     * @param $item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeBarcode($barcode_id, $item_id)
    {
        $barcodeModel = new BarcodeModel();
        $barcodeModel->setBarcodeId($barcode_id);
        $barcodeModel->setItemId($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'remove_barcode');
        $request->add_instance($barcodeModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }

        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);


        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }


    /**
     * @Route("/add_color/{item_id}", name="_repository_item_add_color")
     * @param $item_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addColor($item_id, Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $colorModel ItemColorModel
         */
        $colorModel = ModelSerializer::parse($inputs, ItemColorModel::class);
        $colorModel->setItemId($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'add_available_color');
        $request->add_instance($colorModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }


    /**
     * @Route("/remove_color/{color_id}/{item_id}", name="_repository_item_remove_color")
     * @param $color_id
     * @param $item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeColor($color_id, $item_id)
    {
        $colorModel = new ItemColorModel();
        $colorModel->setItemColorID($color_id);
        $colorModel->setItemId($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'remove_available_color');
        $request->add_instance($colorModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }


    /**
     * @Route("/add_guarantee/{item_id}", name="_repository_item_add_guarantee")
     * @param $item_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addGuarantee($item_id, Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $guaranteeModel GuaranteeModel
         */
        $guaranteeModel = ModelSerializer::parse($inputs, GuaranteeModel::class);
        $guaranteeModel->setItemId($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'add_available_guarantee');
        $request->add_instance($guaranteeModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }


    /**
     * @Route("/remove_guarantee/{guarantee_id}/{item_id}", name="_repository_item_remove_guarantee")
     * @param $guarantee_id
     * @param $item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeGuarantee($guarantee_id, $item_id)
    {
        $guaranteeModel = new GuaranteeModel();
        $guaranteeModel->setGuaranteeID($guarantee_id);
        $guaranteeModel->setItemId($item_id);
//        dd($guaranteeModel);
        $request = new Req(Servers::Repository, Repository::Item, 'remove_available_guarantee');
        $request->add_instance($guaranteeModel);
//        dd($request);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }


    /**
     * @Route("/add_supplier/{item_id}", name="_repository_item_add_supplier")
     * @param $item_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addSupplier($item_id, Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $supplierModel CompanyModel
         */
        $supplierModel = ModelSerializer::parse($inputs, CompanyModel::class);
        $supplierModel->setItemId($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'add_available_supplier');
        $request->add_instance($supplierModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }


    /**
     * @Route("/remove_supplier/{supplier_id}/{item_id}", name="_repository_item_remove_supplier")
     * @param $supplier_id
     * @param $item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeSupplier($supplier_id, $item_id)
    {
        $supplierModel = new CompanyModel();
        $supplierModel->setCompanyID($supplier_id);
        $supplierModel->setItemId($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'remove_available_supplier');
        $request->add_instance($supplierModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'fetch');
        $request->add_instance($itemModel);
        $response = $request->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }

    /**
     * @Route("/add_image/{item_id}", name="_repository_item_add_image")
     * @param Request $request
     * @param $item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addImage(Request $request, $item_id)
    {
        $file = $request->files->get('item_image');

        $uploadRequest = new Req(Servers::Repository, Repository::Item, 'add_image');
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        /**
         * @var $uploadResponse Response
         */
        $uploadResponse = $uploadRequest->uploadImage($file, $itemModel);
        if ($uploadResponse->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $uploadResponse->getMessage());
        }
        $this->addFlash('f', $uploadResponse->getMessage());

        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $cacheRequest = new Req(Servers::Repository, Repository::Item, 'fetch');
        $cacheRequest->add_instance($itemModel);
        $response = $cacheRequest->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }

    /**
     * @Route("/remove_image/{image_id}/{item_id}", name="_repository_item_remove_image")
     * @param $image_id
     * @param $item_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeImage($image_id, $item_id)
    {
        $imageModel = new ImageModel();
        $imageModel->setImageSerial($image_id);
        $imageModel->setItemID($item_id);
        $request = new Req(Servers::Repository, Repository::Item, 'remove_image');
        $request->add_instance($imageModel);
//        dd($request);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $cacheRequest = new Req(Servers::Repository, Repository::Item, 'fetch');
        $cacheRequest->add_instance($itemModel);
        $response = $cacheRequest->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));

    }

    /**
     * @Route("/update_categories/{item_id}", name="_repository_item_update_categories")
     * @param $item_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function updateCategories($item_id, Request $request)
    {
        $inputs = $request->request->all();

        /**
         * @var $itemCategoriesModel ItemCategoriesModel
         */
        $itemCategoriesModel = ModelSerializer::parse($inputs, ItemCategoriesModel::class);
        $itemCategoriesModel->setItemId($item_id);
//        dd($itemCategoriesModel);
        $request = new Req(Servers::Repository, Repository::Item, 'update_category');
        $request->add_instance($itemCategoriesModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $cacheRequest = new Req(Servers::Repository, Repository::Item, 'fetch');
        $cacheRequest->add_instance($itemModel);
        $response = $cacheRequest->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }


    /**
     * @Route("/submit-key-value", name="_submit_key_value")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function submitKeyValue(Request $request)
    {
        $inputs = $request->request->all();
//        dd($inputs);
        /**
         * @var $specKeyValueModel SpecKeyValueModel
         */
        $specKeyValueModel = ModelSerializer::parse($inputs, SpecKeyValueModel::class);
//        $specKeyModel->setSpecKeySpecGroupName();
        $request = new Req(Servers::Repository, Repository::SpecKey, 'submit_key_value');
        $request->add_instance($specKeyValueModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        $item_id = $specKeyValueModel->getItemId();
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $cacheRequest = new Req(Servers::Repository, Repository::Item, 'fetch');
        $cacheRequest->add_instance($itemModel);
        $response = $cacheRequest->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);

        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $specKeyValueModel->getItemId()]));
    }

    /**
     * @Route("/remove-key-value/{item_id}/{key_id}/{value}", name="_remove_key_value")
     * @param $item_id
     * @param $key_id
     * @param $value
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeKeyValue($item_id, $key_id, $value)
    {
        $keyValueModel = new SpecKeyValueModel();
        $keyValueModel->setItemId($item_id);
        $keyValueModel->setKeyId($key_id);
        $keyValueModel->setValue($value);

//        dd($keyValueModel);

        $request = new Req(Servers::Repository, Repository::SpecKey, 'remove_key_value');
        $request->add_instance($keyValueModel);
        $response = $request->send();
//        dd($response);
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        /*
         * Cache
         */
        $itemModel = new ItemModel();
        $itemModel->setItemID($item_id);
        $cacheRequest = new Req(Servers::Repository, Repository::Item, 'fetch');
        $cacheRequest->add_instance($itemModel);
        $response = $cacheRequest->send();
        $responseContent = $response->getContent();

        Cache::cache_record(Servers::Repository, Repository::Item, 'fetch', $item_id, $responseContent);
        return $this->redirect($this->generateUrl('repository_item_repository_item_edit', ['id' => $item_id]));
    }
}
