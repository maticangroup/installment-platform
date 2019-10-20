<?php

namespace App\Controller\General;

use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;


class ItemCategoryViewController extends AbstractController
{
    /**
     * @Route("/general/item/category/view", name="general_item_category_view")
     * @param $itemCategories
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index($itemCategories)
    {
//   dd($itemCategories);
        return $this->render('general/item_category_view/index.html.twig', [
            'itemCategories' => $itemCategories
        ]);
    }
}
