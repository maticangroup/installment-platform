<?php

namespace App\Controller\Accounting;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/accounting/invoice-group", name="accounting_invoice_group")
 */
class InvoiceGroupController extends AbstractController
{
    /**
     * @Route("/list", name="accounting_invoice_group_list")
     */
    public function fetchAll()
    {
        return $this->render('accounting/invoice_group/index.html.twig', [
            'controller_name' => 'InvoiceGroupController',
        ]);
    }

    /**
     * @Route("/add", name="accounting_invoice_group_add")
     */
    public function add()
    {
        return $this->render('accounting/invoice_group/index.html.twig', [
            'controller_name' => 'InvoiceGroupController',
        ]);
    }

    /**
     * @Route("/read", name="accounting_invoice_group_read")
     */
    public function read()
    {
        return $this->render('accounting/invoice_group/index.html.twig', [
            'controller_name' => 'InvoiceGroupController',
        ]);
    }
}
