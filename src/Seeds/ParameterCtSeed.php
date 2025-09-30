<?php

namespace App\Seeds;

use App\Entity\Parameter;
use App\Entity\Geraet;
use Evotodi\SeedBundle\Command\Seed;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParameterCtSeed extends Seed
{
    public static function seedName(): string
    {
        return 'parameter:ct';
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

        $geraet = $this->manager->getRepository(Geraet::class)->findOneBy(['geraet_name' => 'CT_Siemens']);

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
        $geraet = $this->manager->getRepository(Geraet::class)->findOneBy(['geraet_name' => 'CT_Siemens']);
        $this->getManager()->createQuery('DELETE FROM '.$className.' WHERE geraet_id = '.$geraet->getId)->execute();
        return 0;
    }

    public function getData(): array
    {
        return array (
            'PitchFactor',
            'QualityRefMAs',
            'RefKV',
            'Voltage',
            'CustomMAs',
            'CustomMAsA',
            'CustomMAsB',
            'CarekV',
            'OptimizeSliderPosition',
            'Care',
            'CareDoseType',
            'CTDIw',
            'DLP',
            'FastAdjustLimitScanTime',
            'FastAdjustLimitMaxMAs',
            'DoseNotificationValueCTDIvol',
            'oseNotificationValueDLP',
            'RotTime',
            'ScanTime',
            'Delay',
            'Feed',
            'SliceEffective',
            'Acq.',
            'ReconSliceEffective',
            'ReconIncrR',
            'NoOfImages',
            'Kernel',
            'Window',
            'ApiId',
            'Comment1',
            'Comment2',
            'Transfer1',
            'Transfer2',
            'Transfer3',
            'SyngoViaTaskflow',
            'SyngoViaProcessingID',
            'ScanStart',
            'ScanEnd',
            'Pulsing',
            'PulsingStart',
            'PulsingEnd',
            'BestPhase',
            'PhaseStart',
            'Multiphase'
        );
    }

    public function getSelected(): array
    {
        return array (
            'PitchFactor',
            'QualityRefMAs',
            'RefKV',
            'CareDoseType',
            'CTDIw',
            'DLP',
            'ScanTime',
            'Delay',
            'SliceEffective',
            'Acq.',
            'ReconSliceEffective',
            'ReconIncrR',
            'NoOfImages',
            'Kernel',
            'Window'
        );
    }
}
