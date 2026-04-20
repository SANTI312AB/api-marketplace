<?php

namespace App\Form;

use App\Entity\Productos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;

class RecargaType extends AbstractType
{
    private $security;
    private $em;
    public function __construct(EntityManagerInterface $em ,Security $security){
        $this->security = $security;
        $this->em = $em;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipo_recarga',ChoiceType::class,[
                'constraints' => [
                    new NotBlank(['message' => 'Elija un tipo de recarga.']),
                ],
                'choices' => [
                    'RETIRO' => 'TRANSACCIONES'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           // 'data_class' => Recargas::class,
        ]);
    }


    public function getBlockPrefix():string
    {
        return '';
    }
    


}
