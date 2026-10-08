<?php

namespace App\Form;

use App\Entity\Projects;
use App\Entity\Promotions;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionsType extends AbstractType
{

    public function __construct(
        private UserRepository $userRepository
    ) {}


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('name')
            ->add('professor', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'choices' => array_filter(
                    $this->userRepository->findAll(),
                    fn(User $u) => in_array('ROLE_TEACHER', $u->getRoles())
                ),
            ])
            ->add('projects', EntityType::class, [
                'class' => Projects::class,
                'choice_label' => function (Projects $project) {
                    return sprintf('(%d) - %s', $project->getId(), $project->getTitle());
                },
                'multiple' => true,
                'by_reference' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promotions::class,
        ]);
    }
}
