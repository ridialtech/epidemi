<?php
namespace App\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class JsonFileUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    private function read(): array
    {
        if (!file_exists($this->file)) {
            return [];
        }
        $data = json_decode(file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        foreach ($this->read() as $record) {
            if ($record['email'] === $identifier) {
                return new SimpleUser($record['email'], $record['password'], $record['roles']);
            }
        }
        throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === SimpleUser::class;
    }

    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        $users = $this->read();
        foreach ($users as &$record) {
            if ($record['email'] === $user->getUserIdentifier()) {
                $record['password'] = $newHashedPassword;
                file_put_contents($this->file, json_encode($users, JSON_PRETTY_PRINT));
                break;
            }
        }
    }
}
