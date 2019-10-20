<?php

namespace App\Controller\General;

use Matican\ModelSerializer;
use Matican\Models\Repository\LocationModel;
use Matican\Models\Repository\ProvinceModel;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;
use Symfony\Component\Validator\Constraints\Json;

class LocationViewController extends AbstractController
{
    /**
     * @Route("/general/location/view", name="general_location_view")
     * @param $locationModel LocationModel
     * @param $addedLocations
     * @param $submitUrl
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index($locationModel, $addedLocations, $submitUrl)
    {

        $locations = $addedLocations;

        $provincesRequest = new Req(Servers::Repository, Repository::Location, 'get_provinces');
        $provincesResponse = $provincesRequest->send();

        /**
         * @var $provinces ProvinceModel[]
         */
        $provinces = [];
        if ($provincesResponse->getContent()) {
            foreach ($provincesResponse->getContent() as $province) {
                $provinces[] = ModelSerializer::parse($province, ProvinceModel::class);
            }
        }

        return $this->render('general/location_view/index.html.twig', [
            'controller_name' => 'LocationViewController',
            'locations' => $locations,
            'locationModel' => $locationModel,
            'submitUrl' => $submitUrl,
            'provinces' => $provinces,
        ]);
    }


    /**
     * @param $locationModel LocationModel
     * @param $redirectCallBack
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function addLocation($locationModel, $redirectCallBack)
    {
        if ($locationModel->getProvinceName()) {
            $latLang = str_replace(' ', '', $locationModel->getLocationGeoPoints());
            $latLang = explode(',', $latLang);
            if (count($latLang) != 2) {
                $this->addFlash('f', 'geo points are not formatted correctly');
                return $this->redirect($redirectCallBack);
            }
            $locationModel->setLocationLat($latLang[0]);
            $locationModel->setLocationLng($latLang[1]);

            $request = new Req(Servers::Repository, Repository::Location, 'new');
            $request->add_instance($locationModel);
            $response = $request->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());
            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }
        return $this->redirect($redirectCallBack);
    }


    /**
     * @Route("/general/location/remove/{id}", name="general_location_remove")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeLocation($id, Request $request)
    {
        $redirect = $request->query->get('redirect');
        $locationModel = new LocationModel();
        $locationModel->setLocationId($id);
//        dd($locationModel);
        $request = new Req(Servers::Repository, Repository::Location, 'remove');
        $request->add_instance($locationModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($redirect);
    }
}
