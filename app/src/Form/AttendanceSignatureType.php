<?php

namespace App\Form;

use App\Entity\Documents;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\DocumentsRepository;
use App\Entity\AttendanceSignature;
use App\Enum\AttendanceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttendanceSignatureType extends AbstractType
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
            ->add('status', ChoiceType::class, [
                'label' => 'Je déclare',
                'choices' => [
                    'Je suis présent(e)' => AttendanceType::PENDING_PRESENT->value,
                    'Je suis en retard' => AttendanceType::PENDING_LATE->value,
                    'Je suis absent(e)' => AttendanceType::PENDING_ABSENT->value,
                ],
                'expanded' => true,
                'multiple' => false,
                'mapped' => false,
                'data' => $options['initial_status'],
                'choice_value' => fn(?string $value) => $value,
                'choice_label' => fn(string $value) => $value === AttendanceType::PENDING_PRESENT->value ? 'Je suis présent(e)' : ($value === AttendanceType::PENDING_LATE->value ? 'Je suis en retard' : 'Je suis absent(e)'),
                'choice_translation_domain' => false,

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
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => AttendanceSignature::class,
            'current_user' => null,
            'initial_status' => null,
        ]);
        $resolver->setAllowedTypes('current_user', ['null', User::class]);
    }
}
