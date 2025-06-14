<?php

namespace App\Seeds;

use App\Entity\User;
use Evotodi\SeedBundle\Command\Seed;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserSeed extends Seed
{
    protected function configure()
    {
        // The seed won't load if this is not set
        // The resulting command will be {prefix}:country
        $this->setSeedName('mySeed');

        parent::configure();
    }

    public function load(InputInterface $input, OutputInterface $output)
    {
        // Doctrine logging eats a lot of memory, this is a wrapper to disable logging
        $this->disableDoctrineLogging();

        $users = [
            [
                'email' => 'admin@admin.com',
                'password' => 'password123',
                'roles' => ['ROLE_ADMIN'],
            ],
        ];

        foreach ($users as $user) {
            $userRepo = new User();
            $userRepo->setEmail($user['email']);
            $userRepo->setRoles($user['roles']);
            $userRepo->setPassword($this->passwordEncoder->encodePassword($userRepo, $user['password']));
            $this->manager->persist($userRepo);
        }
        $this->manager->flush();
        $this->manager->clear();

        return 0; // Must return an exit code
    }

    public function unload(InputInterface $input, OutputInterface $output)
    {
        // Clear the table
        $this->manager->getConnection()->exec('DELETE FROM user');

        return 0; // Must return an exit code
    }

    public function getOrder(): int
    {
        return 0;
    }
}
