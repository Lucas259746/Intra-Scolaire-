<?php

namespace App\Form;

use App\Entity\Absences;
use App\Entity\Documents;
use App\Entity\User;
use App\Repository\DocumentsRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class AbsencesType extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository,
        private DocumentsRepository $documentsRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'];
        $isAdmin     = $currentUser && in_array('ROLE_ADMIN', $currentUser->getRoles(), true);
        $students    = $this->userRepository->findStudents($isAdmin ? null : $currentUser);

        $allDocs = $this->documentsRepository->findAll();
        $allowedIds = array_map(fn(User $u) => $u->getId(), $students);
        $studentDocs = array_values(array_filter(
            $allDocs,
            fn(Documents $d) => in_array($d->getUser()->getId(), $allowedIds, true)
        ));

        $builder
            ->add('start_date', DateTimeType::class, [
                'widget' => 'single_text',
                'label'  => 'Début',
            ])
            ->add('end_date', DateTimeType::class, [
                'widget'   => 'single_text',
                'required' => false,
                'label'    => 'Fin',
            ])
            ->add('user', EntityType::class, [
                'class'        => User::class,
                'choices'      => $students,
                'choice_label' => fn(User $u) => $u->getFirstname() . ' ' . $u->getLastname(),
                'label'        => 'Étudiant',
            ])
            ->add('document', EntityType::class, [
                'class'        => Documents::class,
                'choices'      => $studentDocs,
                'choice_label' => fn(Documents $d) => $d->getUser()->getFirstname()
                    . ' ' . $d->getUser()->getLastname()
                    . ' — ' . $d->getTitle(),
                'required'    => false,
                'placeholder' => 'Aucun justificatif',
                'label'       => 'Justificatif',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => Absences::class,
            'current_user' => null,
        ]);
        $resolver->setAllowedTypes('current_user', ['null', User::class]);
    }
}
