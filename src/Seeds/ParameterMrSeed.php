<?php

namespace App\Seeds;

use App\Entity\Parameter;
use Evotodi\SeedBundle\Command\Seed;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParameterMrSeed extends Seed
{
    public static function seedName(): string
    {
        return 'parameter:mr';
    }

    public static function getOrder(): int
    {
        return 2;
    }

	public function load(InputInterface $input, OutputInterface $output): int
    {

		//Doctrine logging eats a lot of memory, this is a wrapper to disable logging
		$this->disableDoctrineLogging();

		//Access doctrine through $this->doctrine
		$statesRepository = $this->getManager()->getRepository(Parameter::class);


		foreach ($this->getData() as $name => $description) {

			if($ParameterRepository->findOneBy(array('name' => $name, 'geraet' => 2))) {
				continue;
			}

			$em = new Parameter();
			$em->setName($name);
			$em->setBeschreibung($description);

			//Doctrine manager is also available
			$this->getManager()->persist($em);

			$this->getManager()->flush();
		}

		$this->getManager()->clear();
		return 0;
	}

	public function unload(InputInterface $input, OutputInterface $output): int
    {
		$className = $this->getManager()->getClassMetadata(States::class)->getName();
		$this->getManager()->createQuery('DELETE FROM '.$className)->execute();
		return 0;
	}

	public function getData(): array
    {
        return array (
			'CT' => 'strahlend und schnelle Diagnostik',
			'MRT' => 'laut und eng, aber macht tolle Bilder'
        );
	}
}
