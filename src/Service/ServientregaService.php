<?php

namespace App\Service;

use App\Entity\Ciudades;
use App\Entity\DetalleCarrito;
use App\Entity\DetallePedido;
use App\Entity\GeneralesApp;
use App\Entity\Impuestos;
use App\Entity\Pedidos;
use App\Entity\Servientrega;
use App\Entity\MetodosEnvio;
use App\Entity\TarifasServientrega;
use App\Entity\Tiendas;
use App\Entity\UsuariosDirecciones;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ServientregaService{

    private $em;

    private $params;


    private array $configServientrega = [];
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->params = $params;
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'servientrega']);
        foreach ($generales as $parametro) {
        $this->configServientrega[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }

    }


    public function servi_guias($pedidosArray, Tiendas $tienda)
    {

        $metodo = $this->em->getRepository(MetodosEnvio::class)->findOneBy(['id' => 1]);

        foreach ($pedidosArray as $pedidoData) {

            $peso = null;

            if ($pedidoData['peso_total'] <= 1 && $pedidoData['cantidad_total'] == 1) {
                $peso = $pedidoData['peso_total'];
                $id_p = 1;
            } elseif ($pedidoData['peso_total'] == 1 && $pedidoData['cantidad_total'] == 1) {
                $peso = $pedidoData['peso_total'];
                $id_p = 2;
            } elseif ($pedidoData['peso_total'] > 1 && $pedidoData['cantidad_total'] == 1) {
                $peso = $pedidoData['peso_total'];
                $id_p = 2;
            } elseif ($pedidoData['peso_total'] >= 1 && $pedidoData['cantidad_total'] >= 2) {
                $peso = $pedidoData['peso_total'];
                $id_p = 2;
            } elseif ($pedidoData['peso_total'] < 1 && $pedidoData['cantidad_total'] >= 2) {
                $peso = 1;
                $id_p = 2;
            }


            $params = [

                'ID_TIPO_LOGISTICA' => 1,
                'DETALLE_ENVIO_1' => "",
                'DETALLE_ENVIO_2' => "",
                'DETALLE_ENVIO_3' => "",
                'ID_CIUDAD_ORIGEN' => $pedidoData['vendedor']['id_ciudad_remite'],
                'ID_CIUDAD_DESTINO' => $pedidoData['cliente']['id_ciudad_envio'],
                'ID_DESTINATARIO_NE_CL' => $pedidoData['cliente']['dni'],
                'RAZON_SOCIAL_DESTI_NE' => $pedidoData['cliente']['nombre'],
                'NOMBRE_DESTINATARIO_NE' => $pedidoData['cliente']['nombre'],
                'APELLIDO_DESTINATAR_NE' => $pedidoData['cliente']['apellido'],
                'DIRECCION1_DESTINAT_NE' => $pedidoData['cliente']['direccion_principal'] . "-" . $pedidoData['cliente']['direccion_secundaria'] . "-" . $pedidoData['cliente']['ubicacion_referencia'],
                'SECTOR_DESTINAT_NE' => "",
                'TELEFONO1_DESTINAT_NE' => $pedidoData['cliente']['celular'],
                'TELEFONO2_DESTINAT_NE' => "",
                'CODIGO_POSTAL_DEST_NE' => $pedidoData['cliente']['codigo_postal'],
                'ID_REMITENTE_CL' => $pedidoData['vendedor']['dni'],
                'RAZON_SOCIAL_REMITE' => "",
                'NOMBRE_REMITENTE' => $pedidoData['vendedor']['nombre'],
                'APELLIDO_REMITE' => $pedidoData['vendedor']['apellido'],
                'DIRECCION1_REMITE' => $pedidoData['vendedor']['direccion_remite'],
                'SECTOR_REMITE' => "",
                'TELEFONO1_REMITE' => $pedidoData['vendedor']['celular'],
                'TELEFONO2_REMITE' => "",
                'CODIGO_POSTAL_REMI' => $pedidoData['vendedor']['codigo_postal'],
                'ID_PRODUCTO' => $id_p,
                'CONTENIDO' => $pedidoData['productos'],
                'NUMERO_PIEZAS' => $pedidoData['cantidad_total'],
                'VALOR_MERCANCIA' => $pedidoData['valor_total'],
                'VALOR_ASEGURADO' => $pedidoData['valor_asegurado'],
                'LARGO' => 0,
                'ANCHO' => 0,
                'ALTO' => 0,
                'PESO_FISICO' => $peso,
                'LOGIN_CREACION' => $this->configServientrega['Login'],
                'PASSWORD' => $this->configServientrega['SecretKey']
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_URL,value: $this->configServientrega['Url']);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                return new JsonResponse(['message' => 'error'], 400);
            }

            $returnCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $messageData = json_decode($result, true);

            $msj = isset($messageData['msj']) ? $messageData['msj'] : '';
            $id = isset($messageData['id']) ? $messageData['id'] : '';

            if ($msj == 'GUÍA REGISTRADA CORRECTAMENTE') {

                $pedido = $this->em->getRepository(Pedidos::class)->find($pedidoData['id_pedido']);
                $pedido->setGuiaContador(1);
                $registroEntidad = new Servientrega();
                $registroEntidad->setPedido($pedido);
                $registroEntidad->setNPedido($pedidoData['n_pedido']);
                $registroEntidad->setFechaPedido($pedidoData['fecha_pedido']);
                $registroEntidad->setIdCiudadEnvio($pedidoData['cliente']['id_ciudad_envio']);
                $registroEntidad->setCiudadEnvio($pedidoData['cliente']['ciudad_envio']);
                $registroEntidad->setDireccionPrincipal($pedidoData['cliente']['direccion_principal']);
                $registroEntidad->setDireccionSecundaria($pedidoData['cliente']['direccion_secundaria']);
                $registroEntidad->setCodigoPostal($pedidoData['cliente']['codigo_postal']);
                $registroEntidad->setUbicacionReferencia($pedidoData['cliente']['ubicacion_referencia']);
                $registroEntidad->setNombre($pedidoData['cliente']['nombre']);
                $registroEntidad->setApellido($pedidoData['cliente']['apellido']);
                $registroEntidad->setDni($pedidoData['cliente']['dni']);
                $registroEntidad->setCeular($pedidoData['cliente']['celular']);
                $registroEntidad->setIdCiudadRemite($pedidoData['vendedor']['id_ciudad_remite']);
                $registroEntidad->setCiudadRemite($pedidoData['vendedor']['ciudad_remite']);
                $registroEntidad->setDireccionRemite($pedidoData['vendedor']['direccion_remite'] . ' ' . 'n_piso/casa: ' . $pedidoData['vendedor']['n_casa']);
                $registroEntidad->setNombreVendedor($pedidoData['vendedor']['nombre']);
                $registroEntidad->setApellidoVendedor($pedidoData['vendedor']['apellido']);
                $registroEntidad->setDniVendedor($pedidoData['vendedor']['dni']);
                $registroEntidad->setCelularVendedor($pedidoData['vendedor']['celular']);
                $registroEntidad->setPesoTotal($pedidoData['peso_total']);
                $registroEntidad->setCantidadTotal($pedidoData['cantidad_total']);
                $registroEntidad->setValorTotal($pedidoData['valor_total']);
                $registroEntidad->setValorSeguro($pedidoData['valor_asegurado']);
                $registroEntidad->setProductos($pedidoData['productos']);
                $registroEntidad->setCodigoServientrega($id);
                $registroEntidad->setMsjServientrega($msj);
                $registroEntidad->setResponseServientrega($returnCode);
                $registroEntidad->setTienda($tienda);
                $registroEntidad->setMetodoEnvio($metodo);
                $registroEntidad->setObservacion($pedidoData['observacion']);
                $this->em->persist($registroEntidad);

            } else {

                return ['success' => false, 'message' => $msj, 'status' => $returnCode];
            }

            $this->em->flush();

        }

        return [
            'success' => true,
            'message' => 'Guías registradas correctamente'
        ];
    }


    public function booking_servientrega($pedido)
    {
        $impusto2 = $this->em->getRepository(Impuestos::class)->findOneBy(['id' => 2]);
        $seguro_envio = $impusto2->getIva();
        $P = $this->em->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido, 'estado' => 'APPROVED']);

        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['id' => $P->getTienda()->getId()]);

        $pedidos = $this->em->getRepository(DetallePedido::class)->servi_guias($pedido, $tienda);


        if (!$pedidos) {
            throw new Exception('No se encontro el pedido', Response::HTTP_NOT_FOUND);
        }


        foreach ($pedidos as $pedido) {
            if ($pedido->getPedido()->getGuiaContador() == 1) {
                throw new Exception('Las guías ya fueron generadas.', Response::HTTP_CONFLICT);
            }
        }


        $pedidosArray = [];
        $camposVacios = [];
        foreach ($pedidos as $pedido) {


            $terminosString = '';

            $direccion = $pedido->getIdDireccion();


            $variaciones = $pedido->getIdVariacion() ? $pedido->getIdVariacion() : null;

            if ($variaciones !== null) {
                foreach ($variaciones->getTerminos() as $termino) {

                    // Concatenar los términos en un solo string
                    $terminosString .= $termino->getAtributos()->getNombre() . ': ' . $termino->getNombre() . ', ';
                }

                // Eliminar la coma y el espacio extra al final
                $terminosString = rtrim($terminosString, ', ');
            }

            // Agrupar por direccion el producto
            if (!isset($pedidosArray[$direccion])) {
                $id_ciudad = $pedido->getPedido()->getIdDireccion();
                $ciudad = $this->em->getRepository(Ciudades::class)->find($id_ciudad);
                $fullName = $pedido->getPedido()->getCustomer();

                $parts = preg_split('/[ -]/', $fullName, 2); // Divide por espacio o guion

                $nombre = $parts[0];
                $apellido = isset($parts[1]) ? $parts[1] : '';
                $pedidosArray[$direccion] = [

                    'id_pedido' => $pedido->getPedido()->getId(),
                    'n_pedido' => $pedido->getPedido()->getNumeroPedido(),
                    'fecha_pedido' => $pedido->getPedido()->getFechaPedido(),
                    'observacion' => $pedido->getIdProductos()->getDirecciones() ? $pedido->getIdProductos()->getDirecciones()->getObservacion() : '',
                    'cliente' => [
                        'id_ciudad_envio' => $ciudad->getIdServientrega(),
                        'ciudad_envio' => $pedido->getPedido()->getCustomerCity(),
                        'direccion_principal' => $pedido->getPedido()->getDireccionPrincipal(),
                        'direccion_secundaria' => $pedido->getPedido()->getDireccionSecundaria(),
                        'codigo_postal' => $pedido->getPedido()->getCodigoPostalCustomer(),
                        'ubicacion_referencia' => $pedido->getPedido()->getUbicacionReferencia(),
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'dni' => $pedido->getPedido()->getDniCustomer(),
                        'celular' => $pedido->getPedido()->getCelularCustomer()
                    ],

                    'vendedor' => [
                        'id_ciudad_remite' => $pedido->getIdDireccion(),
                        'ciudad_remite' => $pedido->getCiudadRemite(),
                        'direccion_remite' => $pedido->getDireccionRemite() . ',' . $pedido->getReferencia(),
                        'nombre' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getNombre(),
                        'apellido' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getApellido(),
                        'dni' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getDni(),
                        'celular' => $pedido->getIdProductos()->getTienda()->getLogin()->getUsuarios()->getCelular(),
                        'codigo_postal' => $pedido->getIdProductos()->getDirecciones() ? $pedido->getIdProductos()->getDirecciones()->getCodigoPostal() : '',
                        'n_casa' => $pedido->getIdProductos()->getDirecciones() ? $pedido->getIdProductos()->getDirecciones()->getNCasa() : ''
                    ],

                    'productos' => '',
                    'peso_total' => null,
                    'cantidad_total' => null,
                    'valor_total' => null,
                    'valor_asegurado' => null
                ];


            }
            $peso = $pedido->getIdProductos()->getPeso() * $pedido->getCantidad();

            $productosString = $pedido->getIdProductos()->getNombreProducto() . '' . $terminosString . ',';
            $pedidosArray[$direccion]['peso_total'] += $peso;
            $pedidosArray[$direccion]['cantidad_total'] += $pedido->getCantidad();
            $pedidosArray[$direccion]['valor_total'] += $pedido->getSubtotal();
            $pedidosArray[$direccion]['valor_asegurado'] += $pedido->getSubtotal() * $seguro_envio;
            $pedidosArray[$direccion]['productos'] .= $productosString;


            // Validar campos nulos en cliente
            foreach ($pedidosArray[$direccion]['cliente'] as $key => $value) {
                // Excluir los campos 'ubicacion_referencia' y 'codigo_postal' de la validación
                if ($key !== 'ubicacion_referencia' && $key !== 'codigo_postal' && $value === null) {
                    $camposVacios[] = "El campo '$key' del cliente está vacío.";
                }
            }

            // Validar campos nulos en vendedor
            foreach ($pedidosArray[$direccion]['vendedor'] as $key => $value) {
                if ($value === null) {
                    $camposVacios[] = "El campo '$key' del vendedor está vacío.";
                }
            }


        }

        if (!empty($camposVacios)) {
            throw new Exception(implode(', ', $camposVacios), Response::HTTP_BAD_REQUEST);
        }


        $serviGuiasResponse = $this->servi_guias($pedidosArray, $tienda);

        if (isset($serviGuiasResponse['success']) && $serviGuiasResponse['success'] === true) {
            // Si la función se ejecutó correctamente, retornar el mensaje
            return new JsonResponse(['message' => $serviGuiasResponse['message'], 'contenido' => $pedidosArray], Response::HTTP_OK);
        } else {
            // Si hubo un error, retornar el mensaje de error
            throw new Exception($serviGuiasResponse['message'], $serviGuiasResponse['status']);
        }

    }


    public function pdf_servientrega($codigo_orden){
          $servi_user=$this->configServientrega['Login'];
          $password= $this->configServientrega['SecretKey'];

          $url = $this->configServientrega['PDF'] . "['$codigo_orden','$servi_user','$password','1']";

          return $url;     
    }

    public function soap(){
         $wsl= $this->configServientrega['WSL'];
         $soap= $this->configServientrega['SOAP'];
         

         $data=[
            'wsl'=>$wsl,
            'soap'=>$soap

         ];
         return $data;
    }


    public function custom_serviguias(array $pedidoData)
    {
        $metodo = $this->em->getRepository(MetodosEnvio::class)->findOneBy(['id' => 1]);
        // Lógica para peso o producto, ejemplo básico
        $id_p = 2; // valor por defecto
        $peso = max(1, $pedidoData['peso_total'] ?? 1);

        if ($pedidoData['peso_total'] <= 1 && $pedidoData['cantidad_total'] == 1) {
            $id_p = 1;
        }
        $params = [
            'ID_TIPO_LOGISTICA' => 1,
            'DETALLE_ENVIO_1' => "",
            'DETALLE_ENVIO_2' => "",
            'DETALLE_ENVIO_3' => "",
            'ID_CIUDAD_ORIGEN' => $pedidoData['origen'] ?? '',
            'ID_CIUDAD_DESTINO' => $pedidoData['destino'] ?? '',
            'ID_DESTINATARIO_NE_CL' => $pedidoData['cliente']['dni'],
            'RAZON_SOCIAL_DESTI_NE' => $pedidoData['cliente']['nombre'],
            'NOMBRE_DESTINATARIO_NE' => $pedidoData['cliente']['nombre'],
            'APELLIDO_DESTINATAR_NE' => $pedidoData['cliente']['apellido'],
            'DIRECCION1_DESTINAT_NE' => $pedidoData['cliente']['direccion_principal'] . "-" . $pedidoData['cliente']['direccion_secundaria'] . "-" . $pedidoData['cliente']['ubicacion_referencia'],
            'SECTOR_DESTINAT_NE' => "",
            'TELEFONO1_DESTINAT_NE' => $pedidoData['cliente']['celular'],
            'TELEFONO2_DESTINAT_NE' => "",
            'CODIGO_POSTAL_DEST_NE' => $pedidoData['cliente']['codigo_postal'],
            'ID_REMITENTE_CL' => $pedidoData['vendedor']['dni'],
            'RAZON_SOCIAL_REMITE' => "",
            'NOMBRE_REMITENTE' => $pedidoData['vendedor']['nombre'],
            'APELLIDO_REMITE' => $pedidoData['vendedor']['apellido'],
            'DIRECCION1_REMITE' => $pedidoData['vendedor']['direccion_remite'],
            'SECTOR_REMITE' => "",
            'TELEFONO1_REMITE' => $pedidoData['vendedor']['celular'],
            'TELEFONO2_REMITE' => "",
            'CODIGO_POSTAL_REMI' => $pedidoData['vendedor']['codigo_postal'],
            'ID_PRODUCTO' => $id_p,
            'CONTENIDO' => $pedidoData['productos'],
            'NUMERO_PIEZAS' => $pedidoData['cantidad_total'],
            'VALOR_MERCANCIA' => $pedidoData['valor_total'],
            'VALOR_ASEGURADO' => $pedidoData['valor_asegurado'],
            'LARGO' => 0,
            'ANCHO' => 0,
            'ALTO' => 0,
            'PESO_FISICO' => $peso,
            'LOGIN_CREACION' => $this->configServientrega['Login'],
            'PASSWORD' => $this->configServientrega['SecretKey']
        ];

        // Enviar a Servientrega
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_URL, $this->configServientrega['Url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            return new JsonResponse(['message' => curl_error($ch)], 400);
        }

        $returnCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $messageData = json_decode($result, true);
        $msj = isset($messageData['msj']) ? $messageData['msj'] : '';
        $id = isset($messageData['id']) ? $messageData['id'] : '';

        if ($msj == 'GUÍA REGISTRADA CORRECTAMENTE') {
            $registroEntidad = new Servientrega();
            $registroEntidad->setNPedido($pedidoData['n_pedido']);
            $registroEntidad->setFechaPedido(new \DateTime($pedidoData['fecha_pedido']));
            $registroEntidad->setIdCiudadEnvio($pedidoData['cliente']['id_ciudad_envio']);
            $registroEntidad->setCiudadEnvio($pedidoData['cliente']['ciudad_envio']);
            $registroEntidad->setDireccionPrincipal($pedidoData['cliente']['direccion_principal']);
            $registroEntidad->setDireccionSecundaria($pedidoData['cliente']['direccion_secundaria']);
            $registroEntidad->setCodigoPostal($pedidoData['cliente']['codigo_postal']);
            $registroEntidad->setUbicacionReferencia($pedidoData['cliente']['ubicacion_referencia']);
            $registroEntidad->setNombre($pedidoData['cliente']['nombre']);
            $registroEntidad->setApellido($pedidoData['cliente']['apellido']);
            $registroEntidad->setDni($pedidoData['cliente']['dni']);
            $registroEntidad->setCeular($pedidoData['cliente']['celular']);
            $registroEntidad->setIdCiudadRemite($pedidoData['vendedor']['id_ciudad_remite']);
            $registroEntidad->setCiudadRemite($pedidoData['vendedor']['ciudad_remite']);
            $registroEntidad->setDireccionRemite($pedidoData['vendedor']['direccion_remite'] . ' ' . 'n_piso/casa: ' . $pedidoData['vendedor']['n_casa']);
            $registroEntidad->setNombreVendedor($pedidoData['vendedor']['nombre']);
            $registroEntidad->setApellidoVendedor($pedidoData['vendedor']['apellido']);
            $registroEntidad->setDniVendedor($pedidoData['vendedor']['dni']);
            $registroEntidad->setCelularVendedor($pedidoData['vendedor']['celular']);
            $registroEntidad->setPesoTotal($pedidoData['peso_total']);
            $registroEntidad->setCantidadTotal($pedidoData['cantidad_total']);
            $registroEntidad->setValorTotal($pedidoData['valor_total']);
            $registroEntidad->setValorSeguro($pedidoData['valor_asegurado']);
            $registroEntidad->setProductos($pedidoData['productos']);
            $registroEntidad->setCodigoServientrega($id);
            $registroEntidad->setMsjServientrega($msj);
            $registroEntidad->setResponseServientrega($returnCode);
            $registroEntidad->setMetodoEnvio($metodo);
            $registroEntidad->setObservacion($pedidoData['observacion']);
            $this->em->persist($registroEntidad);
            $this->em->flush();
            return new JsonResponse($messageData, $returnCode);

        }
        return new JsonResponse(['message' => $messageData], $returnCode);
    }



     public function calculo_envio_global($carrito,$iva,$seguro_envio,$region_usuario,$provincia_usuario,$ciudad_usuario){
        $tarifa_local= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>1]);
        $tarifa_cantonal= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>2]);
        $tarifa_provincial= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>3]);
        $tarifa_regional= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>4]);
        $tarifa_galapagos= $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id'=>6]);

    
        $un_kilo_local=$tarifa_local->getTarifas();
        $un_kilo_cantonal=$tarifa_cantonal->getTarifas();
        $un_kilo_provincial=$tarifa_provincial->getTarifas();
        $un_kilo_regional= $tarifa_regional->getTarifas();
        $un_kilo_galapagos= $tarifa_galapagos->getTarifas();
        $dos_kilos_local=$tarifa_local->getDosKilos();
        $dos_kilos_cantonal=$tarifa_cantonal->getDosKilos();
        $dos_kilos_provincial=$tarifa_provincial->getDosKilos();
        $dos_kilos_regional= $tarifa_regional->getDosKilos();
        $dos_kilos_galapagos=$tarifa_galapagos->getDosKilos();

        $kilo_adicional_local=$tarifa_local->getKiloAdicional();
        $kilo_adicional_cantonal=$tarifa_cantonal->getKiloAdicional();
        $kilo_adicional_provincial=$tarifa_provincial->getKiloAdicional();
        $kilo_adicional_regional=$tarifa_regional->getKiloAdicional();
        $kilo_adicional_galapagos=$tarifa_galapagos->getKiloAdicional();
    


         $datos = $this->em->getRepository(DetalleCarrito::class)->findByCarrito($carrito,$iva);
         $costo_envio_total=0;
         $totalPrecio_total=0;
         foreach ($datos as $dato) {
             $tiendaId = $dato['tienda_id'];
             $ciudad = $dato['ciudad'];
             $provincia = $dato['provincia'];
             $region = $dato['region'];
             $totalCantidad = $dato['total_cantidad'];
             $totalPeso = $dato['total_peso'];
             $totalPrecio = $dato['total_precio'];
 
               if ($ciudad_usuario !== null && $provincia_usuario !== null && $region_usuario !== null ) {
                 switch (true) {
                     case $totalPeso >= 2 && $totalPeso < 3:
                         if ($ciudad_usuario == $ciudad && $provincia_usuario == $provincia) {
                             $costo_envio = $dos_kilos_local;
                         } elseif ($provincia_usuario == $provincia && $ciudad_usuario !== $ciudad) {
                             $costo_envio = $dos_kilos_cantonal;
                         } elseif ($region_usuario == $region   && $provincia_usuario !== $provincia) {
                             $costo_envio = $dos_kilos_provincial;
                         }elseif($region_usuario !== $region &&  $region_usuario !== 'INSULAR' && $region !== 'INSULAR' && $provincia_usuario !== $provincia){
                                 $costo_envio = $dos_kilos_regional;
                         }else{
                             
                             $costo_envio = $dos_kilos_galapagos;
                         }
                         break;
             
                     case $totalPeso >= 3:
                         $kilos_adicionales = round($totalPeso) - 2;
                         if ($ciudad_usuario == $ciudad && $provincia_usuario == $provincia) {
                             $costo_envio = $dos_kilos_local + ($kilos_adicionales * $kilo_adicional_local);
                         } elseif ($provincia_usuario == $provincia && $ciudad_usuario !== $ciudad) {
                             $costo_envio = $dos_kilos_cantonal + ($kilos_adicionales * $kilo_adicional_cantonal);
                         } elseif ($region_usuario == $region && $provincia_usuario !== $provincia) {
                             $costo_envio = $dos_kilos_provincial + ($kilos_adicionales * $kilo_adicional_provincial);
                        }elseif($region_usuario !== $region &&  $region_usuario !== 'INSULAR' && $region !== 'INSULAR' && $provincia_usuario !== $provincia){
                             $costo_envio = $dos_kilos_regional + ($kilos_adicionales * $kilo_adicional_regional);
                         }
                         else{
                             $costo_envio = $dos_kilos_galapagos + ($kilos_adicionales * $kilo_adicional_galapagos);
                         }
                         break;
             
                     case $totalPeso > 0 &&  $totalPeso < 2:
                         if ($ciudad_usuario == $ciudad && $provincia_usuario == $provincia) {
                             $costo_envio = $un_kilo_local;
                         } elseif ($provincia_usuario == $provincia && $ciudad_usuario !== $ciudad) {
                             $costo_envio = $un_kilo_cantonal;
                         } elseif ($region_usuario == $region && $provincia_usuario !== $provincia) {
                             $costo_envio = $un_kilo_provincial;
                         } elseif($region_usuario !== $region &&  $region_usuario !== 'INSULAR' && $region !== 'INSULAR' && $provincia_usuario !== $provincia) {
                             $costo_envio = $un_kilo_regional;
                         }else{
                             $costo_envio = $un_kilo_galapagos;
                         }
                         break;
             
                     default:
                         $costo_envio = null;
                 }
             } else{
                 $costo_envio=null;
             }
 
             $costo_envio_total += $costo_envio;
     
             $totalPrecio_total += $totalPrecio;
         }  
            $valor_asegurado = $totalPrecio_total * $seguro_envio;
            $cn = $costo_envio_total + $valor_asegurado;
        
            // Si aplicas IVA, descomentar para calcularlo
            $iva_envio = ($cn * $iva) / 100;
        
            $costo_envio_final= $cn +$iva_envio;
            return $costo_envio_final;

     }


    public function calculo_envio_tienda( $carrito, $iva, $idTienda, $seguro_envio, $region_usuario, $provincia_usuario, $ciudad_usuario)
    {
        $tarifa_local = $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id' => 1]);
        $tarifa_cantonal = $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id' => 2]);
        $tarifa_provincial = $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id' => 3]);
        $tarifa_regional = $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id' => 4]);
        $tarifa_galapagos = $this->em->getRepository(TarifasServientrega::class)->findOneBy(['id' => 6]);


        $un_kilo_local = $tarifa_local->getTarifas();
        $un_kilo_cantonal = $tarifa_cantonal->getTarifas();
        $un_kilo_provincial = $tarifa_provincial->getTarifas();
        $un_kilo_regional = $tarifa_regional->getTarifas();
        $un_kilo_galapagos = $tarifa_galapagos->getTarifas();
        $dos_kilos_local = $tarifa_local->getDosKilos();
        $dos_kilos_cantonal = $tarifa_cantonal->getDosKilos();
        $dos_kilos_provincial = $tarifa_provincial->getDosKilos();
        $dos_kilos_regional = $tarifa_regional->getDosKilos();
        $dos_kilos_galapagos = $tarifa_galapagos->getDosKilos();

        $kilo_adicional_local = $tarifa_local->getKiloAdicional();
        $kilo_adicional_cantonal = $tarifa_cantonal->getKiloAdicional();
        $kilo_adicional_provincial = $tarifa_provincial->getKiloAdicional();
        $kilo_adicional_regional = $tarifa_regional->getKiloAdicional();
        $kilo_adicional_galapagos = $tarifa_galapagos->getKiloAdicional();


        $datos2 = $this->em->getRepository(DetalleCarrito::class)->findByCarrito_tienda($carrito, $iva, $idTienda);
        foreach ($datos2 as $dato2) {
            $tiendaId = $dato2['tienda_id'];
            $ciudad = $dato2['ciudad'];
            $provincia = $dato2['provincia'];
            $region = $dato2['region'];
            $totalCantidad = $dato2['total_cantidad'];
            $totalPeso = $dato2['total_peso'];
            $totalPrecio = $dato2['total_precio'];

            if ($ciudad_usuario !== null && $provincia_usuario !== null && $region_usuario !== null) {
                switch (true) {
                    case $totalPeso >= 2 && $totalPeso < 3:
                        if ($ciudad_usuario == $ciudad && $provincia_usuario == $provincia) {
                            $c_envio = $dos_kilos_local;
                        } elseif ($provincia_usuario == $provincia && $ciudad_usuario !== $ciudad) {
                            $c_envio = $dos_kilos_cantonal;
                        } elseif ($region_usuario == $region && $provincia_usuario !== $provincia) {
                            $c_envio = $dos_kilos_provincial;
                        } elseif ($region_usuario !== $region && $region_usuario !== 'INSULAR' && $region !== 'INSULAR' && $provincia_usuario !== $provincia) {
                            $c_envio = $dos_kilos_regional;
                        } else {

                            $c_envio = $dos_kilos_galapagos;
                        }
                        break;

                    case $totalPeso >= 3:
                        $kilos_adicionales = round($totalPeso) - 2;
                        if ($ciudad_usuario == $ciudad && $provincia_usuario == $provincia) {
                            $c_envio = $dos_kilos_local + ($kilos_adicionales * $kilo_adicional_local);
                        } elseif ($provincia_usuario == $provincia && $ciudad_usuario !== $ciudad) {
                            $c_envio = $dos_kilos_cantonal + ($kilos_adicionales * $kilo_adicional_cantonal);
                        } elseif ($region_usuario == $region && $provincia_usuario !== $provincia) {
                            $c_envio = $dos_kilos_provincial + ($kilos_adicionales * $kilo_adicional_provincial);
                        } elseif ($region_usuario !== $region && $region_usuario !== 'INSULAR' && $region !== 'INSULAR' && $provincia_usuario !== $provincia) {
                            $c_envio = $dos_kilos_regional + ($kilos_adicionales * $kilo_adicional_regional);
                        } else {
                            $c_envio = $dos_kilos_galapagos + ($kilos_adicionales * $kilo_adicional_galapagos);
                        }
                        break;

                    case $totalPeso > 0 && $totalPeso < 2:
                        if ($ciudad_usuario == $ciudad && $provincia_usuario == $provincia) {
                            $c_envio = $un_kilo_local;
                        } elseif ($provincia_usuario == $provincia && $ciudad_usuario !== $ciudad) {
                            $c_envio = $un_kilo_cantonal;
                        } elseif ($region_usuario == $region && $provincia_usuario !== $provincia) {
                            $c_envio = $un_kilo_provincial;
                        } elseif ($region_usuario !== $region && $region_usuario !== 'INSULAR' && $region !== 'INSULAR' && $provincia_usuario !== $provincia) {
                            $c_envio = $un_kilo_regional;
                        } else {
                            $c_envio = $un_kilo_galapagos;
                        }
                        break;

                    default:
                        $c_envio = null;
                }
            } else {
                $c_envio = null;
            }

        }
        // Cálculo de costo de envío final si no cumple las condiciones de envío gratuito
        $va_seguro = $totalPrecio * $seguro_envio;

        $cn_pedido = $c_envio + $va_seguro;
        $iva_envio_pedido = ($cn_pedido * $iva) / 100;
        $costo_envio_pedido = $cn_pedido + $iva_envio_pedido;
        $data['subtotal_envio'] = $cn_pedido;
        $data['iva_envio'] = $iva_envio_pedido;
        $data['costo_envio'] = $costo_envio_pedido;
        return $data;
    }

}