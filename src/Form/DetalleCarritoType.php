<?php

namespace App\Form;

use App\Entity\DetalleCarrito;
use App\Entity\Productos;
use App\Entity\Variaciones;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class DetalleCarritoType extends AbstractType
{
    private EntityManagerInterface $entityManager;


    public function __construct(EntityManagerInterface $entityManager /*, ControlStock $controlStockService = null */)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentTiendaId = $options['current_tienda_id'];

        $builder
            ->add('cantidad', NumberType::class, [
                'label' => 'Cantidad',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La cantidad no puede estar vacía.']), // Agregamos NotBlank aquí
                    new Range([
                        'min' => 1,
                        'minMessage' => 'La cantidad debe ser al menos {{ limit }}.',
                    ]),
                    // La validación de stock específica se hará en el callback general o en los de producto/variación
                ],
            ])

            ->add('IdProducto', EntityType::class, [
                'label' => 'Producto',
                'class' => Productos::class,
                 'choice_label' => 'nombre_producto', // Mostrar el nombre del producto
                 'placeholder' => 'Seleccione un producto', // Opción para un placeholder
                'constraints' => [
                    new NotBlank(['message' => 'Agrega un producto']),
                    new Callback(function (?Productos $producto, ExecutionContextInterface $context) use ($currentTiendaId) {
                         // Validaciones generales del producto (existencia, tienda, tipo, disponibilidad)
                        $this->validateProductGeneral($producto, $currentTiendaId, $context);
                        // La validación de stock del producto base se hará en el callback general
                    }),
                ],
            ])

            ->add('IdVariacion', EntityType::class, [
                'label' => 'Variación',
                'required' => false, // Una variante no siempre es requerida si el producto no tiene variantes
                'class' => Variaciones::class,
                'choice_label' => function(?Variaciones $variacion) { // Mostrar los términos de la variación en el selector
                     if (!$variacion) {
                         return '';
                     }
                     $terminosNombres = [];
                     foreach ($variacion->getTerminos() as $termino) {
                         $terminosNombres[] = $termino->getNombre();
                     }
                     return implode(', ', $terminosNombres);
                },
                 'placeholder' => 'Seleccione una variación (si aplica)', // Opción para un placeholder
                'constraints' => [
                     // La validación de la variante (si se seleccionó la correcta, si es requerida, y stock) se hará en el callback general
                ],
            ])
            // Agregamos un callback a nivel del formulario para validar el stock
             ->add('__stock_validation__', null, [
                 'mapped' => false, // Este campo no mapea a ninguna propiedad de la entidad
                 'constraints' => [
                     new Callback([$this, 'validateStockForCart']),
                 ],
             ])
        ;
    }

    // Método para validaciones generales del producto (sin incluir stock)
    private function validateProductGeneral(?Productos $producto, int $currentTiendaId, ExecutionContextInterface $context): void
    {
        if (!$producto) {
            $context->buildViolation('Este producto no existe.')
                ->addViolation();
            return; // Detener la ejecución si no hay producto
        }

        // Validar si el producto pertenece a la tienda actual (asumiendo que no puedes comprar tus propios productos).
        if ($producto->getTienda()->getId() === $currentTiendaId) { // Usar === para comparación estricta
            $context->buildViolation('No se puede agregar productos de tu misma tienda.')
                ->addViolation();
        }

        

        // Validar tipo de producto (servicio no se puede agregar al carrito).
        // Asegúrate de que getId() existe en ProductosTipo y 4 es el ID correcto para servicio
        if ($producto->getProductosTipo() && $producto->getProductosTipo()->getId() === 4) {
             $context->buildViolation('No puedes agregar productos de tipo servicio.')
                 ->addViolation();
         }


        // Validar disponibilidad del producto.
        if (!$producto->isDisponibilidadProducto()) {
            $context->buildViolation('El producto no está disponible actualmente.')
                ->addViolation();
        }

        // Validar si el producto está suspendido.
        if ($producto->isSuspendido()) {
            $context->buildViolation('El producto está suspendido y no se puede agregar al carrito.')
                ->addViolation();
        }
        // No validamos stock aquí, se hace en validateStockForCart
    }


    // Método para validar la variante seleccionada (si aplica) y el stock
    public function validateStockForCart(mixed $value, ExecutionContextInterface $context): void
    {
        // Acceder a los datos del formulario
        $form = $context->getRoot();
        $producto = $form->get('IdProducto')->getData();
        $variacion = $form->get('IdVariacion')->getData();
        $cantidadSolicitada = $form->get('cantidad')->getData();

        // Si no hay producto seleccionado o cantidad válida, otras validaciones ya se encargarán
        if (!$producto || $cantidadSolicitada === null || $cantidadSolicitada <= 0) {
            return;
        }

        // Verificar si el producto tiene variaciones
        $hasVariations = $producto->getVariaciones()->count() > 0;

        // Lógica de validación de stock similar a tu función control_stock
        if ($hasVariations) {
            // El producto tiene variaciones, validar que se haya seleccionado una
            if (!$variacion) {
                 $context->buildViolation('Debes seleccionar una variación para este producto.')
                    ->atPath('IdVariacion') // Asociar el error al campo IdVariacion
                     ->addViolation();
                 return; // Detener validación de stock si falta la variante requerida
            }

            // Validar que la variación seleccionada pertenezca al producto
             if ($variacion->getProductos()->getId() !== $producto->getId()) {
                 $context->buildViolation('La variación seleccionada no pertenece al producto base.')
                     ->atPath('IdVariacion') // Asociar el error al campo IdVariacion
                     ->addViolation();
                 return; // Detener validación si la variante no es del producto
            }

            // Validar stock de la variante
            $cantidad_variante = $variacion->getCantidad();
            $terminosNombres = [];

            foreach ($variacion->getTerminos() as $termino) {
                 $terminosNombres[] = $termino->getNombre();
            }

            $terminosString = '';
            if (!empty($terminosNombres)) {
                $terminosString = ' (' . implode(', ', $terminosNombres) . ')';
            }

            $nombreCompletoProducto = $producto->getNombreProducto() . $terminosString;

            if ($cantidad_variante <= 0) {
                $context->buildViolation('No hay stock disponible para - ' . $nombreCompletoProducto)
                     ->atPath('cantidad') // O al campo que consideres más apropiado
                     ->addViolation();
            } elseif ($cantidadSolicitada > $cantidad_variante) {
                $context->buildViolation('No hay suficiente stock para- ' . $nombreCompletoProducto)
                     ->atPath('cantidad') // O al campo que consideres más apropiado
                     ->addViolation();
            }

        } else {
            // El producto NO tiene variaciones, validar contra el stock del producto base.
            // Solo si no se ha seleccionado ninguna variación (esto ya lo maneja la lógica superior, pero es una doble verificación)
            if ($variacion !== null) {
                 // Esto no debería ocurrir si el producto no tiene variantes y se intenta seleccionar una
                 // Podrías añadir una validación aquí si quieres ser estricto
                 $context->buildViolation('Este producto no acepta variaciones.')
                      ->atPath('IdVariacion')
                      ->addViolation();
                 return;
            }

            $cantidad_producto_base = $producto->getCantidadProducto();

            if ($cantidad_producto_base <= 0) {
                $context->buildViolation('No hay stock disponible - ' . $producto->getNombreProducto())
                    ->atPath('cantidad') // O al campo que consideres más apropiado
                    ->addViolation();
            } elseif ($cantidadSolicitada > $cantidad_producto_base) {
                $context->buildViolation('No hay suficiente stock - ' . $producto->getNombreProducto())
                    ->atPath('cantidad') // O al campo que consideres más apropiado
                    ->addViolation();
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DetalleCarrito::class,
            'current_tienda_id' => null, // Opción personalizada con valor predeterminado
        ]);

    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}