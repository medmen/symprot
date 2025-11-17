<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Geraet;

class ProtocoluploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('protocolFile', FileType::class, [
                'label' => 'XML oder PDF Datei',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File(
                        maxSize: '50M',
                        mimeTypes: ['application/pdf', 'application/xml', 'text/xml']
                    ),
                ],
            ])
            ->add('geraet', EntityType::class, [
                'class' => Geraet::class,
                'choice_label' => 'geraetName',
                'placeholder' => 'Bitte Gerät wählen',
                'mapped' => false,
                'required' => true,
                'label' => 'Gerät'
            ])
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'data_class' => null,
        ]);
    }
}
