<?php

namespace App\Controller\Accounting;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/accounting/credit-deed", name="accounting_credit_deed")
 */
class CreditDeedController extends AbstractController
{
    /**
     * @Route("/list", name="accounting_credit_deed_list")
     */
    public function fetchAll()
    {
        return $this->render('accounting/credit_deed/list.html.twig', [
            'controller_name' => 'CreditDeedController',
        ]);
    }

    /**
     * @Route("/add", name="accounting_credit_deed_add")
     */
    public function add()
    {
        return $this->render('accounting/credit_deed/add,html.twig', [
            'controller_name' => 'CreditDeedController',
        ]);
    }

    /**
     * @Route("/read", name="accounting_credit_deed_read")
     */
    public function read()
    {
        return $this->render('accounting/credit_deed/read.html.twig', [
            'controller_name' => 'CreditDeedController',
        ]);
    }
}
