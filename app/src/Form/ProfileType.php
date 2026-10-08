<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, ['label' => 'Prénom'])
            ->add('lastname', TextType::class, ['label' => 'Nom'])
            ->add('phone_number', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'constraints' => [new Regex('/^\+?[0-9]{7,15}$/', 'Numéro invalide')],
            ])
            ->add('github', TextType::class, [
                'label' => 'GitHub',
                'required' => false,
            ])
            ->add('google_drive', TextType::class, [
                'label' => 'Google Drive',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
