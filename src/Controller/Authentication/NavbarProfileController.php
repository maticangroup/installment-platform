<?php

namespace App\Controller\Authentication;

use Matican\Authentication\AuthUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class NavbarProfileController extends AbstractController
{
    /**
     * @Route("/authentication/navbar/profile", name="authentication_navbar_profile")
     */
    public function index()
    {
        $currentUser = AuthUser::current_user();
        $userName = "";
        if ($currentUser->getUserMobile()) {
            $userName = $currentUser->getUserMobile();
        }
        if ($currentUser->getUserName()) {
            $userName = $currentUser->getUserName();
        }
        return $this->render('authentication/navbar_profile/index.html.twig', [
            'controller_name' => 'NavbarProfileController',
            'userName' => $userName,
            'userId' => $currentUser->getUserId()
        ]);
    }
}
