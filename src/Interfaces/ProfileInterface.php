<?php

namespace App\Interfaces;

use App\Entity\DetallePedido;
use App\Entity\Login;
use App\Entity\Post;
use App\Entity\Productos;
use App\Entity\ProductosComentarios;
use App\Entity\Seguidores;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class ProfileInterface
{
    private $em;
    private $request;

    private $router;

    public function __construct(EntityManagerInterface $em, RequestStack $request, UrlGeneratorInterface $router)
    {

        $this->em = $em;
        $this->request = $request->getCurrentRequest();
        $this->router = $router;

    }

    public function private_profile(Login $login)
    {
        $host = $this->router->getContext()->getBaseUrl();
        $domain = $this->request->getSchemeAndHttpHost();


        $usuario = $this->em->getRepository(Usuarios::class)->findOneBy(['login' => $login]);
        $userData = null;
        if ($login) {

            $ventas = $this->numero_ventas($login);
         
            $tipo_documento = $usuario->getTipoDocumento();
            if ($tipo_documento === 'CI' || $tipo_documento === 'PPN' || $tipo_documento == null) {
                $apellido = $usuario->getApellido();
            } else {
                $apellido = null;
            }

            $avatarUrl = '';
            if ($login->getUsuarios() && $login->getUsuarios()->getAvatar() !== null) {
                $avatarUrl = $domain . $host . '/public/user/selfie/' . $login->getUsuarios()->getAvatar();
            }

            // Aquí puedes personalizar qué datos del usuario quieres devolver en el JSON
            $userData = [
                // 'id' => $user->getId(),
                'email' => $login->getEmail(),
                'username' => $login->getUsername(),
                'nombre' => $usuario->getNombre(),
                'apellido' => $apellido,
                'celular' => $usuario->getCelular(),
                'tipo_documento' => $usuario->getTipoDocumento() ? $usuario->getTipoDocumento() : '',
                'dni' => $usuario->getDni() ? $usuario->getDni() : '',
                'genero' => $usuario->getGenero() ? $usuario->getGenero() : '',
                'fecha_registro' => $login->getFechaRegistro()->format('c'),
                'fecha_nacimiento' => $usuario->getFechaNacimiento() ? $usuario->getFechaNacimiento()->format('c') : '',
                'avatar' => $avatarUrl,
                'verificacion' => $login->getVericacion()->getNobreEstado(),
                'tienda_oficial' => $login->getTiendas() ? $login->getTiendas()->getEstado()->getNobreEstado() === 'VERIFICADO' : false,
                'requiere_biométrico' => $usuario->isRequiereBiometrico(),
                'verificacion_biometrico' => $usuario->getEstados() ? $usuario->getEstados()->getNobreEstado() : '',
                'has_verified' => $usuario->isHasVerified(),
                'intentos_biometrico' => $usuario->getLimiteBiometrico(),
                'minimum_purchase_amount' => $usuario->getCompraMinimaBiometrico(),
                'productos_en_venta' => $ventas['productos'],
                'calificacion' => $ventas['calificacion'],
                'total_calificaiones' => $ventas['total_calificaciones'],
                'total_ventas' => $ventas['numero_ventas']
            ];
        }

        return new JsonResponse($userData);

    }



    public function public_profile(Login $login)
    {

        $userData = null;


        if ($login) {
            $host = $this->router->getContext()->getBaseUrl();
            $domain = $this->request->getSchemeAndHttpHost();


            $avatarUrl = '';
            if ($login->getUsuarios() && $login->getUsuarios()->getAvatar() !== null) {
                $avatarUrl = $domain . $host . '/public/user/selfie/' . $login->getUsuarios()->getAvatar();
            }

            $tipo_documento = $login->getUsuarios()->getTipoDocumento();

            if ($login->getUsuarios()->getDni() !== null && $login->getTiendas()->getEstado() !== 3) {
                if ($tipo_documento === 'CI' || $tipo_documento === 'PPN' || $tipo_documento === null) {
                    $apellido = $login->getUsuarios()->getApellido();
                } else {
                    $apellido = null;
                }
            } else {
                $apellido = null;
            }


            $dato = null;

            $nombre_tienda = $login->getTiendas()->getNombreTienda() ? $login->getTiendas()->getNombreTienda() : null;
            $nombre_usuario = $login->getUsuarios() ? $login->getUsuarios()->getNombre() : '';

            if ($nombre_tienda && $login->getTiendas()->getEstado()->getId() == 3) {
                $dato = $nombre_tienda;
            } else {
                $dato = $nombre_usuario;
            }

            $ventas = $this->numero_ventas($login);


            $userData = [
                'username' => $login->getUsername(),
                'nombre' => $dato,
                'apellido' => $apellido,
                'avatar' => $avatarUrl,
                'productos_en_venta' => $ventas['productos'],
                'calificacion' => $ventas['calificacion'],
                'total_calificaiones' => $ventas['total_calificaciones'],
                'total_ventas' => $ventas['numero_ventas'],
                'verificada' => $login->getTiendas()->getEstado() ? $login->getTiendas()->getEstado()->getNobreEstado() === 'VERIFICADO' : false,
                'fecha_registro' => $login->getFechaRegistro()->format('c')
            ];

        }



        return new JsonResponse($userData);
    }


    public function profile_setings(Login $login){
        

    }



    private function numero_ventas(Login $login)
    {

        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $login]);
        $producto = $this->em->getRepository(Productos::class)->count(['tienda' => $tienda, 'disponibilidad_producto' => true, 'suspendido' => false,'borrador'=>false]);
        $pedidos = $this->em->getRepository(DetallePedido::class)->filter_transactions($tienda);
        $comentarios = $this->em->getRepository(ProductosComentarios::class)->comentarios_tienda($tienda);

        $pedidosArray = [];
        $numero_ventas = 0;
        foreach ($pedidos as $pedido) {
            $numeroPedido = $pedido->getNumeroPedido();
            if (!isset($pedidosArray[$numeroPedido])) {
                $pedidosArray[$numeroPedido] = [
                    'numero_orden' => $numeroPedido
                ];
                $numero_ventas++;
            }
        }

        $total = 0;
        $count = 0;
        foreach ($comentarios as $comentario) {
            $calificacion = $comentario->getCalificacion();
            if ($calificacion !== null && $calificacion !== '') {
                $total += $calificacion;
                $count++;
            }

        }

        $promedio = $count > 0 ? $total / $count : 0;

        $data = [
            'productos' => $producto,
            'numero_ventas' => $numero_ventas,
            'total_calificaciones' => $count,
            'calificacion' => $promedio
        ];

        return $data;
    }


    private function esta_siguiendo()
    {

    }

}