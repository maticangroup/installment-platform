<?php

namespace App\Controller\Notification;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/notification/newsletter", name="notification_newsletter")
 */
class NewsletterController extends AbstractController
{
    /**
     * @Route("/list", name="_notification_newsletter_list")
     */
    public function fetchAll()
    {
        return $this->render('notification/newsletter/list.html.twig', [
            'controller_name' => 'NewsletterController',
        ]);
    }

    /**
     * @Route("/create", name="_notification_newsletter_add_create")
     */
    public function create()
    {
        return $this->render('notification/newsletter/create.html.twig', [
            'controller_name' => 'NewsletterController',
        ]);
    }

    /**
     * @Route("/save", name="_notification_newsletter_add_save")
     */
    public function save()
    {
        return $this->render('notification/newsletter/edit.html.twig', [
            'controller_name' => 'NewsletterController',
        ]);
    }

    /**
     * @Route("/edit", name="_notification_newsletter_add_edit")
     */
    public function edit()
    {
        return $this->render('notification/newsletter/edit.html.twig', [
            'controller_name' => 'NewsletterController',
        ]);
    }

    /**
     * @Route("/update", name="_notification_newsletter_add_update")
     */
    public function update()
    {
        return $this->render('notification/newsletter/edit.html.twig', [
            'controller_name' => 'NewsletterController',
        ]);
    }

    /**
     * @Route("/subscribers", name="_notification_newsletter_subscribers")
     */
    public function subscribers()
    {
        return $this->render('notification/newsletter/subscribers.html.twig', [
            'controller_name' => 'NewsletterController',
        ]);
    }
}
