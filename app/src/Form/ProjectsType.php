<?php

namespace App\Form;

use App\Entity\Projects;
use App\Entity\Promotions;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('visibility')
            ->add('description')
            ->add('google_drive')
            ->add('due_date')
            ->add('promotions', EntityType::class, [
                'class' => Promotions::class,
                'choice_label' => function (Promotions $promotion) {
                    return sprintf('(%d) - %s', $promotion->getId(), $promotion->getName());
                },
                'multiple' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Projects::class,
        ]);
    }
}
