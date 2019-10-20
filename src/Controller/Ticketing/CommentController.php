<?php

namespace App\Controller\Ticketing;

use Matican\ModelSerializer;
use Matican\Models\Ticketing\CommentModel;
use Matican\Models\Ticketing\ItemCommentsModel;
use Matican\Core\Entities\Ticketing;
use Matican\Core\Servers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Matican\Core\Transaction\Request as Req;

/**
 * @Route("/ticketing/comment", name="ticketing_comment")
 */
class CommentController extends AbstractController
{
    /**
     * @Route("/list", name="_list")
     */
    public function fetchAll()
    {
        $request = new Req(Servers::Accounting, Ticketing::Comment, 'all');
        $response = $request->send();

        /**
         * @var $itemComments ItemCommentsModel[]
         */
        $itemComments = [];
        if ($response->getContent()) {
            foreach ($response->getContent() as $comment) {
                $itemComments[] = ModelSerializer::parse($comment, ItemCommentsModel::class);
            }
        }


        return $this->render('ticketing/comment/list.html.twig', [
            'controller_name' => 'CommentController',
            'itemComments' => $itemComments,
        ]);
    }

    /**
     * @Route("/read/{id}", name="_read")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \ReflectionException
     */
    public function read($id, Request $request)
    {
        $inputs = $request->request->all();
        /**
         * @var $itemCommentsModel ItemCommentsModel
         */
        $itemCommentsModel = ModelSerializer::parse($inputs, ItemCommentsModel::class);
        $itemCommentsModel->setItemCommentId($id);
        $request = new Req(Servers::Accounting, Ticketing::Comment, 'all');
        $request->add_instance($itemCommentsModel);
        $response = $request->send();
        /**
         * @var $itemCommentsModel ItemCommentsModel
         */
        $itemCommentsModel = ModelSerializer::parse($response->getContent(), ItemCommentsModel::class);

        /**
         * @var $comments CommentModel[]
         */
        $comments = [];
        if ($itemCommentsModel->getItemCommentComments()) {
            foreach ($itemCommentsModel->getItemCommentComments() as $comment) {
                $comments[] = ModelSerializer::parse($comment, CommentModel::class);
            }
        }


        return $this->render('ticketing/comment/read.html.twig', [
            'controller_name' => 'CommentController',
            'itemCommentsModel' => $itemCommentsModel,
            'comments' => $comments,
        ]);
    }

    public function addComment()
    {

    }

    public function approveComment()
    {

    }

    public function spamComment()
    {

    }

    public function readComment()
    {

    }
}
