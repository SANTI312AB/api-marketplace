<?php

namespace App\Form;

use App\Entity\Login;
use App\Entity\Productos;
use App\Entity\Regateos;
use App\Entity\Variaciones;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
class RegateoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentTiendaId = $options['current_tienda_id'];
        $builder
            ->add('regateo',NumberType::class,[
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => 1]),
                    new Regex([
                        'pattern' => '/^[0-9]+(\.[0-9]{1,2})?$/',
                        'message' => 'El regateo debe contener solo nuneros'
                    ]),
                ],
            ])
        
            ->add('producto', EntityType::class, [
                'class' => Productos::class,
                'choice_label' => 'id',
                'constraints' => [
                    new NotBlank(['message' => 'Agrega un producto']),
                    new Callback(function (?Productos $producto, ExecutionContextInterface $context) use ($currentTiendaId) {
                        $this->validateProduct($producto, $currentTiendaId, $context);
                    }),
                ],
            ])
            ->add('variacion', EntityType::class, [
                'class' => Variaciones::class,
                'choice_label' => 'id',
                'constraints' => [
                    new Callback(function (?Variaciones $variacion, ExecutionContextInterface $context) {
                        $this->validateVariation($variacion, $context);
                    }),
                ],
            ])
        ;
    }


    private function validateProduct(?Productos $producto, int $currentTiendaId, ExecutionContextInterface $context): void
    {
        if (!$producto) {
            $context->buildViolation('Este producto no existe.')
                ->addViolation();
            return; // Detener la ejecución si no hay producto
        }
    

        // Validar si el producto pertenece a la tienda actual.
        if ($producto->getTienda()->getId() == $currentTiendaId) {
            $context->buildViolation('No se puede agregar productos de tu misma tienda.')
                ->addViolation();
        }

        if($producto->getProductosTipo()->getId() == 4){
            $context->buildViolation('No puedes agregar productos de tipo servicio.')
                ->addViolation();
        }

        // Validar si el producto está disponible.
        if (!$producto->isDisponibilidadProducto()) {
            $context->buildViolation('El producto no está disponible actualmente.')
                ->addViolation();
        }

        // Validar si el producto está suspendido.
        if ($producto->isSuspendido()) {
            $context->buildViolation('El producto está suspendido y no se puede agregar al carrito.')
                ->addViolation();
        }

        // Validar stock disponible.
        $stockDisponible = $producto->getCantidadProducto();
        $cantidadSolicitada = 1;

        if ($stockDisponible <= 0) {
            $context->buildViolation('No hay stock disponible.')
                ->addViolation();
        } elseif ($cantidadSolicitada > $stockDisponible) {
            $context->buildViolation('No hay stock suficiente.')
                ->addViolation();
        }

        $regateable = $producto->isRegateoProducto();
        if ($regateable == false) {
            $context->buildViolation('Este producto no es regateable.')
                ->addViolation();
        }

        $regateosRechazados = $producto->getRegateos()->filter(function (Regateos $regateo) {
            return in_array($regateo->getEstado(), ['PENDING', 'REJECTED']) && $regateo->getFecha() > (new \DateTime())->modify('-48 hours');
        });

        if ($regateosRechazados->count() >= 3) {
            $context->buildViolation('No se puede hacer otro regateo en este producto debido a múltiples rechazos recientes.')
            ->addViolation();
        }


    }

    private function validateVariation(?Variaciones $variacion, ExecutionContextInterface $context): void
    {
        $producto = $context->getRoot()->get('producto')->getData();

        if (!$producto) {
            return;
        }

        // Verificar si el producto tiene variaciones
        $hasVariations = $producto->getVariaciones()->count() > 0;

        if ($hasVariations && !$variacion) {
            // El producto tiene variaciones pero no se seleccionó ninguna
            $context->buildViolation('Debes seleccionar una variación para este producto.')
                ->atPath('IdVariacion')
                ->addViolation();
        } elseif ($variacion) {
            // Validar que la variación pertenezca al producto
            if ($variacion->getProductos()->getId() !== $producto->getId()) {
                $context->buildViolation('La variación seleccionada no pertenece al producto base.')
                    ->atPath('IdVariacion')
                    ->addViolation();
            }
        }
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Regateos::class,
            'current_tienda_id' => null, // Opción personalizada con valor predeterminado
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
