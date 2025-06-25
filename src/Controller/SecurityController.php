<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return $this->redirectToRoute('login');
    }
    #[Route('/login', name: 'login')]
    public function login(): BinaryFileResponse
    {
        $path = $this->getParameter('kernel.project_dir').'/public/login/index.html';
        return new BinaryFileResponse($path);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        // controller can be blank: Symfony handles the logout.
    }
}
