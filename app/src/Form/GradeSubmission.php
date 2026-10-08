<?php

namespace App\Form;

use App\Entity\Grades;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GradeSubmission extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('submission', TextType::class, [
                'label' => 'Lien de votre rendu',
                'attr' => ['placeholder' => 'https://drive.google.com/...']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Grades::class,
        ]);
    }
}
