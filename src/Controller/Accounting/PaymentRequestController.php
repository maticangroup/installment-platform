<?php

namespace App\Controller\Accounting;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/accounting/payment-request", name="accounting_payment_request")
 */
class PaymentRequestController extends AbstractController
{
    /**
     * @Route("/list", name="accounting_payment_request_list")
     */
    public function fetchAll()
    {
        return $this->render('accounting/payment_request/list.html.twig', [
            'controller_name' => 'PaymentRequestController',
        ]);
    }

    /**
     * @Route("/add", name="accounting_payment_request_add")
     */
    public function add()
    {
        return $this->render('accounting/payment_request/add.html.twig', [
            'controller_name' => 'PaymentRequestController',
        ]);
    }

    /**
     * @Route("/read", name="accounting_payment_request_read")
     */
    public function read()
    {
        return $this->render('accounting/payment_request/read.html.twig', [
            'controller_name' => 'PaymentRequestController',
        ]);
    }
}
