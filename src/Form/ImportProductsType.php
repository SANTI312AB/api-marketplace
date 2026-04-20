<?php

namespace App\Form;

use App\Entity\UsuariosDirecciones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Callback;

class ImportProductsType extends AbstractType
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager,Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('excel',FileType::class,[
                'label' => 'Archivo Excel (csv o xlsx)',
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'text/csv',            // CSV
                            'application/vnd.ms-excel', // Old Excel formats (xls)
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
                        ],
                        'mimeTypesMessage' => 'Por favor sube un archivo CSV o XLSX válido.',
                        'maxSize' => '30M' // Limita el tamaño del archivo si es necesario
                    ]),
                    new NotBlank(
                        ['message' => 'Debes seleccionar un archivo']
                    )
                ],
            ])
            ->add('direcciones',EntityType::class,[
                'label'=>'Dirección',
                'class'=> UsuariosDirecciones::class,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor, selecciona una dirección.',
                    ]), 
                    new Callback([$this,'validateDireccion']),
                ] 
             ])



        ;
    }


    public function validateDireccion($value, ExecutionContextInterface $context)
    {
        $user = $this->security->getUser();
        $user_id = $user->getUsuarios()->getId();
        if ($value) {
            // Aquí se verifica si el ID es correcto
           
            // dump($id); // Descomenta esto para ver el ID

            $entity = $this->entityManager->getRepository(UsuariosDirecciones::class)->findBy(['id' => $value,'usuario'=>$user_id]);

            if (!$entity) {
                $context->buildViolation('La dirección seleccionada no te pertenece.')
                    ->atPath('direcciones')
                    ->addViolation();

                 
            } 
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);

         
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
