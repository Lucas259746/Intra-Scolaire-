<?php

namespace App\Form;

use App\Entity\Notifications;
use App\Entity\Promotions;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\PromotionsRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'];
        $isAdmin     = in_array('ROLE_ADMIN', $currentUser->getRoles(), true);

        $audienceChoices = $isAdmin
            ? [
                'Tous les utilisateurs'  => 'all',
                'Étudiants uniquement'   => 'students',
                'Professeurs uniquement' => 'teachers',
                'Une promotion'          => 'promotion',
              ]
            : ['Une promotion' => 'promotion'];

        $builder
            ->add('title')
            ->add('message')
            ->add('type', EnumType::class, [
                'class' => NotificationType::class,
            ])
            ->add('audience', ChoiceType::class, [
                'label'  => 'Destinataires',
                'mapped' => false,
                'choices' => $audienceChoices,
            ])
            ->add('promotion', EntityType::class, [
                'label'        => 'Promotion',
                'class'        => Promotions::class,
                'choice_label' => 'name',
                'mapped'       => false,
                'required'     => false,
                'placeholder'  => 'Toutes les promotions',
                'query_builder' => function (PromotionsRepository $er) use ($currentUser, $isAdmin) {
                    $qb = $er->createQueryBuilder('pr')->orderBy('pr.name', 'ASC');
                    if (!$isAdmin) {
                        $qb->where('pr.professor = :user')->setParameter('user', $currentUser);
                    }
                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => Notifications::class,
            'current_user' => null,
        ]);
        $resolver->setAllowedTypes('current_user', ['null', User::class]);
    }
}
