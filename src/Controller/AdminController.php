<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
}
