<?php

namespace App\Controller\Repository;

use App\Cache;
use Matican\ModelSerializer;
use Matican\Models\Repository\ItemCategoryModel;
use Matican\Models\Repository\ItemCategorySpecKeyModel;
use Matican\Models\Repository\SizeModel;
use Matican\Models\Repository\SpecGroupModel;
use Matican\Models\Repository\SpecKeyModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Matican\Models\Repository\ItemCategory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/repository/item-category", name="repository_item_category")
 */
class ItemCategoryController extends AbstractController
{

    /**
     * @Route("/create", name="_repository_item_category_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_itemcategory_new);
        $canEdit = AuthUser::if_is_allowed(ServerPermissions::repository_itemcategory_fetch);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_itemcategory_all);

        $inputs = $request->request->all();

        /**
         * @var $itemCategoryModel ItemCategoryModel
         */
        $itemCategoryModel = ModelSerializer::parse($inputs, ItemCategoryModel::class);

        /**
         * @var $sizes SizeModel[]
         */
        $sizes = [];
        $allSizesRequest = new Req(Servers::Repository, Repository::Size, 'all');
        $allSizesResponse = $allSizesRequest->send();
        if ($allSizesResponse->getContent()) {
            foreach ($allSizesResponse->getContent() as $size) {
                $sizes[] = ModelSerializer::parse($size, SizeModel::class);
            }
        }

        if (!empty($inputs)) {
            if ($canCreate) {
                $coreRequest = new Req(Servers::Repository, Repository::ItemCategory, 'new');
                $file = $request->files->get('itemCategoryImageUrl');
//                dd($itemCategoryModel);
                if ($file) {
                    $response = $coreRequest->uploadImage($file, $itemCategoryModel);
                } else {
                    $response = $coreRequest->uploadImage(null, $itemCategoryModel);
                }
//                dd($response);
                if ($response->getStatus() == ResponseStatus::successful) {
                    /**
                     * @var $newItemCategory ItemCategoryModel
                     */
                    $newItemCategory = ModelSerializer::parse($response->getContent(), ItemCategoryModel::class);
                    $this->addFlash('s', $response->getMessage());
                    return $this->redirect($this->generateUrl('repository_item_category_repository_item_category_edit', ['id' => $newItemCategory->getItemCategoryID()]));
                } else {
                    $this->addFlash('f', $response->getMessage());
                }
            }
        }

        /**
         * @var $itemCategories ItemCategoryModel[]
         */
        $itemCategories = [];
        if ($canSeeAll) {
            $allItemCategoriesRequest = new Req(Servers::Repository, Repository::ItemCategory, 'all');
            $allItemCategoriesResponse = $allItemCategoriesRequest->send();
            $itemCategories = $allItemCategoriesResponse->getContent();
        }

//        dd($itemCategories);
        Cache::cache_action(Servers::Repository, Repository::ItemCategory, 'all');

        return $this->render('repository/item_category/create.html.twig', [
            'controller_name' => 'ItemCategoryController',
            'itemCategoryModel' => $itemCategoryModel,
            'itemCategories' => $itemCategories,
            'sizes' => $sizes,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canEdit' => $canEdit,

        ]);
    }


    /**
     * @Route("/edit/{id}", name="_repository_item_category_edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function edit($id, Request $request)
    {
        $canUpdate = AuthUser::if_is_allowed(ServerPermissions::repository_itemcategory_update);
        $canAssignKeys = AuthUser::if_is_allowed(ServerPermissions::repository_itemcategory_assign_keys);

        $inputs = $request->request->all();
        /**
         * @var $itemCategoryModel ItemCategoryModel
         */
        $itemCategoryModel = ModelSerializer::parse($inputs, ItemCategoryModel::class);

        /**
         * @var $itemCategories ItemCategoryModel[]
         */
        $itemCategories = [];

        /**
         * @var $sizes SizeModel[]
         */
        $sizes = [];

        if ($canUpdate) {
            $itemCategoryModel->setItemCategoryID($id);
            $updateRequest = new Req(Servers::Repository, Repository::ItemCategory, 'fetch');
            $updateRequest->add_instance($itemCategoryModel);
            $response = $updateRequest->send();

            $itemCategoryModel = ModelSerializer::parse($response->getContent(), ItemCategoryModel::class);

//            dd($itemCategoryModel);

            if (!empty($inputs)) {
                $itemCategoryModel = ModelSerializer::parse($inputs, ItemCategoryModel::class);
                $itemCategoryModel->setItemCategoryID($id);
                $updateRequest = new Req(Servers::Repository, Repository::ItemCategory, 'update');
                $file = $request->files->get('itemCategoryImageUrl');
                if ($file) {
                    $response = $updateRequest->uploadImage($file, $itemCategoryModel);
                } else {
                    $response = $updateRequest->uploadImage(null, $itemCategoryModel);
                }
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                    return $this->redirect($this->generateUrl('repository_item_category_repository_item_category_edit', ['id' => $id]));
                }
                $this->addFlash('f', $response->getMessage());
            }

            $allItemCategoriesRequest = new Req(Servers::Repository, Repository::ItemCategory, 'all');
            $allItemCategoriesResponse = $allItemCategoriesRequest->send();
            $itemCategories = $allItemCategoriesResponse->getContent();

            $allSizesRequest = new Req(Servers::Repository, Repository::Size, 'all');
            $allSizesResponse = $allSizesRequest->send();
            if ($allSizesResponse->getContent()) {
                foreach ($allSizesResponse->getContent() as $size) {
                    $sizes[] = ModelSerializer::parse($size, SizeModel::class);
                }
            }
        }


        /**
         * @var $specGroups SpecGroupModel[]
         */
        $specGroups = [];
        if ($canAssignKeys) {
            $allSpecGroupsRequest = new Req(Servers::Repository, Repository::SpecGroup, 'all');
            $allSpecGroupResponse = $allSpecGroupsRequest->send();
            $itemCategorySpecKeyIds = $itemCategoryModel->getItemCategorySpecKeysIds();
            if ($allSpecGroupResponse->getContent()) {
                foreach ($allSpecGroupResponse->getContent() as $specGroup) {
                    /**
                     * @var $specGroupModel SpecGroupModel
                     */
                    $specGroupModel = ModelSerializer::parse($specGroup, SpecGroupModel::class);
                    if ($specGroupModel->getSpecGroupSpecKeys()) {
                        $keys = $specGroupModel->getSpecGroupSpecKeys();
                        foreach ($keys as $key => $specKey) {
                            /**
                             * @var $specKeyModel SpecKeyModel
                             */
                            $specKeyModel = ModelSerializer::parse($specKey, SpecKeyModel::class);
                            if (is_array($itemCategorySpecKeyIds)) {
                                if (in_array($specKeyModel->getSpecKeyID(), $itemCategorySpecKeyIds)) {

                                    $specKeyModel->setSpecKeyIsChecked(true);
                                }
                                $keys[$key] = $specKeyModel;
                            }

                        }
                        $specGroupModel->setSpecGroupSpecKeys($keys);
                    }
                    $specGroups[] = $specGroupModel;
                }
            }
        }
        Cache::cache_action(Servers::Repository, Repository::ItemCategory, 'all');

        return $this->render('repository/item_category/edit.html.twig', [
            'controller_name' => 'ItemCategoryController',
            'itemCategoryModel' => $itemCategoryModel,
            'itemCategories' => $itemCategories,
            'specGroups' => $specGroups,
            'sizes' => $sizes,
            'canUpdate' => $canUpdate,
            'canAssignKeys' => $canAssignKeys,

        ]);
    }


    /**
     * @Route("/include-spec-key/{category_id}/", name="_repository_item_category_include")
     * @param Request $request
     * @param $category_id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function includeSpecKeys(Request $request, $category_id)
    {
        $inputs = $request->request->all();

        /**
         * @var $specKeyModel ItemCategorySpecKeyModel
         */
        $specKeyModel = ModelSerializer::parse($inputs, ItemCategorySpecKeyModel::class);

        $specKeyModel->setItemCategoryId($category_id);
        $request = new Req(Servers::Repository, Repository::ItemCategory, 'assign_keys');
        $request->add_instance($specKeyModel);
        $response = $request->send();

        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        Cache::cache_action(Servers::Repository, Repository::ItemCategory, 'all');

        return $this->redirect($this->generateUrl('repository_item_category_repository_item_category_edit', ['id' => $category_id]));
    }
}
