<?php

class PgsFrete
{
    private $_use     = 'curl';
    private $_debug   = false;
    private $_methods = array('curl');
    private $_result;

    public function PgsFrete()
    {
        if ($this->debug()) {
            echo "\nPgsFrete started!";
        }
    }

    public function debug($debug=null)
    {
        if (null===$debug) {
            return $this->_debug;
        }
        $this->_debug = (bool) $debug;
    }

    public function setUse($useMethod)
    {
        if ('string'!==gettype($useMethod)) {
            throw new Exception('Method for setUse not allowed.'.
              'Method passed: '.var_export($useMethod, true));
        }
        $useMethod = strtolower($useMethod);
        if (!in_array($useMethod, $this->_methods)) {
            throw new Exception('Method for setUse not allowed.'.
              'Method passed: '.var_export($useMethod, true));
        }
        $this->_use = $useMethod;
        if ($this->debug()) {
            echo "\nMethod changed to ".strtoupper($useMethod);
        }
    }

    public function getUse()
    {
        return $this->_use;
    }

    public function request($url, $post=null)
    {
        $method = $this->getUse();
        if (in_array($method, $this->_methods)) {
            $method_name = '_request'.ucWords($method);
            if (!method_exists($this, $method_name)) {
              throw new Exception("Method $method_name does not exists.");
            }
            if ($this->debug()) {
                echo "\nTrying to get '$url' using ".strtoupper($method);
            }
            return call_user_func(array($this, $method_name), $url, $post);
        } else {
            throw new Exception('Method not seted.');
        }
    }

    private function _requestCurl($url, $post=null)
    {
        $parse = parse_url($url);
        $ch    = curl_init();
        if ('https'===$parse['scheme']) {
            // Nao verificar certificado
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        }
        curl_setopt($ch, CURLOPT_URL, $url); // Retornar o resultado
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retornar o resultado
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true); // Ativa o modo POST
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Insere os POSTs
        }
        $result = curl_exec($ch);
        curl_close($ch);
        $this->_result = $result;
    }

    public function gerar($CepOrigem, $Peso, $Valor, $Destino)
    {
        $url = "https://pagseguro.uol.com.br/CalculaFrete.aspx";
        $this->request($url."?CepOrigem={$CepOrigem}&Peso={$Peso}&Valor={$Valor}");
        $result = $this->_result;
        $pos = strpos($result, '<form name="Form1" method="post"');
        $result = substr($result, $pos);
        $pos = strpos($result, '</form>');
        $result = substr($result, 0, $pos+8);

        preg_match('@(name="__VIEWSTATE".+value="([^"]+)")@', $result, $matches);
        $post = array(
            '__VIEWSTATE'   => $matches[2],
            'txtValor'      => $Valor,
            'txtCepDestino' => $Destino,
            'btnCalcular'   => 'Calcular'
        );
        $this->request($url, $post);
        $resultado = $this->_result;
        $resultado = preg_replace('/[\n\r\s]+/', ' ', $resultado);
        preg_match('@(Sedex).+>R\$ ([\d,]+)<.+(PAC).+>R\$ ([\d,]+)<@', $resultado, $matches);
        $return = array(
          $matches[1] => $matches[2],
          $matches[3] => $matches[4]
        );
        return $return;
    }
}
