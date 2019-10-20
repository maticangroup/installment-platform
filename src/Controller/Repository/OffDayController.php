<?php

namespace App\Controller\Repository;

use Matican\ModelSerializer;
use Matican\Models\Repository\OffDayModel;
use Matican\Authentication\AuthUser;
use Matican\Permissions\ServerPermissions;
use Matican\Core\Entities\Repository;
use Matican\Core\Servers;
use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/repository/off-day", name="repository_off_day")
 */
class OffDayController extends AbstractController
{

    /**
     * @Route("/create", name="_repository_off_day_create")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function create(Request $request)
    {
        $canCreate = AuthUser::if_is_allowed(ServerPermissions::repository_offdays_new);
        $canSeeAll = AuthUser::if_is_allowed(ServerPermissions::repository_offdays_all);
        $canRemove = AuthUser::if_is_allowed(ServerPermissions::repository_offdays_delete);


        $inputs = $request->request->all();
        /**
         * @var $offDayModel OffDayModel
         */
        $offDayModel = ModelSerializer::parse($inputs, OffDayModel::class);
//        $years = [];
//        $months = [];
//        $days = [];

        if ($canCreate) {
            if (!empty($inputs)) {

                $request = new Req(Servers::Repository, Repository::OffDays, 'new');
                $request->add_instance($offDayModel);
                $response = $request->send();
                if ($response->getStatus() == ResponseStatus::successful) {
                    $this->addFlash('s', $response->getMessage());
                }
                $this->addFlash('f', $response->getMessage());
            }


//            for ($i = 1950; $i <= 2019; $i++) {
//                $years[] = $i;
//            }
//
//
//            for ($j = 1; $j <= 12; $j++) {
//                $months[] = $j;
//            }
//
//
//            for ($k = 1; $k <= 31; $k++) {
//                $days[] = $k;
//            }
        }


        /**
         * @var $offDays OffDayModel[]
         */
        $offDays = [];

        if ($canSeeAll) {
            $offDaysRequest = new Req(Servers::Repository, Repository::OffDays, 'all');
            $offDaysResponse = $offDaysRequest->send();
            if ($offDaysResponse->getContent()) {
                foreach ($offDaysResponse->getContent() as $offDay) {
                    $offDays[] = ModelSerializer::parse($offDay, OffDayModel::class);
                }
            }
        }

        return $this->render('repository/off_day/create.html.twig', [
            'controller_name' => 'OffDayController',
            'offDayModel' => $offDayModel,
            'offDays' => $offDays,
//            'years' => $years,
//            'months' => $months,
//            'days' => $days,
            'canCreate' => $canCreate,
            'canSeeAll' => $canSeeAll,
            'canRemove' => $canRemove,
        ]);
    }

    /**
     * @Route("/remove/{id}", name="_repository_off_day_remove")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function removeDayOff($id)
    {
        $offDayModel = new OffDayModel();
        $offDayModel->setOffDayID($id);
        $request = new Req(Servers::Repository, Repository::OffDays, 'delete');
        $request->add_instance($offDayModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            $this->addFlash('s', $response->getMessage());
        } else {
            $this->addFlash('f', $response->getMessage());
        }
        return $this->redirect($this->generateUrl('repository_off_day_repository_off_day_create'));
    }
}
