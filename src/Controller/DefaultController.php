<?php

namespace App\Controller;

use App\Entity\News;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function index()
    {
        $doctrine = $this->getDoctrine();
        $news = $doctrine->getRepository(News::class)->findAll();

        return $this->render('default/index.html.twig', [
            'news' => $news,
        ]);
    }
}
