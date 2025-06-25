<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['GET','POST'])]
    public function register(Request $request): Response
    {
        $file = $this->getParameter('user_file');
        if ($request->isMethod('POST')) {
            $email = trim((string)$request->request->get('email'));
            $pass = (string)$request->request->get('pass');
            if ($email !== '' && $pass !== '') {
                $users = file_exists($file) ? json_decode(file_get_contents($file), true) ?? [] : [];
                foreach ($users as $u) {
                    if ($u['email'] === $email) {
                        return new Response('Email deja utilise', 400);
                    }
                }
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $users[] = ['email' => $email, 'password' => $hash, 'roles' => ['ROLE_USER']];
                file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
                return $this->redirectToRoute('login');
            }
        }

        $path = $this->getParameter('kernel.project_dir').'/public/register/index.html';
        return new BinaryFileResponse($path);
    }
}
