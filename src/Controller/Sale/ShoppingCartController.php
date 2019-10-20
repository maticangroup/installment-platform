<?php

namespace App\Controller\Sale;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sale/shopping-cart", name="sale_shopping_cart")
 */
class ShoppingCartController extends AbstractController
{
    /**
     * @Route("/list", name="sale_shopping_cart_list")
     */
    public function fetchAll()
    {
        return $this->render('sale/shopping_cart/index.html.twig', [
            'controller_name' => 'ShoppingCartController',
        ]);
    }
}
