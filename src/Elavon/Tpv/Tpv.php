<?php
namespace Elavon\Tpv;

use Exception;

class Tpv
{
    /**
     * Variables de configuración obligatorias
     *
     * @var array
     */
    private $configRequired = array('Environment', 'MERCHANT_ID', 'ACCOUNT', 'KEY', 'CURRENCY', 'AUTO_SETTLE_FLAG');

    /**
     * Variables de configuración que se enviarán desde el formulario
     *
     * @var array
     */
    private $configInputs = array('MERCHANT_ID', 'ACCOUNT', 'CURRENCY', 'AUTO_SETTLE_FLAG');

    /**
     * Variables de pedido obligatorias
     *
     * @var array
     */
    private $orderRequired = array('ORDER_ID', 'AMOUNT');

    /**
     * Variables con las que se generará el hash SHA1 para el formulario
     *
     * @var array
     */
    private $hashInputColumns = array('TIMESTAMP', 'MERCHANT_ID', 'ORDER_ID', 'AMOUNT', 'CURRENCY');

    /**
     * Entornos posibles
     *
     * @var array
     */
    private $environments = array(
        'test' => 'https://hpp.prueba.santanderelavontpvvirtual.es/pay',
        'real' => 'https://hpp.santanderelavontpvvirtual.es/pay'
    );

    /**
     * Almacén de variables de configuración post-procesadas
     *
     * @var array
     */
    private $config = array();

    /**
     * Almacén de variables de pedido post-procesadas
     *
     * @var array
     */
    private $order = array();

    /**
     * Cargador de la clase
     *
     * @param array $config
     *
     * @return self
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);

        return $this;
    }

    /**
     * Cargador de variables de configuración
     *
     * @param array $config
     *
     * @throws Exception
     *
     * @return self
     */
    private function setConfig(array $config)
    {
        $this->checkRequired($config, $this->configRequired);

        if (empty($this->environments[$config['Environment']])) {
            throw new Exception(sprintf('El valor de Envirnoment (%s) no es correcto', $config['Environment']));
        }

        $config['URL'] = $this->environments[$config['Environment']];

        $this->config = $config;

        return $this;
    }

    /**
     * Cargador de variables de pedido
     *
     * @param array $order
     *
     * @return self
     */
    public function setFormHiddens(array $order)
    {
        $this->order = $this->getOrder($order);

        return $this;
    }

    /**
     * Respuesta con los campos input type="hidden" para el formulario
     *
     * @return string
     */
    public function getFormHiddens()
    {
        $inputs = '';

        foreach ($this->order as $name => $value) {
            $inputs .= '<input type="hidden" name="'.$name.'" value="'.$value.'">';
        }

        return $inputs;
    }

    /**
     * Respuesta con la url del formulario
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->config['URL'];
    }

    /**
     * Respuesta con el formulario completo
     *
     * @param string $id
     *
     * @return string
     */
    public function getFormFull($id = null)
    {
        return '<form id="'.$this->getFormId($id).'" action="'.$this->getFormAction().'" method="post">'
            .$this->getFormHiddens().'</form>';
    }

    /**
     * Respuesta con el formulario completo y la redirección por javascript
     *
     * @param string $id
     *
     * @return string
     */
    public function getFormFullWithRedirect($id = null)
    {
        return $this->getFormFull($id).$this->getFormRedirect($id);
    }

    /**
     * Respuesta con el javascript que realiza la redirección del formulario
     *
     * @param string $id
     *
     * @return string
     */
    public function getFormRedirect($id = null)
    {
        return '<script>document.getElementById("'.$this->getFormId($id).'").submit();</script>';
    }

    /**
     * Devuelve un identificador de formulario
     *
     * @param string $id
     *
     * @return string
     */
    private function getFormId($id)
    {
        return $id ?: 'elavon-tpv';
    }

    /**
     * Validación y procesado del pedido
     *
     * @param array $order
     *
     * @return array
     */
    private function getOrder(array $order)
    {
        $this->checkRequired($order, $this->orderRequired);

        foreach ($this->configInputs as $name) {
            $order[$name] = $this->config[$name];
        }

        $order['TIMESTAMP'] = date('YmdHis');
        $order['AMOUNT'] = $this->getAmount($order['AMOUNT']);
        $order['SHA1HASH'] = $this->hash($order, $this->hashInputColumns);

        return $order;
    }

    /**
     * Método auxiliar de comprobación de variables obligatorias
     *
     * @param array $values
     * @param array $columns
     *
     * @throws Exception
     */
    private function checkRequired(array $values, array $columns)
    {
        foreach ($columns as $name) {
            if (empty($values[$name])) {
                throw new Exception(sprintf('El campo %s es obligatorio', $name));
            }
        }
    }

    /**
     * Generación del hash SHA1
     *
     * @param array $values
     * @param array $columns
     *
     * @return string
     */
    private function hash(array $values, array $columns)
    {
        return sha1(sha1($this->concat($values, $columns)).'.'.$this->config['KEY']);
    }

    /**
     * Validación de la respuesta bancaria de un pago de pedido
     *
     * @param array $input Variables recibidas vía POST
     *
     * @throws Exception
     *
     * @return array
     */
    public function checkTransaction(array $input)
    {
        $columns = array('TIMESTAMP', 'MERCHANT_ID', 'ORDER_ID', 'RESULT', 'MESSAGE', 'PASREF', 'AUTHCODE');

        $input['MERCHANT_ID'] = $this->config['MERCHANT_ID'];

        $this->checkRequired($input, $columns + array('SHA1HASH'));

        if ($input['RESULT'] !== '00') {
            throw new Exception(sprintf('El resultado de la operación ha devuelto el error %s', $input['RESULT']));
        }

        if ($input['SHA1HASH'] !== $this->hash($input, $columns)) {
            throw new Exception('La firma de la operación es incorrecta');
        }

        return $input;
    }

    /**
     * Devuelve un valor válido para la cantidad
     *
     * @param mixed $amount
     *
     * @return integer
     */
    public function getAmount($amount)
    {
        if (empty($amount)) {
            return '000';
        }

        if (preg_match('/[\d]+\.[\d]+,[\d]+/', $amount)) {
            $amount = str_replace('.', '', $amount);
        }

        if (strpos($amount, ',') !== false) {
            $amount = floatval(str_replace(',', '.', $amount));
        }

        return (round($amount, 2) * 100);
    }

    /**
     * Unión de un array mediante puntos
     *
     * @param array $values
     * @param array $columns
     *
     * @return string
     */
    private function concat(array $values, array $columns)
    {
        $string = array();

        foreach ($columns as $name) {
            $string[] = $values[$name];
        }

        return implode('.', $string);
    }
}
