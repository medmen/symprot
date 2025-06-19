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
                        'binaryFormat' => true, // Use MiB, GiB, etc. so php ini settings are respected
                        'maxSizeMessage' => 'Die Datei ist zu groß ({{ size }} {{ suffix }}). Maximal {{ limit }} {{ suffix }} sind erlaubt.',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/xml',
                            'text/xml',
                        ],
                        'mimeTypesMessage' => 'Bitte laden Sie eine PDF oder XML Datei hoch.',
                        'uploadIniSizeErrorMessage' => 'Die Datei ist zu groß. Maximal {{ limit }} {{ suffix }} sind vom Server erlaubt.',
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
