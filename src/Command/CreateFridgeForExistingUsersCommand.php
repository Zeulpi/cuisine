<?php
namespace App\Command;

use App\Entity\User;
use App\Entity\Fridge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:create-fridges',
    description: 'Creates a fridge for all users who do not have one.'
)]
class CreateFridgeForExistingUsersCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Récupérer tous les utilisateurs
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        $createdFridges = 0;

        foreach ($users as $user) {
            // Vérifier si l'utilisateur a déjà un frigo
            $fridge = $user->getFridge();  // Récupérer le fridge

            if (!$fridge) {
                // Si l'utilisateur n'a pas de frigo, on en crée un
                $fridge = new Fridge();
                $fridge->setUser($user);
                $fridge->setInventory([]);  // Initialise avec un inventaire vide (ou par défaut)

                // Persister le frigo
                $this->entityManager->persist($fridge);
                $createdFridges++;

                $output->writeln("Fridge created for user: " . $user->getId());
            }
        }

        // Enregistrer les changements en base
        $this->entityManager->flush();

        $output->writeln("Total fridges created: " . $createdFridges);

        return Command::SUCCESS;
    }
}
