<?php

namespace App\Form;

use App\Entity\Promotions;
use App\Entity\PromotionUsers;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionUsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getFirstname() . ' ' . $u->getLastname(),
                'property_path' => 'userId',
            ])
            ->add('promotion', EntityType::class, [
                'class' => Promotions::class,
                'choice_label' => 'name',
                'property_path' => 'promotionId',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromotionUsers::class,
        ]);
    }
}
