<?php

namespace App\Repository;

use App\Entity\GeneralesApp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<GeneralesApp>
 *
 * @method GeneralesApp|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeneralesApp|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeneralesApp[]    findAll()
 * @method GeneralesApp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeneralesAppRepository extends ServiceEntityRepository
{
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneralesApp::class);
    }

    public function data_url(){
        $url= $this->findOneBy(['nombre'=>'placetopay','atributoGeneral'=>'Url']);

        return $url->getValorGeneral();
    }


    public function getLoginPTP(): array
    {
        // 1. Obtenemos TODOS los parámetros de 'placetopay' en una sola consulta
        $parametrosPtp = $this->findBy(['nombre' => 'placetopay']);

        // Si no se encuentra ningún parámetro, retornamos un array vacío
        if (empty($parametrosPtp)) {
            return [];
        }

        // 2. Creamos un array asociativo para un acceso más fácil ('Login' => 'valor', 'SecretKey' => 'valor')
        $config = [];
        foreach ($parametrosPtp as $parametro) {
            $config[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }

        // 3. Verificamos que obtuvimos los dos valores necesarios
        if (isset($config['Login']) && isset($config['SecretKey'])) {
            $seed = date('c');
            $rawNonce = random_bytes(16); // Más seguro que rand()
            $tranKey = base64_encode(hash('sha256', $rawNonce . $seed . $config['SecretKey'], true));
            $nonce = base64_encode($rawNonce);

            return [
                "locale" => "es_EC",
                "auth" => [
                    "login" => $config['Login'],
                    "tranKey" => $tranKey,
                    "nonce" => $nonce,
                    "seed" => $seed,
                ]
            ];
        }

        // Si no se encontraron los parámetros requeridos, retorna vacío
        return [];
    }

    public function crearPagoPTP($nombre, $apellido, $email, $dni, $documento, $telefono, $referencia, $descripcion, $total, $subtotal, $envio, $impuestos, $expiracion, $returnUrl, $ipUrl = "127.0.0.1", $skipResult = false)
    {
        $rest = [
            "buyer" => [
                "name" => $nombre,
                "surname" => $apellido,
                "email" => $email,
                "document" => $dni,
                "documentType" => $documento,
                "mobile"=> $telefono
            ],
            "payment" => [
                "reference" => $referencia,
                "description" => $descripcion,
                "amount" => [
                    "currency" => "USD",
                    "total" => $total,
                    "details"=> [
                        [
                          "kind"=> "subtotal",
                          "amount"=> $subtotal
                        ],
                        [
                            "kind"=>"shipping",
                            "amount"=> $envio
                        ]
                    ],
                    "taxes"=> [
                        [
                          "kind"=> "valueAddedTax",
                          "amount"=> $impuestos,
                          "base"=> $subtotal
                        ],
                    ],
                ]
            ],
            "expiration" => $expiracion,
            "returnUrl" => $returnUrl,
            "ipAddress" => $ipUrl,
            "userAgent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
            "paymentMethod" => null,
            "skipResult" => $skipResult,
            "noBuyerFill"=> false,
            "allowPartial"=> false
        ];
    
        return $rest;
    }



    public function notificacion(){
        $rest = [

            "status"=> [
                "status"=> "APPROVED",
                "reason"=> "00",
                "message"=> "La petición ha sido aprobada exitosamente",
                "date"=> "2024-01-09T11:31:22-05:00"
            ],
              "requestId"=> 709009,
              "reference"=> "PED-001-8111",
              "signature"=> ""
        ];


        return $rest;
    }

    public function registrarTarjeta($referencia,$descripcion,$expiracion,$returnUrl,$ipUrl="127.0.0.1",$skipResult=true){
        $rest = [

            "subscription"=> [
                "reference"=>$referencia,
                "description"=>$descripcion,

            ],
            "expiration" =>$expiracion,
            "returnUrl" => $returnUrl,
            "ipAddress" =>$ipUrl,
            "userAgent" =>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
            "skipResult" =>$skipResult
        ];


        return $rest;
    }

    public function pagarTarjetaGuardadas($nombre,$apellido,$email,$dni,$documento,$telefono,$referencia,$descripcion,$total,$subtotal,$envio,$impuestos,$expiracion,$returnUrl,$token,$ipUrl="127.0.0.1",$skipResult=true){
        $rest = [

            "buyer" => [
                "name" => $nombre,
                "surname" => $apellido,
                "email" => $email,
                "document" => $dni,
                "documentType" => $documento,
                "mobile"=> $telefono
                
                
            ],
            "payment" => [
                "reference" => $referencia,
                "description" => $descripcion,
                "amount" => [
                    "currency" => "USD",
                    "total" => $total,
                    "details"=> [
                        [
                          "kind"=> "subtotal",
                          "amount"=> $subtotal
                        ],
                        [
                            "kind"=>"shipping",
                            "amount"=> $envio
                        ]
                    ],
                    "taxes"=> [
                        [
                          "kind"=> "valueAddedTax",
                          "amount"=> $impuestos,
                          "base"=> 0
                        ],
                ],
    
                ]
            ],
            "instrument"=> [
                "token"=>$token,

            ],
            "expiration" =>$expiracion,
            "returnUrl" => $returnUrl,
            "ipAddress" =>$ipUrl,
            "userAgent" =>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
            "paymentMethod"=>null,
            "skipResult" =>$skipResult
        ];


        return $rest;
    }
}
