<?php

namespace App\Controller\Ticketing;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/ticketing/comment-point", name="ticketing_comment_point")
 */
class CommentPointController extends AbstractController
{
    /**
     * @Route("/list", name="ticketing_comment_point_list")
     */
    public function fetchAll()
    {
        return $this->render('ticketing/comment_point/index.html.twig', [
            'controller_name' => 'CommentPointController',
        ]);
    }
}
