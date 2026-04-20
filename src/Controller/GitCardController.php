<?php

namespace App\Controller;

use App\Entity\Carrito;
use App\Entity\DetalleCarrito;
use App\Entity\EntregasTipo;
use App\Entity\Estados;
use App\Entity\Login;
use App\Entity\Productos;
use App\Entity\ProductosGaleria;
use App\Entity\ProductosTipo;
use App\Entity\ProductosVentas;
use App\Entity\Tiendas;
use App\Form\GitcardType;
use App\Interfaces\ErrorsInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GitCardController extends AbstractController
{
    private  $em;
    private $errorsInterface;

    public function __construct(EntityManagerInterface $em, ErrorsInterface $errorsInterface){

        $this->em = $em;  // Injecting EntityManager into the controller.
        $this->errorsInterface = $errorsInterface;
    }
    #[Route('/api/productos/gift_card', name: 'app_gift_card_list', methods:['GET'])]
    #[OA\Tag(name: 'GiftCard')]
    #[OA\Response(
        response: 200,
        description: 'Lista de GitCards ',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user= $this->getUser();
        $tienda= $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $products = $this->em->getRepository(Productos::class)->findBy(['tienda'=>$tienda,'productos_tipo'=>3]);

        $productsArray = [];
        foreach ($products as $product) {
            $productsArray[] = [
                'id' => $product->getId(),
                'git_card' => $product->getNombreProducto(),
                'email' => $product->getDescripcionCortaProducto(),
                'nombre'=>$product->getDescripcionLargaProducto(),
                'precio' => $product->getPrecioNormalProducto(),
                'productos_tipo'=>$product->getProductosTipo() ? $product->getProductosTipo()->getTipo():''
            ];
        }

        return $this->json($productsArray);
        
    }

    #[Route('/api/productos/gift_card/new', name: 'app_gift_card_new', methods:['POST'])]
    #[OA\Tag(name: 'GiftCard')]
    #[OA\RequestBody(
        description: 'Añadir una GITcard a la tienda.',
        content: new Model(type: GitcardType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new (Request $request): Response
    {
        $user= $this->getUser();
        $tienda= $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $estado= $this->em->getRepository(Estados::class)->findOneBy(['id'=>5]);
        $tipo_venta= $this->em->getRepository(ProductosVentas::class)->findOneBy(['id'=>1]);
        $entregas_tipo= $this->em->getRepository(EntregasTipo::class)->findOneBy(['id'=>2]);

        $producto_tipo = $this->em->getRepository(ProductosTipo::class)->findOneBy(['id'=>3]);

        $producto = new Productos();

        $form = $this->createForm( GitcardType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $email=$form->get('email')->getData();
            $nombre=$form->get('nombre')->getData();
            $precio= $form->get('precio')->getData();
            $code= $form->get('tipo')->getData();

            $producto->setDescripcionCortaProducto($email);

            $producto->setDescripcionLargaProducto($nombre);

            $producto->setPrecioNormalProducto($precio);

            $slug = strtolower(str_replace(' ', '-', $nombre));

                // Convierte caracteres acentuados y especiales a su equivalente ASCII
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);

                // Elimina cualquier carácter que no sea una letra, número o guión
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

                // Limitar el slug a una longitud máxima (opcional)
            $slug = substr($slug, 0, 100);

                // Añadir un identificador único para evitar duplicados
            $slug = $slug . '-' . uniqid();
            $producto->setNombreProducto($code.'-'.$nombre);
            $producto->setSlugProducto($code.'-'.$slug);
            $producto->setProductosTipo($producto_tipo);
            $producto->setEstado($estado);
            $producto->setProductosVentas(productos_ventas: $tipo_venta);
            $producto->setEntrgasTipo($entregas_tipo);
            $producto->setTieneIva(true);
            $producto->setImpuestosIncluidos(false);
            $producto->setVariable(false);
            $producto->setBorrador(false);
            $producto->setTienda($tienda);
            $producto->setCantidadProducto(1);
            

            $imagen = new ProductosGaleria();

            $imagen->setUrlProductoGaleria('giftcard.jpg');

            $imagen->setProducto($producto);
            $this->em->persist($imagen);
            $this->em->persist($producto);
            $this->em->flush();

            $this->addcarrito($user, $producto);


            return $this->errorsInterface->succes_message('Guardado');
        }

        return $this->errorsInterface->form_errors($form);
        
    }


    #[Route('/api/productos/gift_card/{id}', name: 'app_gift_card_edit', methods:['PATCH'])]
    #[OA\Tag(name: 'GiftCard')]
    #[OA\RequestBody(
        description: 'Edita una GitCard de la tienda',
        content: new Model(type: GitcardType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit($id, Request $request): Response
    {
        $user= $this->getUser();
        $tienda= $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $producto = $this->em->getRepository(Productos::class)->findOneBy(['id' => $id, 'tienda' => $tienda, 'productos_tipo'=>3]);

        if ($producto === null) {
            return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(GitcardType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email=$form->get('email')->getData();
            $nombre=$form->get('nombre')->getData();
            $precio= $form->get('precio')->getData();

            $producto->setDescripcionCortaProducto($email);

            $producto->setDescripcionLargaProducto($nombre);

            $producto->setPrecioNormalProducto($precio);

            $producto->setFechaEdicion(new DateTime());
            $this->em->persist($producto);
           
            $this->em->flush();


            return $this->errorsInterface->succes_message('Editado');
        }


        // Aplica el método form_string_error para devolver el error en formato consistente
        return $this->errorsInterface->form_string_error($form, ['id_producto' => $producto->getId()]);
    }

    private function addcarrito($user, $producto){

        $u= $this->em->getRepository(Login::class)->find($user);
        $carrito = $this->em->getRepository(Carrito::class)->findOneBy(['login' => $u]);
        $p = $this->em->getRepository(Productos::class)->find($producto);

        if (!$carrito) {
            $carrito = new Carrito();
            $carrito->setLogin($u);
            $carrito->setFecha(new DateTime());
            $this->em->persist($carrito);
            $this->em->flush();
        }

            $detalleCarrito = new DetalleCarrito();
            $detalleCarrito->setCarrito($carrito);
            $detalleCarrito->setIdProducto($p);
            $detalleCarrito->setCantidad(1);
            $this->em->persist($detalleCarrito);
            $this->em->flush();
    }


    #[Route('/api/productos/gift_card/{id}', name: 'app_git_card_delete', methods:['DELETE'])]
    #[OA\Tag(name: 'GiftCard')]
    #[OA\Response(
        response: 200,
        description: 'Elimina una GtCard de la tienda',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete($id): Response
    {
        $user= $this->getUser();
        $tienda= $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $producto = $this->em->getRepository(Productos::class)->findOneBy(['id' => $id, 'tienda' => $tienda, 'productos_tipo'=>3]);

        if ($producto === null) {
            return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($producto);
        $this->em->flush();

        return $this->errorsInterface->succes_message('GiftCard eliminado');
    }

}
