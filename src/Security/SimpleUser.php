<?php
namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class SimpleUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private string $email;
    private string $password;
    private array $roles;

    public function __construct(string $email, string $password, array $roles = [])
    {
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // nothing to do
    }
}
