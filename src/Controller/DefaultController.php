<?php

namespace App\Controller;

use App\ClientConfig;
use Matican\Models\Authentication\UserModel;
use Matican\Models\Repository\ItemModel;
use Matican\Models\Repository\PersonModel;
use Matican\Authentication\AuthUser;
use Matican\Core\Servers;
use Matican\Core\Transaction\Request;
use Matican\Models\Media\Image;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default")
     */
    public function index()
    {
//        dd(AuthUser::current_user());
//        dd(AuthUser::class);

//        $request = new Request("test", "test", "test");
//        $response = $request->uploadImage(null, null);

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @Route("/upload_test", name="upload_test")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function uploadTest(\Symfony\Component\HttpFoundation\Request $request)
    {
        /**
         * @var $file UploadedFile
         */
        $file = $request->files->get('test_file');
        if ($file) {
            $image = new Image();
            $image->setName($file->getClientOriginalName());
            $image->setContent($file->getPathname());
            $image->setFileName($file->getPathname());
            $image->setMimeType($file->getMimeType());
            $coreRequest = new Request(Servers::Media, "Image", "upload");
            $response = $coreRequest->uploadImage($image, ['test', 'test2', 'tesss']);
            dd($response);
        }

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }
}
