Elavon TPV
=====

Este script te permitirá generar los formularios para la integración de la pasarela de pago de Elavon (Santander).

## Instalación

Añade las dependencias vía composer: `"elavon/tpv": "1.*"`

```bash
composer update
```

O incluye el autoloader del paquete:

```php
require __DIR__.'/elavon-tpv/src/autoload.php';
```

## Ejemplo de pago

```php
# Incluye tu arquivo de configuración (copia config.php para config.local.php)

$config = require (__DIR__.'/config.local.php');

# Cargamos la clase con los parámetros base

$TPV = new Elavon\Tpv\Tpv($config);

# Indicamos los campos para el pedido

$TPV->setFormHiddens(array(
    'ORDER_ID' => '012121323',
    'AMOUNT' => '568,25'
));

# Rellenamos el formulario de pedido y redirigimos al TPV

echo '<form action="'.$TPV->getFormAction().'" method="post">'.$TPV->getFormHiddens().'</form>';

die('<script>document.forms[0].submit();</script>');

# O bien si quieres el formulario completo pero no quieres redirección

echo $TPV->getFormFull();

# O bien si quieres el formulario completo y que realice la redirección al TPV (igual que la primera opción)

die($TPV->getFormFullWithRedirect());

```

Para realizar el control de los pagos, la TPV se comunicará con nosotros a través de la url configurada en el panel del propio banco.

Este script no será visible ni debe responder nada, simplemente verifica el pago.

El banco siempre se comunicará con nosotros a través de esta url, sea correcto o incorrecto.

Podemos realizar un script (Lo que en el ejemplo sería http://dominio.com/direccion-control-pago) que valide los pagos de la siguiente manera:

```php
# Incluye tu arquivo de configuración (copia config.php para config.local.php)

$config = require (__DIR__.'/config.local.php');

# Cargamos la clase con los parámetros base

$TPV = new Elavon\Tpv\Tpv($config);

# Realizamos la comprobación de la transacción

try {
    $datos = $TPV->checkTransaction($_POST);
    $success = true;
    $message = '';
} catch (Exception $e) {
    $datos = $_POST;
    $success = false;
    $message = $e->getMessage();
}

# Actualización del registro en caso de pago (ejemplo Laravel)

if (empty($datos['ORDER_ID'])) {
    Log::error('No se ha recibido el identificador de pedido');
    exit;
}

try {
    $order = Order::findOrFail($datos['ORDER_ID']);
} catch (Exception $e) {
    Log::error('El pedido indicado no existe');
    exit;
}

$order->tpv_post = json_encode($_POST);
$order->tpv_datos = json_encode($datos);
$order->tpv_respuesta = $datos['RESULT'];
$order->tpv_mensaje = $datos['MESSAGE'];

$order->save();

return $success ? view('tpv-ok') : view('tpv-ko');
```

Si deseas más información sobre parámetros u opciones, Google puede echarte una mano https://www.google.es/search?q=manual+instalaci%C3%B3n+santander+php+filetype%3Apdf
