<?php

namespace App\Form;

use App\Entity\Protocol;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ProtocoluploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('protocolFile', VichFileType::class, [
                'label' => 'XML oder PDF Datei',
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/xml',
                            'text/xml',
                        ],
                    ]),
                ],
            ]
            )
            ->add('geraet')
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => Protocol::class,
        ]);
    }
}
