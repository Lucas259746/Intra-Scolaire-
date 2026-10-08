<?php

namespace App\Form;

use App\Entity\Grades;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GradeCorrectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('grade', TextType::class, [
                'label'    => 'Note',
                'attr'     => ['placeholder' => 'ex: 15/20'],
                'required' => false,
            ])
            ->add('comments', TextareaType::class, [
                'label'    => 'Commentaire',
                'required' => false,
                'attr'     => ['rows' => 4, 'placeholder' => 'Retour sur le travail de l\'étudiant…'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Grades::class,
        ]);
    }
}
