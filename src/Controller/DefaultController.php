<?php

namespace App\Controller;

use App\Entity\News;
use App\Entity\Subscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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

    public function subscribeOnNews(Request $request)
    {
        $request = $request->request;

        $token = $request->get('token');
        $email = $request->get('email');

        if ($this->isCsrfTokenValid('subscribe-on-news', $token)) {

            $subscriber = new Subscriber();
            $subscriber->setEmail($email);

            $em = $this->getDoctrine()->getManager();

            $em->persist($subscriber);
            $em->flush();
        }

        return $this->redirectToRoute('index');
    }
}
