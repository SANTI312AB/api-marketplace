<?php

namespace App\Form;

use App\Entity\Cupon;
use App\Entity\Factura;
use App\Entity\Login;
use App\Entity\MetodosEnvio;
use App\Entity\MetodosPago;
use App\Entity\UsuariosDirecciones;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;

class PayMetodType extends AbstractType
{

    private $em;
    private $security;

    // Inyecta el EntityManager en el FormType
    public function __construct(EntityManagerInterface $em,Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('metodo_pago',EntityType::class,[
                'class'=>MetodosPago::class,
                'constraints' => [
                    new NotBlank(['message' => 'Elije un metodo de pago.']),

                    new Callback([$this,'validar_metodo_pago'])
                ]   
            ])

            ->add('metodo_envio',EntityType::class,[
                'class'=>MetodosEnvio::class,
                 'required' => false, // Marca el campo como opcional
                'constraints' => [
                    //new NotBlank(['message' => 'Elije un metod de envio.']),

                    new Callback([$this,'validar_metodo_envio'])

                ]
            
            ])
            ->add('factura_id',EntityType::class,[
                'class'=>Factura::class,
                'constraints' => [  
                    new Callback([$this,'validar_factura'])

                ]

            ])
            ->add('direccion_id',EntityType::class,[
                'class'=>UsuariosDirecciones::class,
                 'required' => false, // Marca el campo como opcional
                'constraints' => [
                   // new NotBlank(['message' => 'Seleccione una dirección.']),

                    new Callback([$this,'validar_direccion'])

                ]
            ])
            ->add('codigo_cupon',TextType::class,[
                'constraints' => [
                    new Callback([$this,'validar_cupon'])

                ]
            ])
            ->add('pago_mixto', ChoiceType::class, [
                'choices' => [
                    true => true,
                    false => false,
                ],
                'multiple' => false,
                'data' => false, // Valor inicial por defecto
            ]);
    }


    public function validar_metodo_pago($value, ExecutionContextInterface $context){
        if($value){
            $meto_pago = $this->em->getRepository(MetodosPago::class)->findOneBy(['id' => $value, 'activo'=>true]);
            if (!$meto_pago) {
                $context->buildViolation('El metodo de pago esta desactivado.')
                    ->atPath('metodo_pago')
                    ->addViolation();
            }
        }

    }

    public function validar_metodo_envio($value, ExecutionContextInterface $context){
       
        if ($value ) {
            $meto_envio = $this->em->getRepository(MetodosEnvio::class)->findOneBy(['id' => $value]);

             if(!$meto_envio){
                $context->buildViolation('El metodo de envio no existe.')
                ->atPath('metodo_envio')
                ->addViolation();
             }
            if ($meto_envio && $meto_envio->isActivo() == false) {
                $context->buildViolation('El metodo de envio esta desactivado.')
                    ->atPath('metodo_envio')
                    ->addViolation();
            }   
        }
    }


    public function validar_factura($value, ExecutionContextInterface $context){

        $user = $this->security->getUser();
        if($value){
            $factura = $this->em->getRepository(Factura::class)->findOneBy(['id' => $value, 'login'=>$user]);
            if (!$factura) {
                $context->buildViolation('La factura seleccionada no te pertenece.')
                    ->atPath('factura_id')
                    ->addViolation();
            }
        }

    }


    public function validar_direccion($value, ExecutionContextInterface $context){


        if ($value === null || $value === '') {
            return;
        }
    
        $user = $this->security->getUser();
        if($user instanceof Login){
            $user_id = $user->getUsuarios()->getId();
        }
        
        $direccion = $this->em->getRepository(UsuariosDirecciones::class)->findOneBy(['id' => $value, 'usuario'=>$user_id]);
        
        if (!$direccion) {
                $context->buildViolation('La dirección seleccionada no te pertenece.')
                    ->atPath('direccion_id')
                    ->addViolation();
            }
    
    }

    public function validar_cupon($value,ExecutionContextInterface $context){
        $user = $this->security->getUser();
        if ($value){


            $cupon = $this->em->getRepository(Cupon::class)->findOneBy(['codigo_cupon' => $value]);
            if (!$cupon) {

                $context->buildViolation('El código de cupón no existe.')
                    ->atPath('codigo_cupon')
                    ->addViolation();       
            }

            if ($cupon){

                if ($cupon->getTienda() === $user->getTiendas()) {
                    $context->buildViolation('No se puede utilizar un cupón de tu tienda.')
                        ->atPath('codigo_cupon')
                        ->addViolation();
                }

                if($cupon->isActivo() == false) {
                    $context->buildViolation('El cupón no está activo.')
                        ->atPath('codigo_cupon')
                        ->addViolation();
                }

                if ($cupon->getFechaInicio() > new DateTime() && $cupon->getFechaInicio() !== null) {
                    $context->buildViolation('El cupón no ha iniciado.')
                        ->atPath('codigo_cupon')
                        ->addViolation();
                }

                if ($cupon->getFechaFin() < new DateTime() && $cupon->getFechaFin() !== null) {
                    $context->buildViolation('El cupón ha expirado.')
                        ->atPath('codigo_cupon')
                        ->addViolation();
                }


                if($cupon->getLimiteUso() && $cupon->getUsoCupon() > $cupon->getLimiteUso()){
                    $context->buildViolation('El cupón a alcanzado su límite de uso.')
                        ->atPath('codigo_cupon')
                        ->addViolation();
                }
                
   
                $uso_cupon= $this->em->getRepository(Cupon::class)->uso_cupon($value, $user);
   
                if($uso_cupon){
                    $context->buildViolation('Ya usaste este cupón.')
                        ->atPath('codigo_cupon')
                        ->addViolation();
                }

            }

        }
    }

   

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }

    public function getBlockPrefix():string
    {
        return '';
    }
    
}
