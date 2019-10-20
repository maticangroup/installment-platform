<?php

namespace App\Controller\Accounting;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/accounting/credit-balance", name="accounting_credit_balance")
 */
class CreditBalanceController extends AbstractController
{
    /**
     * @Route("/list", name="accounting_credit_balance_list")
     */
    public function fetchAll()
    {
        return $this->render('accounting/credit_balance/list.html.twig', [
            'controller_name' => 'CreditBalanceController',
        ]);
    }
}
