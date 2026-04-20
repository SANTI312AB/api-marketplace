<?php

namespace App\Form;

use App\Entity\Login;
use App\Entity\Usuarios;
use App\Validator\Constraints\CedulaEcuatoriana;
use App\Validator\Constraints\RucEcuador;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UsuariosType extends AbstractType
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'Agrega un correo']),
                    new Length(['min' => 4, 'max' => 100]),
                    new Callback(function ($email, ExecutionContextInterface $context) {
                        $user = $this->security->getUser();
                        $current = $user instanceof Login ? $user->getEmail() : null;
                        $exists = $this->entityManager
                            ->getRepository(Usuarios::class)
                            ->findOneBy(['email' => $email]);

                        if ($exists && $exists->getEmail() !== $current) {
                            $context->buildViolation('El email ya está en uso.')
                                ->atPath('email')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'constraints' => [
                    new Length(['min' => 4, 'max' => 150]),
                    new Callback(function ($username, ExecutionContextInterface $context) {
                        $user = $this->security->getUser();
                        $current = $user instanceof Login ? $user->getUsername() : null;
                        $exists = $this->entityManager
                            ->getRepository(Usuarios::class)
                            ->findOneBy(['username' => $username]);

                        if ($exists && $exists->getUsername() !== $current) {
                            $context->buildViolation('El username ya está en uso.')
                                ->atPath('username')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'constraints' => [
                    new NotBlank(['message' => 'Este campo debe tener datos']),
                    new Length(['min' => 4, 'max' => 30]),
                ],
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'constraints' => [
                    new Length(['min' => 4, 'max' => 30]),
                ],
            ])
            ->add('celular', TextType::class, [
                'label' => 'Celular',
                'constraints' => [
                    new NotBlank(['message' => 'Agrega un número de celular']),
                    new Length(['min' => 9, 'max' => 10]),
                    new Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'El celular solo puede contener números.',
                    ]),
                    new Callback(function ($celular, ExecutionContextInterface $context) {
                        $user = $this->security->getUser();
                        $current = $user instanceof Login
                            ? $user->getUsuarios()->getCelular()
                            : null;
                        $exists = $this->entityManager
                            ->getRepository(Usuarios::class)
                            ->findOneBy(['celular' => $celular]);

                        if ($exists && $exists->getCelular() !== $current) {
                            $context->buildViolation('El celular ya está en uso.')
                                ->atPath('celular')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('tipo_documento', ChoiceType::class, [
                'label' => 'Tipo de Documento',
                'constraints' => [new NotBlank(['message' => 'Seleccione un tipo de documento'])],
                'choices' => [
                    'CI'    => 'CI',
                    'RUC'       => 'RUC',
                    'PPN' => 'PPN',
                ],
            ])
            ->add('dni', TextType::class, [
                'label' => 'DNI',
                'constraints' => [
                    new NotBlank(['message' => 'Agrega un número de documento.']),
                    new Length(['min' => 4, 'max' => 16]),
                    new Callback(function ($dni, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $tipo = $form->get('tipo_documento')->getData();
                        $user = $this->security->getUser();
                        $current = $user instanceof Login
                            ? $user->getUsuarios()->getDni()
                            : null;

                        switch ($tipo) {
                            case 'CI':
                                if (strlen($dni) !== 10 || !preg_match('/^[0-9]+$/', $dni)) {
                                    $context->buildViolation('La cédula debe tener 10 dígitos numéricos.')
                                        ->addViolation();
                                    return;
                                }
                                $errors = $this->validator->validate($dni, [new CedulaEcuatoriana()]);
                                foreach ($errors as $e) {
                                    $context->buildViolation($e->getMessage())->addViolation();
                                }
                                break;
                            case 'RUC':
                                if (strlen($dni) !== 13 || !preg_match('/^[0-9]+$/', $dni)) {
                                    $context->buildViolation('El RUC debe tener 13 dígitos numéricos.')
                                        ->addViolation();
                                    return;
                                }
                                $errors = $this->validator->validate($dni, [new RucEcuador()]);
                                foreach ($errors as $e) {
                                    $context->buildViolation($e->getMessage())->addViolation();
                                }
                                break;
                            case 'PPN':
                                if (strlen($dni) < 4 || strlen($dni) > 16) {
                                    $context->buildViolation('El pasaporte debe tener entre 4 y 16 caracteres.')
                                        ->addViolation();
                                    return;
                                }
                                break;
                            default:
                                return;
                        }

                        $exists = $this->entityManager
                            ->getRepository(Usuarios::class)
                            ->findOneBy(['dni' => $dni]);
                        if ($exists && $exists->getDni() !== $current) {
                            $msg = match ($tipo) {
                                'CI'  => 'La cédula ya está en uso.',
                                'RUC' => 'El RUC ya está en uso.',
                                'PPN' => 'El pasaporte ya está en uso.',
                            };
                            $context->buildViolation($msg)
                                ->atPath('dni')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('genero', ChoiceType::class, [
                'required' => false,
                'label' => 'Género',
                'choices' => [
                    'MASCULINO'      => 'MASCULINO',
                    'FEMENINO'       => 'FEMENINO',
                    'NO_ESPECIFICADO' => 'NO_ESPECIFICADO',
                ],
            ])
            ->add('fecha_nacimiento', DateType::class, [
                'required' => false,
                'label' => 'Fecha de Nacimiento',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

}
