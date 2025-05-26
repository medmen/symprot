<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 8/29/2017
 * Time: 11:32 AM
 * Comments: https://github.com/soyuka/SeedBundle
 */

namespace App\Seeds;

use App\Entity\Geraet;
use Evotodi\SeedBundle\Command\Seed;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeraetSeed extends Seed
{
    public static function seedName(): string
    {
        return 'geraet';
    }

    public static function getOrder(): int
    {
        return 1;
    }

	public function load(InputInterface $input, OutputInterface $output): int
    {

		//Doctrine logging eats a lot of memory, this is a wrapper to disable logging
		$this->disableDoctrineLogging();

		//Access doctrine through $this->doctrine
		$statesRepository = $this->getManager()->getRepository(Geraet::class);


		foreach ($this->getData() as $name => $description) {

			if($GeraetRepository->findOneBy(array('name' => $name))) {
				continue;
			}

			$em = new Geraet();
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
			'CT_Siemens' => 'strahlend und schnelle Diagnostik',
			'MRT_Siemens' => 'laut und eng, aber macht tolle Bilder'
        );
	}
}
