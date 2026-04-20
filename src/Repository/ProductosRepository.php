<?php

namespace App\Repository;

use App\Entity\Atributos;
use App\Entity\Productos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Productos>
 *
 * @method Productos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Productos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Productos[]    findAll()
 * @method Productos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Productos::class);
    }

    public function findProductosWithFilters(array $filters = null, $minPrecio = null, $maxPrecio = null,$estado = null,$categoria= null,$subcategoria=null,$tienda=null, $conDescuento=null,$searchTerm =null,bool $cantidad = null,$categoria_tienda=null,$marca=null,$entrega_tipo=null, bool $mas_vendido = null, $subcategoria_tienda= null,$productos_tipo=null,$ciudad=null,$tipo_cobro=null, array $terminos=null)
    {

        $query = $this->getEntityManager()->createQueryBuilder();
    
        $query
            ->select('p')
            ->from('App:productos', 'p')
            ->leftJoin('p.variaciones', 'v')
            ->leftJoin('p.estado','e')
            ->leftJoin('p.categorias', 'c')
            ->leftJoin('p.subcategorias','s')
            ->leftJoin('p.marcas', 'm')
            ->leftJoin('p.tienda','t')
            ->leftJoin('p.categoriasTiendas','ct')
            ->leftJoin('p.subcategoriasTiendas', 'st')
            ->leftJoin('p.entrgas_tipo', 'i')
            ->leftJoin('p.productos_tipo', 'pt')
            ->leftJoin('p.direcciones','d')
            ->leftJoin('d.ciudad','cy')
            ->where('p.disponibilidad_producto = :value')
            ->andWhere('p.suspendido = :value2')
            ->andWhere('p.borrador = :value3')
            ->andWhere('p.productos_tipo != :tipo_product')
            ->andWhere('t.id NOT IN (:ids_tienda)')
            ->setParameter('ids_tienda', [630,722,721,720,719,718])
            ->setParameter('value', true)
            ->setParameter('value2', false)
            ->setParameter('value3', false)
            ->setParameter('tipo_product',3)
            ->andWhere(
                $query->expr()->orX(
                 $query->expr()->like('LOWER(p.nombre_producto)', ':searchTerm'),
                    $query->expr()->like('LOWER(c.nombre)', ':searchTerm'),
                    $query->expr()->like('LOWER(s.nombre)', ':searchTerm'),
                    $query->expr()->like('LOWER(t.nombre_tienda)', ':searchTerm'),
                    $query->expr()->like('LOWER(ct.nombre)', ':searchTerm'),
                    $query->expr()->like('LOWER(i.tipo)', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%' . strtolower(trim($searchTerm)) . '%');
        
    
    
            if ($cantidad !== null) {
                if ($cantidad) {
                    $query->andWhere('p.cantidad_producto > 0');
                }
            }
    
        
            if ($conDescuento !== null) {
                $query->andWhere('p.tiene_descuento = :descuento')
                    ->setParameter('descuento',$conDescuento);
            }
    
           if ($minPrecio !== null) {
            $query->andWhere('p.precio_normal_producto >= :minPrecio')
                ->setParameter('minPrecio', $minPrecio);
            }
    
    
        if ($maxPrecio !== null) {
            $query->andWhere('p.precio_normal_producto <= :maxPrecio')
                ->setParameter('maxPrecio', $maxPrecio);
        }
    
        $orderBy = $filters['orderBy'] ?? null;
    
    if ($orderBy !== null) {
        // Verifica si $orderBy contiene '_'
        if (strpos($orderBy, '_') !== false) {
            // Si contiene '_', divide la cadena en campo y dirección
            list($campo, $direccion) = explode('_', $orderBy);
        } else {
            // Si no contiene '_', usa $orderBy como campo y establece un valor predeterminado para dirección
            $campo = $orderBy;
            $direccion = null; // o 'desc', según lo que desees como predeterminado
        }
    
        // Luego, procede con tu lógica de ordenamiento
        switch ($campo) {
            case 'precio':
                $query->addOrderBy('p.precio_normal_producto', $direccion);
                break;
    
            case 'fecha':
                $query->addOrderBy('p.fecha_registro_producto', $direccion);
                break;
    
            case 'random':
                // Para 'aleatorio', no necesitas dirección, así que puedes ignorar $direccion
                $query->addOrderBy('RAND()');
                break;
    
            // ... otros casos
    
            default:
                // Manejar un campo desconocido o no especificado
                break;
        }
    }else{
        $query->addOrderBy('p.fecha_registro_producto', 'desc');
    }
    
    
        if ($tienda !== null) {
            $query->andWhere('t.slug = :tienda')
                ->setParameter('tienda', $tienda);
        }
    
        if ($estado !== null) {
            $query->andWhere('e.slug = :estado')
                ->setParameter('estado', $estado);
        }

        if($mas_vendido !== null) {

            $query
            ->innerJoin('p.detallePedidos','dp')
            ->innerJoin('dp.pedido','pd')
            ->where('pd.estado = :estadoPago')
            ->andWhere(
                $query->expr()->orX(
                    $query->expr()->andX(
                        'pd.tipo_envio = :domicilio',
                        'pd.estado_envio = 22'
                    ),
                    $query->expr()->andX(
                        'pd.tipo_envio = :retiro',
                        'pd.estado_retiro = 22'
                    ),
                    $query->expr()->andX(
                        'pd.tipo_envio = :ambos',
                        'pd.estado_envio = 22',
                        'pd.estado_retiro = 22'
                    )
                )
            )
            ->andWhere('pd.provincia <> :value')
            ->andWhere('pd.region <> :value')
            ->andWhere('pd.subtotal <> :value')
            ->andWhere('pd.iva <> :value')
            ->andWhere('pd.total <> :value')
            ->andWhere('pd.total_final <> :value ')
            ->setParameters([
                'estadoPago' => 'APPROVED',
                'domicilio' => 'A DOMICILIO',
                'retiro' => 'RETIRO EN TIENDA FISICA',
                'ambos' => 'AMBOS',
                'value' => '',

            ])
            
            ->groupBy('p.id')
            ->orderBy('SUM(dp.cantidad)', 'DESC');
        }
    
        if ($categoria !== null) {
            $query->andWhere('c.slug = :categoria')
                ->setParameter('categoria', $categoria);
        }
    
    
        if ($subcategoria !== null) {
            $query->andWhere('s.slug = :subcategoria')
                ->setParameter('subcategoria', $subcategoria);
        }
    
        if ($categoria_tienda !== null) {
            $query->andWhere('ct.slug = :categoria_tienda')
                ->setParameter('categoria_tienda', $categoria_tienda);
        } 

        if ($subcategoria_tienda!== null) {
            $query->andWhere('st.slug = :subcategoria_tienda')
                ->setParameter('subcategoria_tienda', $subcategoria_tienda);
        }

        if ($marca !== null) {
            $query->andWhere(
                $query->expr()->orX(
                    'LOWER(m.slug) = LOWER(:marca)'  // Marca de la relación ManyToOne
                    
                )
            )->setParameter('marca', strtolower($marca));
        }
        

        if($entrega_tipo !== null) {
            $query->andWhere('i.slug = :entrega_tipo')
                ->setParameter('entrega_tipo', $entrega_tipo);
        }

        if ($minPrecio!== null) {
            $query->andWhere('p.precio_normal_producto >= :minPrecio')
                ->setParameter('minPrecio', $minPrecio);
        }

        if($productos_tipo !== null) {
            $query->andWhere('pt.tipo = :filtro_tipo')
                ->setParameter('filtro_tipo', $productos_tipo);
        }else{
            $query->andWhere('pt.tipo = :filtro_tipo')
            ->setParameter('filtro_tipo', 'FISICO');
        }

        if($ciudad !== null) {
             $query->andWhere('cy.ciudad = :ciudad')
             ->setParameter('ciudad', $ciudad);
        }

        if($tipo_cobro !== null) {
             $query->andWhere('p.cobro_servicio = :tipo_cobro')
             ->setParameter('tipo_cobro', $tipo_cobro);
        }


        if ($terminos !== null && !empty($terminos)) {
            // Subconsulta para productos que tienen TODOS los términos
            $subQuery = $this->getEntityManager()->createQueryBuilder()
                ->select('p_sub.id')
                ->from('App:productos', 'p_sub')
                ->innerJoin('p_sub.variaciones', 'v_sub')
                ->innerJoin('v_sub.terminos', 't_sub')
                ->where('t_sub.nombre IN (:terminos)')
                ->groupBy('p_sub.id')
                ->having('COUNT(DISTINCT t_sub.nombre) = :totalTerminos');
    
            // Filtrar productos principales usando la subconsulta
            $query->andWhere($query->expr()->in('p.id', $subQuery->getDQL()))
                 ->setParameter('terminos', $terminos)
                 ->setParameter('totalTerminos', count($terminos));
        }
    

     
        return $query->getQuery()->getResult();
}


public function findProductosWithFilters_tienda(array $filters = null, $minPrecio = null, $maxPrecio = null,$estado = null,$categoria= null,$subcategoria=null,$tienda=null, $conDescuento=null,$searchTerm = null,bool $cantidad = null,$categoria_tienda=null,$marca=null,$entrega_tipo=null, bool $mas_vendido = null, $subcategoria_tienda = null,$productos_tipo=null,$ciudad=null,$tipo_cobro=null,array $terminos= null)
{
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
        ->select('p')
        ->from('App:productos', 'p')
        ->leftJoin('p.variaciones', 'v')
        ->leftJoin('p.estado','e')
        ->leftJoin('p.categorias', 'c')
        ->leftJoin('p.subcategorias','s')
        ->leftJoin('p.marcas', 'm')
        ->leftJoin('p.tienda','t')
        ->leftJoin('p.categoriasTiendas','ct')
        ->leftJoin('p.subcategoriasTiendas', 'st')
        ->leftJoin('p.entrgas_tipo', 'i')
        ->leftJoin('p.productos_tipo', 'pt')
        ->leftJoin('p.direcciones','d')
        ->leftJoin('d.ciudad','cy')
        ->leftJoin('v.terminos', 'vr')
        ->where('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product',3)
        ->andWhere(
            $query->expr()->orX(
             $query->expr()->like('LOWER(p.nombre_producto)', ':searchTerm'),
                $query->expr()->like('LOWER(c.nombre)', ':searchTerm'),
                $query->expr()->like('LOWER(s.nombre)', ':searchTerm'),
                $query->expr()->like('LOWER(t.nombre_tienda)', ':searchTerm'),
                $query->expr()->like('LOWER(i.tipo)', ':searchTerm'),
                $query->expr()->like('LOWER(pt.tipo)', ':searchTerm')
            )
        )
        ->setParameter('searchTerm', '%'.$searchTerm.'%');
    


        if ($cantidad !== null) {
            if ($cantidad) {
                $query->andWhere('p.cantidad_producto > 0');
            }
        }

    
        if ($conDescuento !== null) {
            $query->andWhere('p.tiene_descuento = :descuento')
                ->setParameter('descuento',$conDescuento);
        }

       if ($minPrecio !== null) {
        $query->andWhere('p.precio_normal_producto >= :minPrecio')
            ->setParameter('minPrecio', $minPrecio);
        }


    if ($maxPrecio !== null) {
        $query->andWhere('p.precio_normal_producto <= :maxPrecio')
            ->setParameter('maxPrecio', $maxPrecio);
    }

    $orderBy = $filters['orderBy'] ?? null;

    if ($orderBy !== null) {
    // Verifica si $orderBy contiene '_'
    if (strpos($orderBy, '_') !== false) {
        // Si contiene '_', divide la cadena en campo y dirección
        list($campo, $direccion) = explode('_', $orderBy);
    } else {
        // Si no contiene '_', usa $orderBy como campo y establece un valor predeterminado para dirección
        $campo = $orderBy;
        $direccion = null; // o 'desc', según lo que desees como predeterminado
    }

    // Luego, procede con tu lógica de ordenamiento
    switch ($campo) {
        case 'precio':
            $query->addOrderBy('p.precio_normal_producto', $direccion);
            break;

        case 'fecha':
            $query->addOrderBy('p.fecha_registro_producto', $direccion);
            break;

        case 'random':
            // Para 'aleatorio', no necesitas dirección, así que puedes ignorar $direccion
            $query->addOrderBy('RAND()');
            break;

        // ... otros casos

        default:
            // Manejar un campo desconocido o no especificado
            break;
    }
    
    }else{
    $query->addOrderBy('p.fecha_registro_producto', 'desc');
    }


    if ($tienda !== null) {
        $query->andWhere('t.slug = :tienda')
            ->setParameter('tienda', $tienda);
    }

    if ($estado !== null) {
        $query->andWhere('e.slug = :estado')
            ->setParameter('estado', $estado);
    }

    if($mas_vendido !== null) {

        $query
        ->innerJoin('p.detallePedidos','d')
        ->innerJoin('d.pedido','pd')
        ->where('pd.estado = :estadoPago')
        ->andWhere(
            $query->expr()->orX(
                $query->expr()->andX(
                    'pd.tipo_envio = :domicilio',
                    'pd.estado_envio = 22'
                ),
                $query->expr()->andX(
                    'pd.tipo_envio = :retiro',
                    'pd.estado_retiro = 22'
                ),
                $query->expr()->andX(
                    'pd.tipo_envio = :ambos',
                    'pd.estado_envio = 22',
                    'pd.estado_retiro = 22'
                )
            )
        )
        ->andWhere(
            'pd.ubicacion_referencia <>  :value'
        )
        ->andWhere('pd.provincia <> :value')
        ->andWhere('pd.region <> :value')
        ->andWhere('pd.subtotal <> :value')
        ->andWhere('pd.iva <> :value')
        ->andWhere('pd.total <> :value')
        ->andWhere('pd.total_final <> :value ')
        ->setParameters([
            'estadoPago' => 'APPROVED',
            'domicilio' => 'A DOMICILIO',
            'retiro' => 'RETIRO EN TIENDA FISICA',
            'ambos' => 'AMBOS',
            'value' => '',

        ])
        
        ->groupBy('p.id')
        ->orderBy('SUM(d.cantidad)', 'DESC');
    }

    if ($categoria !== null) {
        $query->andWhere('c.slug = :categoria')
            ->setParameter('categoria', $categoria);
    }


    if ($subcategoria !== null) {
        $query->andWhere('s.slug = :subcategoria')
            ->setParameter('subcategoria', $subcategoria);
    }

    if ($categoria_tienda !== null) {
        $query->andWhere('ct.slug = :categoria_tienda')
            ->setParameter('categoria_tienda', $categoria_tienda);
    } 

    if ($subcategoria_tienda!== null) {
        $query->andWhere('st.slug = :subcategoria_tienda')
            ->setParameter('subcategoria_tienda', $subcategoria_tienda);
    }

    if ($marca !== null) {
        $query->andWhere(
            $query->expr()->orX(
                'LOWER(m.slug) = LOWER(:marca)'
            )
        )->setParameter('marca', strtolower($marca));
    }

     
    if($entrega_tipo !== null) {
        $query->andWhere('i.slug = :entrega_tipo')
            ->setParameter('entrega_tipo', $entrega_tipo);
    }

    if($productos_tipo !== null) {
        $query->andWhere('pt.tipo = :filtro_tipo')
            ->setParameter('filtro_tipo', $productos_tipo);
    }

    if($ciudad !== null) {
        $query->andWhere('cy.ciudad = :ciudad')
        ->setParameter('ciudad', $ciudad);
    }

    if($tipo_cobro !== null) {
        $query->andWhere('p.cobro_servicio = :tipo_cobro')
        ->setParameter('tipo_cobro', $tipo_cobro);
    }

    if ($terminos !== null && !empty($terminos)) {
        // Subconsulta para productos que tienen TODOS los términos
        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('p_sub.id')
            ->from('App:productos', 'p_sub')
            ->innerJoin('p_sub.variaciones', 'v_sub')
            ->innerJoin('v_sub.terminos', 't_sub')
            ->where('t_sub.nombre IN (:terminos)')
            ->groupBy('p_sub.id')
            ->having('COUNT(DISTINCT t_sub.nombre) = :totalTerminos');

        // Filtrar productos principales usando la subconsulta
        $query->andWhere($query->expr()->in('p.id', $subQuery->getDQL()))
             ->setParameter('terminos', $terminos)
             ->setParameter('totalTerminos', count($terminos));
    }

  

    return $query->getQuery()->getResult();
}

public function productos_categorias($slug,$categoria)
{
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
        ->select('p')
        ->from('App:productos', 'p')
        ->addSelect('c')
        ->leftJoin('p.categorias', 'c')
        ->Where('p.cantidad_producto > 0')
        ->andWhere('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.slug_producto != :slug')
        ->andWhere('c.slug = :categoria')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('categoria', $categoria)
        ->setParameter('slug', $slug)
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product',3)
        ->orderBy('RAND()')
        ->setMaxResults(3);

    return $query->getQuery()->getResult();
}


public function productos_subcategorias($slug,$subcategoria)
{
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
        ->select('p')
        ->from('App:productos', 'p')
        ->addSelect('s')
        ->leftJoin('p.subcategorias', 's')
        ->where('p.cantidad_producto > 0')
        ->andWhere('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('s.slug = :subcateoria')
        ->andWhere('p.slug_producto != :slug')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('subcateoria', $subcategoria)
        ->setParameter('slug', $slug)
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product',3)
        ->orderBy('RAND()')
        ->setMaxResults(3);
  
    return $query->getQuery()->getResult();  
}



 public function productos_subcategorias_tienda($tienda, $subcategoria,$slug){
     $query = $this->getEntityManager()->createQueryBuilder();

     $query
         ->select('p')
         ->from('App:productos', 'p')
         ->addSelect('s')
         ->leftJoin('p.subcategorias', 's')
         ->leftJoin('p.tienda','t')
         ->where('p.cantidad_producto > 0')
         ->andWhere('p.disponibilidad_producto = :value')
         ->andWhere('p.suspendido = :value2')
         ->andWhere('s.slug = :subcateoria')
         ->andWhere('t.slug = :tienda')
         ->andWhere('p.slug_producto != :slug')
         ->andWhere('p.borrador = :value3')
         ->andWhere('t.id NOT IN (:ids_tienda)')
         ->andWhere('p.productos_tipo != :tipo_product')
         ->setParameter('ids_tienda', [630,722,721,720,719,718])
         ->setParameter('subcateoria', $subcategoria)
         ->setParameter('tienda', $tienda)
         ->setParameter('value', true)
         ->setParameter('value2', false)
         ->setParameter('slug', $slug)
         ->setParameter('value3', false)
         ->setParameter('tipo_product',3)
         ->orderBy('RAND()')
         ->setMaxResults(3);

     return $query->getQuery()->getResult();
 }

 public function productos_categoria_tienda($tienda, $categoria,$slug){
     $query = $this->getEntityManager()->createQueryBuilder();

     $query
         ->select('p')
         ->from('App:productos', 'p')
         ->addSelect('c')
         ->leftJoin('p.categorias', 'c')
         ->leftJoin('p.tienda', 't')
         ->where('p.cantidad_producto > 0')
         ->andWhere('p.disponibilidad_producto = :value')
         ->andWhere('p.suspendido = :value2')
         ->andWhere('c.slug = :categoria')
         ->andWhere('t.slug = :tienda')
         ->andWhere('p.slug_producto != :slug')
         ->andWhere('p.borrador = :value3')
         ->andWhere('t.id NOT IN (:ids_tienda)')
         ->andWhere('p.productos_tipo != :tipo_product')
         ->setParameter('ids_tienda', [630,722,721,720,719,718])
         ->setParameter('categoria', $categoria)
         ->setParameter('tienda', $tienda)
         ->setParameter('value', true)
         ->setParameter('value2', false)
         ->setParameter('slug', $slug)
         ->setParameter('value3', false)
         ->setParameter('tipo_product',3)
         ->orderBy('RAND()')
         ->setMaxResults(3);

     return $query->getQuery()->getResult();
 }


 public function productos_randon_3($slug){
     $query = $this->getEntityManager()->createQueryBuilder();

     $query
         ->select('p')
         ->from('App:productos', 'p')
         ->where('p.cantidad_producto > 0')
         ->andWhere('p.disponibilidad_producto = :value')
         ->andWhere('p.suspendido = :value2')
         ->andWhere('p.slug_producto != :slug')
         ->andWhere('p.borrador = :value3')
         ->andWhere('p.productos_tipo != :tipo_product')
         ->orderBy('RAND()')
         ->setParameter('value', true)
         ->setParameter('value2', false)
         ->setParameter('slug', $slug)
         ->setParameter('value3', false)
         ->setParameter('tipo_product',3)
         ->setMaxResults(3);

     return $query->getQuery()->getResult();
 

 }

 public function productos_randon_bloque($id = null){
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
        ->select('p')
        ->from('App:productos', 'p')
        ->leftJoin('p.categorias', 'c')
        ->where('p.cantidad_producto > 0')
        ->andWhere('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->orderBy('RAND()')
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product',3)
        ->setMaxResults(1);

        
        if ($id !== null) {
            $query->andWhere('c.id = :categoria')
                ->setParameter('categoria', $id);
        }

    return $query->getQuery()->getOneOrNullResult();


}

public function productos_categorias_bloque($id= null)
{
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
        ->select('p')
        ->from('App:productos', 'p')
        ->addSelect('c')
        ->leftJoin('p.categorias', 'c')
        ->Where('p.cantidad_producto > 0')
        ->andWhere('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product',value: 3)
        ->orderBy('RAND()')
        ->setMaxResults(20);

        if ($id !== null) {
            $query->andWhere('c.id = :categoria')
                ->setParameter('categoria', $id);
        }
    

    return $query->getQuery()->getResult();
}



 public function productos_vendedor($tienda,$slug){
     $query = $this->getEntityManager()->createQueryBuilder();

     $query
         ->select('p')
         ->from('App:productos', 'p')
         ->addSelect('t')
         ->leftJoin('p.tienda', 't')
         ->where('p.cantidad_producto > 0')
         ->andWhere('p.disponibilidad_producto = :value')
         ->andWhere('p.suspendido = :value2')
         ->andWhere('t.slug = :tienda')
         ->andWhere('p.slug_producto != :slug')
         ->andWhere('p.borrador = :value3')
         ->andWhere('t.id NOT IN (:ids_tienda)')
         ->andWhere('p.productos_tipo != :tipo_product')
         ->setParameter('ids_tienda', [630,722,721,720,719,718])
         ->setParameter('tienda', $tienda)
         ->setParameter('value', true)
         ->setParameter('value2', false)
         ->setParameter('slug', $slug)
         ->setParameter('value3', false)
         ->setParameter('tipo_product',3)
         ->orderBy('RAND()')
         ->setMaxResults(3);

     return $query->getQuery()->getResult();
 
 }


public function randon($categoria= null,$subcategoria= null,$tienda=null){

        $query = $this->getEntityManager()->createQueryBuilder();

         $query
        ->select('p')
        ->from('App:productos', 'p')
        ->addSelect('c')
        ->addSelect('s')
        ->leftJoin('p.categorias', 'c')
        ->leftJoin('p.subcategorias','s')
        ->leftJoin('p.tienda', 't')
        ->where('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product',3)
        ->orderBy('RAND()')
        ->setMaxResults(1);


        if ($categoria !== null) {
            $query->andWhere('c.slug = :categoria')
                ->setParameter('categoria', $categoria);
        }
    
    
        if ($subcategoria !== null) {
            $query->andWhere('s.slug = :subcategoria')
                ->setParameter('subcategoria', $subcategoria);
        }

        if ($tienda !== null) {
            $query->andWhere('t.id = :tienda')
                ->setParameter('tienda', $tienda);
        }
    

        return $query->getQuery()->getOneOrNullResult();

    }


    public function randon_2(){

        $query = $this->getEntityManager()->createQueryBuilder();

         $query
        ->select('p')
        ->from('App:productos', 'p')
        ->where('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product',3)
        ->orderBy('RAND()')
        ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();

    }


     public function search_product(string $searchTerm)
{
    $qb = $this->getEntityManager()->createQueryBuilder();

    $qb->select('p')
        ->from('App:productos', 'p')
        ->innerJoin('p.estado', 'e')
        ->leftJoin('p.categorias', 'c')
        ->leftJoin('p.subcategorias', 's')
        ->leftJoin('p.tienda', 't')
        ->leftJoin('p.marcas', 'm')
        ->leftJoin('p.entrgas_tipo', 'i')
        ->leftJoin('p.productos_tipo', 'pt')
        ->leftJoin('p.direcciones', 'd')
        ->leftJoin('d.ciudad', 'cy')
        ->where('p.disponibilidad_producto = :activo')
        ->andWhere('p.suspendido = :suspendido')
        ->andWhere('p.borrador = :borrador')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->andWhere('t.id NOT IN (:ids_tienda)')
        ->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like("LOWER(p.nombre_producto)", ":likeTerm"),
                $qb->expr()->like("LOWER(c.nombre)", ":likeTerm"),
                $qb->expr()->like("LOWER(s.nombre)", ":likeTerm"),
                $qb->expr()->like("LOWER(t.nombre_tienda)", ":likeTerm"),
                $qb->expr()->like("LOWER(m.nombre_m)", ":likeTerm"),
                $qb->expr()->like("LOWER(i.tipo)", ":likeTerm"),
                $qb->expr()->like("LOWER(pt.tipo)", ":likeTerm"),
                $qb->expr()->like("LOWER(cy.ciudad)", ":likeTerm")
            )
        )
        ->setParameters([
            'activo' => true,
            'suspendido' => false,
            'borrador' => false,
            'tipo_product' => 3,
            'ids_tienda' => [630, 722, 721, 720, 719, 718],
            'likeTerm' => '%' . strtolower(trim($searchTerm)) . '%',
        ])
        ->orderBy( 'RAND()')
        ->setMaxResults(6);

    return $qb->getQuery()->getResult();
}




  


public function productos_categorias_tiendas($categoria_tienda){

    $query = $this->getEntityManager()->createQueryBuilder();
    $query->select('p')
    ->from('App:productos', 'p')
    ->innerJoin('p.categoriasTiendas','c')
    ->where('c.id = :categoria_tienda')
    ->andWhere('p.disponibilidad_producto = :value')
    ->andWhere('p.suspendido = :value2')
    ->andWhere('p.borrador = :value3')
    ->andWhere('p.productos_tipo != :tipo_product')
    ->setParameter('categoria_tienda', $categoria_tienda)
    ->setParameter('value', true)
    ->setParameter('value2', false)
    ->setParameter('value3', false)
    ->setParameter('tipo_product',3);


    return $query->getQuery()->getResult();

}

public function tipo_cobro_producto_servicio() {
    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('p.cobro_servicio as nombre', 'COUNT(p.cobro_servicio) AS total_productos')
        ->distinct()
        ->from('App:Productos', 'p')
        ->where('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->andWhere('p.cobro_servicio IS NOT NULL')  // Excluye valores NULL
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product', 3)
        ->groupBy('p.cobro_servicio')
        ->orderBy('COUNT(p.cobro_servicio)', 'DESC');

    return $query->getQuery()->getResult();
}


public function productos_con_terminos($categoria = null, $subcategoria = null) {
    $qb = $this->getEntityManager()->createQueryBuilder();

    $qb->select([
          // <- Nuevo: ID del atributo
            'a.nombre as atributo', // <- Nombre del atributo    // <- ID del término
            't.nombre AS nombre', 
            'COUNT(DISTINCT p.id) AS total_productos'
        ])
        ->from(Atributos::class, 'a')
        ->innerJoin('a.terminos', 't')
        ->innerJoin('t.variaciones', 'v')
        ->innerJoin('v.productos', 'p')
        ->leftJoin('p.categorias', 'c')
        ->leftJoin('p.subcategorias','s')
        ->where('p.disponibilidad_producto = :available')
        ->andWhere('p.suspendido = :notSuspended')
        ->andWhere('p.borrador = :notDraft')
        ->andWhere('p.productos_tipo != :excludedType')
        ->groupBy('a.nombre, t.nombre')  // <- Agrupa por atributo y término
        ->orderBy('a.nombre, t.nombre', 'ASC')
        ->setParameters([
            ':available' => true,
            ':notSuspended' => false,
            ':notDraft' => false,
            ':excludedType' => 3
        ]);

    // Filtros opcionales
    if ($categoria !== null) {
        $qb->andWhere('c.slug = :categoria')->setParameter('categoria', $categoria);
    }
    if ($subcategoria !== null) {
        $qb->andWhere('s.slug = :subcategoria')->setParameter('subcategoria', $subcategoria);
    }

    return $qb->getQuery()->getResult();
}

 public function giftcard_aprovada($producto, $user){
    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('p')
        ->from('App:Productos', 'p')
        ->innerJoin('p.detallePedidos','d')
        ->innerJoin('d.pedido','pd')
        ->where('pd.estado = :estado')
        ->andWhere('pd.login = :login')
        ->andWhere('p.productos_tipo = :tipo')
        ->andWhere('p.slug_producto = :nombre_producto')
        ->setParameter('tipo', 3) 
        ->setParameter('nombre_producto', $producto)
        ->setParameter('login', $user)
        ->setParameter('estado', 'APPROVED')
        ->setMaxResults(1);

    return $query->getQuery()->getOneOrNullResult();

 }
  

}
