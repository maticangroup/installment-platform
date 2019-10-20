<?php

namespace App\Controller\Accounting;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/accounting/payment", name="accounting_payment")
 */
class PaymentController extends AbstractController
{
    /**
     * @Route("/list", name="accounting_payment_list")
     */
    public function fetchAll()
    {
        return $this->render('accounting/payment/index.html.twig', [
            'controller_name' => 'PaymentController',
        ]);
    }

    /**
     * @Route("/add", name="accounting_payment_add")
     */
    public function add()
    {
        return $this->render('accounting/payment/index.html.twig', [
            'controller_name' => 'PaymentController',
        ]);
    }

    /**
     * @Route("/read", name="accounting_payment_read")
     */
    public function read()
    {
        return $this->render('accounting/payment/index.html.twig', [
            'controller_name' => 'PaymentController',
        ]);
    }
}
