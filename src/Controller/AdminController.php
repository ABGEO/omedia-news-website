<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
