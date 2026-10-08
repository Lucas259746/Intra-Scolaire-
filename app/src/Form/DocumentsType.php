<?php

namespace App\Form;

use App\Entity\Documents;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class DocumentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userChoices = $options['user_choices'];

        $builder
            ->add('type')
            ->add('title');

        if ($userChoices !== false) {
            $fieldOptions = [
                'class'        => User::class,
                'choice_label' => fn(User $u) => $u->getFirstname() . ' ' . $u->getLastname(),
            ];

            if (is_array($userChoices)) {
                $fieldOptions['choices'] = $userChoices;
            }

            $builder->add('user', EntityType::class, $fieldOptions);
        }

        $builder->add('attachment', FileType::class, [
            'label'       => 'Fichier du cours (PDF)',
            'mapped'      => false,
            'required'    => false,
            'constraints' => [
                new File(
                    maxSize: '20M',
                    mimeTypes: [
                        'application/pdf',
                        'application/x-pdf',
                    ],
                    mimeTypesMessage: "Merci d'uploader un document valide (PDF)"
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => Documents::class,
            'user_choices' => null,
        ]);
        $resolver->setAllowedTypes('user_choices', ['null', 'bool', 'array']);
    }
}
