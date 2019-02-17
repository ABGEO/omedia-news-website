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

    /**
     * Show Single news
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showSingleNews($id)
    {
        $doctrine = $this->getDoctrine();
        $news = $doctrine->getRepository(News::class)->find($id);

        if (!$news)
            throw $this->createNotFoundException("News $id not found!");

        return $this->render('default/single_news.html.twig', [
            'news' => $news,
        ]);
    }
}
