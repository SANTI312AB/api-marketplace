# 🛒 Marketplace API

API desarrollada con Symfony 6.4 para un ecosistema de Marketplace donde cualquier usuario puede comprar y vender. Este backend centraliza pagos, logística, marketing e inteligencia artificial.

## ✨ Características y Conectores
* **Autenticacion:** Con **Google y AppleID**. 
* **Pagos:** Integración con **PlacetoPay** y **PayPal**.
* **Logística:** Gestión de envíos a través de **Servientrega** y **Delivereo**.
* **IA & Asistencia:** Chatbot integrado con **Ollama (LLM)** para soporte en tiempo real.
* **CRM & Marketing:** Sincronización con **HubSpot**.
* **Emailing:** Notificaciones transaccionales vía **SMTP**.
* **Configuración Dinámica:** Gestión de credenciales de terceros directamente desde la base de datos (tabla `generales_app`).

## 🛠️ Requisitos Técnicos

* **PHP:** 8.5 o superior.
* **Framework:** Symfony 6.4.
* **Base de Datos:** MySQL 8.4 LTS.
* **Dependencias:** Composer.

---

## 🚀 Despliegue de la API

Puedes poner en marcha este proyecto eligiendo una de las siguientes dos opciones:

### Opción A: Despliegue con Docker (Recomendado)
Esta opción automatiza el entorno de ejecución, asegurando que todas las dependencias funcionen de forma aislada.

1.  **Clonar el proyecto:**
    ```bash
    git clone [https://github.com/tu_usuario/shopbyback.git](https://github.com/tu_usuario/shopbyback.git)
    cd shopbyback
    ```
2.  **Preparar el entorno:**
    Copia el archivo de variables y ajusta los parámetros de base de datos si es necesario:
    ```bash
    cp .env .env.local
    ```
3.  **Levantar servicios:**
    ```bash
    docker-compose up -d
    ```
4.  **Instalar dependencias y Migraciones:**
    ```bash
    docker-compose exec php composer install
    docker-compose exec php bin/console doctrine:migrations:migrate
    ```

### Opción B: Despliegue Local (WampServer / Manual)
Ideal si prefieres gestionar tu propio servidor web y base de datos localmente.

1.  **Requisitos de SSL:**
    Para que las integraciones (PayPal, HubSpot, etc.) no fallen por errores de conexión, debes configurar el certificado CA:
    * Descarga `cacert.pem` desde [curl.se](https://curl.se/ca/cacert.pem).
    * En tu `php.ini`, configura las rutas:
        ```ini
        curl.cainfo = "C:\ruta\hacia\cacert.pem"
        openssl.cafile = "C:\ruta\hacia\cacert.pem"
        ```
2.  **Instalación:**
    ```bash
    composer install
    ```
3.  **Configuración de Base de Datos:**
    Edita el archivo `.env` y configura tu conexión a MySQL 8.4:
    ```env
    DATABASE_URL="mysql://root:password@127.0.0.1:3306/db?serverVersion=8.4.0"
    ```
4.  **Ejecutar Migraciones:**
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

---

## 🔐 Seguridad y Autenticación (JWT)

Este proyecto utiliza **LexikJWTAuthenticationBundle** para la seguridad de la API. Es estrictamente necesario generar las llaves SSH para la firma de los tokens.

### Generación de Llaves (.pem)

Para la versión 2.xx de este paquete, puedes usar Web-Token y generar Claves web JSON () y conjuntos de teclas web JSON () en lugar de Claves codificadas en PEM.JWKJWKSet

Por favor, consulte la página dedicada :d oc:'Web-Token feature <10-web-token>' para consultar Más información.

$ php bin/console lexik:jwt:generate-keypair

Tus claves caerán en y (a menos que hayas configurado una ruta diferente) config/jwt/
Configura la ruta de las claves SSL y la frase de contraseña en el archivo .env.


Accede a la tabla **`generales_app`** en tu cliente SQL para configurar:
* Tokens de PlacetoPay y PayPal.
* Endpoints de Servientrega y Delivereo.
* API Key de HubSpot.
* Configuración del modelo Ollama y credenciales SMTP.

## Swager Acces 
url: localhost/doc
user: swagger
password: abc1234

## Nota: 
Asegurarse de que los trigers y enventos estan en la base de dato para un correcto funcionamiento, si no ejecutarlos. Se encuentra en la carpeta:  /script