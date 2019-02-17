<?php

namespace App\Controller;

use App\Entity\News;
use App\Entity\Subscriber;
use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminController extends AbstractController
{
    public function index()
    {
        return $this->render('admin/index.html.twig');
    }

    /**
     * Show all users
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function users()
    {
        $doctrine = $this->getDoctrine();
        $users = $doctrine->getRepository(User::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users
        ]);
    }


    /**
     * Remove user from DB
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeUser($id)
    {
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        $user = $doctrine->getRepository(User::class)->find($id);

        if ($user) {
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('admin_users');
    }

    /**
     * Show user editing form
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editUser($id)
    {
        $doctrine = $this->getDoctrine();

        $user = $doctrine->getRepository(User::class)->find($id);

        if (!$user)
            throw $this->createNotFoundException("User not found!");

        return $this->render('admin/edit_user.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Save edited user in db
     *
     * @param Request $request
     * @param $id
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveUser(Request $request, $id, UserPasswordEncoderInterface $passwordEncoder)
    {
        $request = $request->request;

        $username = $request->get('username');
        $password = $request->get('password');
        $role = $request->get('role');

        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        /** @var User $user */
        $user = $doctrine->getRepository(User::class)->find($id);

        if (!$user)
            throw $this->createNotFoundException("User not found!");

        $user->setUsername($username);
        $user->setRoles($role);

        if ($password != '' || $password != null) {
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $password
                )
            );
        }

        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    /**
     * Show All News
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showNews()
    {
        $doctrine = $this->getDoctrine();
        $news = $doctrine->getRepository(News::class)->findAll();

        return $this->render('admin/news.html.twig', [
            'news' => $news
        ]);
    }

    /**
     * Show News adding form
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addNews()
    {
        return $this->render('admin/add_news.html.twig', [
            'edit' => false,
            'news' => null
        ]);
    }

    /**
     * Show News editing form
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editNews($id)
    {
        $doctrine = $this->getDoctrine();

        $news = $doctrine->getRepository(News::class)->find($id);

        if (!$news)
            throw $this->createNotFoundException("News not found!");

        return $this->render('admin/add_news.html.twig', [
            'edit' => true,
            'news' => $news
        ]);
    }

    /**
     * Show News adding form
     *
     * @param Request $request
     * @param $id
     * @param \Swift_Mailer $mailer
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveNews(Request $request, $id, \Swift_Mailer $mailer)
    {
        $files = $request->files;
        $request = $request->request;

        $token = $request->get('token');
        $title = $request->get('title');
        $subTitle = $request->get('subTitle');
        $tags = $request->get('tags');
        $body = $request->get('body');
        /** @var File $image */
        $image = $files->get('image');
        $fileName = null;

        $csrfId = $id == 0 ? 'add-news' : 'edit-news';

        if ($this->isCsrfTokenValid($csrfId, $token)) {
            if ($image) {
                $filePath = __DIR__ . "/../../public/uploads/images/";
                $fileName = md5($image->getBasename().time()).'.'.$image->guessExtension();

                $fileSystem = new Filesystem();

                if (!$fileSystem->exists($filePath))
                    $fileSystem->mkdir($filePath);

                $image->move(
                    $filePath,
                    $fileName
                );
            }

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            if ($id == 0) {
                $news = new News();
            } else {
                /** @var News $news */
                $news = $doctrine->getRepository(News::class)->find($id);

                if (!$news)
                    throw $this->createNotFoundException("News not found!");
            }

            $news->setTitle($title);
            $news->setSubTitle($subTitle);
            $news->setTags($tags);
            $news->setBody($body);

            if ($fileName != null)
                $news->setImage($fileName);

            $em->persist($news);
            $em->flush();

            if ($id == 0)
                $this->sendAddedNewsEmail($news->getTitle(), $news->getId(), $mailer);
        }

        return $this->redirectToRoute('admin_news');
    }

    /**
     * Remove news from DB
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeNews($id)
    {
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        /** @var News $news */
        $news = $doctrine->getRepository(News::class)->find($id);

        if ($news) {
            if ($img = $news->getImage()) {
                $fileSystem = new Filesystem();

                $imgPath = __DIR__ . "/../../public/uploads/images/$img";

                if ($fileSystem->exists($imgPath))
                    $fileSystem->remove($imgPath);
            }

            $em->remove($news);
            $em->flush();
        }

        return $this->redirectToRoute('admin_news');
    }


    /**
     * Send email about news adding to subscribers
     *
     * @param $newsTitle
     * @param $newsId
     * @param \Swift_Mailer $mailer
     * @return AdminController
     */
    public function sendAddedNewsEmail($newsTitle, $newsId, \Swift_Mailer $mailer): self
    {
        $doctrine = $this->getDoctrine();
        $emails = $doctrine->getRepository(Subscriber::class)->getSubscriberEmails();
        $_emails = [];
        foreach ($emails as $email)
            $_emails[] = $email['email'];

        $message = (new \Swift_Message('Added News!'))
            ->setFrom('news@omediaTesting.com', 'Omedia Testing')
            ->setTo($_emails)
            ->setBody(
                $this->renderView('email/added_news.html.twig', [
                    'title' => $newsTitle,
                    'url' => $this->generateUrl('single_news', ['id' => $newsId], UrlGeneratorInterface::ABSOLUTE_URL)
                ]),
                'text/html'
            );

        $mailer->send($message);

        return $this;
    }
}
