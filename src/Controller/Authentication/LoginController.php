<?php

namespace App\Controller\Authentication;


use Matican\Authentication\AuthUser;
use Matican\Models\Authentication\ClientModel;
use Matican\Models\Authentication\UserModel;
use Matican\Models\Repository\ItemModel;
use Matican\ModelSerializer;

//use Grpc\Server;
use Matican\Core\Entities\Authentication;
use Matican\Core\Servers;
use Matican\Core\Transaction\Response;

use Matican\ResponseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;
use Symfony\Component\Validator\Constraints\Json;

//use Matican\Core\Transaction\Request as Req;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="authentication_login")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read(Request $request)
    {
        AuthUser::logout();

        $inputs = $request->request->all();
        /**
         * @var $userModel UserModel
         */
        $userModel = ModelSerializer::parse($inputs, UserModel::class);

        if ($request->query->get('reseller_token')) {
            $clientModel = new ClientModel();
            $clientModel->setAccessToken($request->query->get('reseller_token'));
            $clientRequest = new Req(Servers::Authentication, Authentication::Client, 'fetch_by_access_token');
            $clientRequest->add_instance($clientModel);
            $response = $clientRequest->send();
            /**
             * @var $clientModel ClientModel
             */
            $clientModel = ModelSerializer::parse($response->getContent(), ClientModel::class);
            if (!isset($_SESSION['http_referrer'])) {
                $_SESSION['http_referrer'] = $_SERVER['HTTP_REFERER'];
            }
        } else {
            $clientModel = null;
        }
        if (!empty($inputs['password_button'])) {
//            dd('password_button');
            $userModel->setUserMobile($inputs['userMobile']);
            $passwordRequest = new Req(Servers::Authentication, Authentication::User, 'send_password');
            $passwordRequest->add_instance($userModel);
            $response = $passwordRequest->send();
            if ($response->getStatus() == ResponseStatus::successful) {
                $this->addFlash('s', $response->getMessage());

            } else {
                $this->addFlash('f', $response->getMessage());
            }
        }

        if (!empty($inputs['login_button'])) {
//            dd('login_button');
            $userModel->setUserMobile($inputs['userMobile']);
            $userModel->setUserPassword($inputs['userPassword']);
//            dd($userModel);
            $loginRequest = new Req(Servers::Authentication, Authentication::User, 'login');
            $loginRequest->add_instance($userModel);
            $response = $loginRequest->send();
//            dd($response);
            if ($response->getStatus() == ResponseStatus::successful) {

                $userModel = ModelSerializer::parse($response->getContent(), UserModel::class);
//                $userModel->setUserPassword('');

                AuthUser::login($userModel);

                AuthUser::purge_role_permissions();

                $this->addFlash('s', $response->getMessage());
                if ($clientModel) {
                    $redirectURL = $clientModel->getClientDomain() .
                        $clientModel->getAuthenticationTerminalUrl() .
                        "&userName=" .
                        $userModel->getUserName() . "&userPassword=" .
                        $userModel->getUserPassword() . "&personId=" . $userModel->getPersonId();
                    if (isset($_SESSION['http_referrer'])) {
                        $referrer = $_SESSION['http_referrer'];
                        unset($_SESSION['http_referrer']);
                        return $this->redirect($redirectURL . "&referrer=" . $referrer);
                    }
                    return $this->redirect($redirectURL);
                }
                return $this->redirect($this->generateUrl("default"));
            } else {
                $this->addFlash('f', $response->getMessage());
//                return $this->redirect($this->generateUrl('authentication_login'));
            }
        }
        if ($request->query->get('reseller_token')) {
            $reseller_token = $request->query->get('reseller_token');
        } else {
            $reseller_token = null;
        }

        return $this->render('authentication/login/read.html.twig', [
            'controller_name' => 'LoginController',
            'userModel' => $userModel,
            'reseller_token' => $reseller_token
        ]);
    }

    /**
     * @Route("/logout", name="authentication_logout")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \ReflectionException
     */
    public function logout()
    {
        $currentUserID = AuthUser::current_user()->getUserId();
        $request = new Req(Servers::Authentication, Authentication::User, 'logout');
        $userModel = new UserModel();
        $userModel->setUserId($currentUserID);
        $request->add_instance($userModel);
        $response = $request->send();
        if ($response->getStatus() == ResponseStatus::successful) {
            AuthUser::logout();
            return $this->redirect($this->generateUrl("authentication_login"));
        } else {
            return $this->redirect($this->generateUrl("default"));
        }
    }
}
