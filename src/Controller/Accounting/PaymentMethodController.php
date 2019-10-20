<?php

namespace App\Controller\Accounting;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/accounting/payment-method", name="accounting_payment_method")
 */
class PaymentMethodController extends AbstractController
{
    /**
     * @Route("/list", name="accounting_payment_method_list")
     */
    public function fetchAll()
    {
        return $this->render('accounting/payment_method/index.html.twig', [
            'controller_name' => 'PaymentMethodController',
        ]);
    }

    /**
     * @Route("/add", name="accounting_payment_method_add")
     */
    public function add()
    {
        return $this->render('accounting/payment_method/index.html.twig', [
            'controller_name' => 'PaymentMethodController',
        ]);
    }

    /**
     * @Route("/read", name="accounting_payment_method_read")
     */
    public function read()
    {
        return $this->render('accounting/payment_method/index.html.twig', [
            'controller_name' => 'PaymentMethodController',
        ]);
    }
}
