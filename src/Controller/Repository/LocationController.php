<?php

namespace App\Controller\Repository;

use Matican\Core\Transaction\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/repository/location", name="repository_location")
 */
class LocationController extends AbstractController
{

    /**
     * @Route("/get-provinces", name="_get_provinces")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function getProvinces()
    {
        $request = new Request("Repository", "Location", "get_provinces");
        $response = $request->send();
        return $this->json($response->getContent());
    }

    /**
     * @Route("/get-cities/{id}", name="_get_cities")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \ReflectionException
     */
    public function getCities($id)
    {
        $request = new Request("Repository", "Location", "get_cities");

        $provinceModel = new \Matican\Models\Repository\ProvinceModel();
        $provinceModel->setProvinceId($id);
        $request->add_instance($provinceModel);
        $response = $request->send();
        return $this->json($response->getContent());
    }
}
