<?php

namespace App\Controller;

use App\Entity\News;
use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
        return $this->render('admin/add_news.html.twig');
    }

    /**
     * Show News adding form
     *
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveNews(Request $request, $id)
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

        if ($this->isCsrfTokenValid('add-news', $token)) {
            if ($image) {
                $filePath = __DIR__ . "/../../public/uploads/images/";
                $fileName = md5($image->getBasename().time()).'.'.$image->guessExtension();

                $image->move(
                    $filePath,
                    $fileName
                );
            }

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            if ($id == 0) {
                $news = new News();

                $news->setTitle($title);
                $news->setSubTitle($subTitle);
                $news->setTags($tags);
                $news->setBody($body);
                $news->setImage($fileName);

                $em->persist($news);
                $em->flush();
            }
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
}
