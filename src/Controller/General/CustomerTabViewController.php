<?php

namespace App\Controller\General;

use Matican\ModelSerializer;
use Matican\Models\Repository\PersonModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomerTabViewController extends AbstractController
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        $inputs = $request->request->all();
        /**
         * @var $personModel PersonModel
         */
        $personModel = ModelSerializer::parse($inputs, PersonModel::class);

        return $this->render('general/customer_tab_view/index.html.twig', [
            'controller_name' => 'CustomerTabViewController',
            'personModel' => $personModel,
        ]);
    }
}
