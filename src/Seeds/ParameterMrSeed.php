<?php
namespace App\Seeds;

use App\Entity\Parameter;
use App\Entity\Geraet;
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
        return 2; // Ensure this runs after GeraeteSeed (which should be 1)
    }

    public function load(InputInterface $input, OutputInterface $output): int
    {

        //Doctrine logging eats a lot of memory, this is a wrapper to disable logging
        $this->disableDoctrineLogging();

        // Fetch the user by email or any unique identifier

        $geraet = $this->manager->getRepository(Geraet::class)->findOneBy(['geraet_name' => 'MRT_Siemens']);


        //Access doctrine through $this->doctrine
        $ParameterRepository = $this->getManager()->getRepository(Parameter::class);

        $order = 0;

        foreach ($this->getData() as $name) {

            if($ParameterRepository->findOneBy(array('parameter_name' => $name, 'geraet' => $geraet->getGeraetId()))) {
                continue;
            }
            $order++;

            $em = new Parameter();
            $em->setParameterName($name);

            if(in_array($name, $this->getSelected())) {
                $em->setParameterSelected(true);
            }

            $em->setSortPosition($order);
            $em->setGeraet($geraet);

            //Doctrine manager is also available
            $this->getManager()->persist($em);

            $this->getManager()->flush();

        }

        $this->getManager()->clear();
        return 0;
    }

    public function unload(InputInterface $input, OutputInterface $output): int
    {
        $className = $this->getManager()->getClassMetadata(Parameter::class)->getName();
        $geraet = $this->manager->getRepository(Geraet::class)->findOneBy(['geraet_name' => 'MRT_Siemens']);

        $this->getManager()->createQuery('DELETE FROM '.$className.' WHERE geraet_id = '.$geraet->getId)->execute();
        return 0;
    }

    public function getData(): array
    {
        return array (
            "Zeit bis k-Raummitte",
            "WARP",
            "Verknüpfungen",
            "Turbo Faktor",
            "TA",
            "TR",
            "TI",
            "TE",
            "SWI",
            "Sequenz Typ",
            "Segmente",
            "Schichten im 3D-Block",
            "Schichten",
            "Schichtdicke",
            "Schicht-Auflösung",
            "Phasenkodierrichtung",
            "Phasen-Oversampling",
            "Phasen-Auflösung",
            "Normalisierung",
            "MTC",
            "MSMA",
            "Mittelungen",
            "Interpolation",
            "FOV Phase",
            "FOV Auslese",
            "Flipwinkel",
            "Fettsättigung",
            "EPI Faktor",
            "Echozüge pro Schicht",
            "Echoabstand",
            "Distanzfaktor",
            "Diffusion",
            "Dicke",
            "Deep Resolve",
            "Dark Blood",
            "Cine",
            "CAIPIRINHA Modus",
            "Beschleunigungsmodus",
            "Beschleunigungsfaktor PE",
            "Beschleunigungsfaktor 3D",
            "Basis-Auflösung",
            "Bandbreite",
            "b-Wert 2",
            "b-Wert 1",
            "b-Wert >=",
            "Atemkontrolle",
            "Voxelgröße",
        );
    }

    public function getSelected(): array
    {
        return array (
            "TA",
            "TR",
            "TE",
            "FOV Auslese",
            "Schichten",
            "Schichtdicke",
            "Schicht-Auflösung",
            "Basis-Auflösung",
            "Voxelgröße",
        );
    }
}
