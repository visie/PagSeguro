<?php
/**
 * Retorno automático do PagSeguro
 *
 * Faz a requisição e verificação do POST recebido pelo PagSeguro
 *
 * PHP Version 5
 *
 * @category  PagSeguro
 * @package   PagSeguro
 * @author    Michael Castillo Granados <mike@visie.com.br>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link      http://visie.com.br/pagseguro
 */

if (!defined('TOKEN')) {
    define('TOKEN', '');
}

/**
 * RetornoPagSeguro
 *
 * Classe de manipulação para o retorno do post do pagseguro
 *
 * @category PagSeguro
 * @package  PagSeguro
 * @author   dgmike <mike@visie.com.br>
 * @license  http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link     http://visie.com.br/pagseguro
 */

class RetornoPagSeguro
{
    /**
     * _preparaDados
     *
     * Prepara os dados vindos do post e converte-os para url, adicionando
     * o token do usuario quando necessario.
     *
     * @param array $post        Array contendo os posts do pagseguro
     * @param bool  $confirmacao Controlando a adicao do token no post
     *
     * @return string
     *
     * @access private
     *
     * @internal é usado pela {@see RetornoPagSeguro::verifica} para gerar os,
     * dados que serão enviados pelo PagSeguro
     */
    function _preparaDados($post, $confirmacao=true)
    {
        if ('array' !== gettype($post)) {
            $post = array();
        }
        if ($confirmacao) {
            $post['Comando'] = 'validar';
            $post['Token']   = TOKEN;
        }
        $retorno = array();
        foreach ($post as $key => $value) {
            if ('string' !== gettype($value)) {
                $post[$key] = '';
            }
            $value     = urlencode(stripslashes($value));
            $retorno[] = "{$key}={$value}";
        }
        return implode('&', $retorno);
    }

    /**
     * _tipoEnvio
     *
     * Checa qual será a conexao de acordo com a versao do PHP
     * preferencialmente em CURL ou via socket
     *
     * em CURL o retorno será:
     * <code>
     *     array ('curl','https://pagseguro.uol.com.br/Security/NPI/Default.aspx')
     * </code>
     * já em socket o retorno será:
     *
     * <code>
     *     array ('fsocket', '/Security/NPI/Default.aspx', $objeto_de_conexao)
     * </code>
     *
     * se não encontrar nenhum nem outro:
     *
     * <code>
     *     array ('','')
     * </code>
     *
     * @access private
     * @global string $_retPagSeguroErrNo   Numero de erro do pagseguro
     * @global string $_retPagSeguroErrStr  Texto descritivo do erro do pagseguro
     * @return array                        Array com as configurações
     *
     */
    function _tipoEnvio()
    {
        // Prefira utilizar a função CURL do PHP
        // Leia mais sobre CURL em: http://us3.php.net/curl
        global $_retPagSeguroErrNo, $_retPagSeguroErrStr;
        if (function_exists('curl_exec')) {
            return array(
                'curl', 
                'https://pagseguro.uol.com.br/Security/NPI/Default.aspx'
                );
        } elseif ((PHP_VERSION >= 4.3) && 
              ($fp = @fsockopen('ssl://pagseguro.uol.com.br',
              443, $_retPagSeguroErrNo, $_retPagSeguroErrStr, 30))
        ) {
            return array('fsocket', '/Security/NPI/Default.aspx', $fp);
        } elseif ($fp = @fsockopen('pagseguro.uol.com.br', 80, 
            $_retPagSeguroErrNo, $_retPagSeguroErrStr, 30)
        ) {
            return array('fsocket', '/Security/NPI/Default.aspx', $fp);
        }
        return array ('', '');
    }

    /**
     * _confirma
     *
     * Faz a parte Server-Side, verificando os dados junto ao PagSeguro
     *
     * @param array $tipoEnvio Array com a configuração gerada
     *                         por {@see Retorno::_tipoEnvio()}
     * @param array $post      Dados vindos no POST do PagSeguro para serem
     *                         verificados
     *
     * @return bool
     * @global string $_retPagSeguroErrNo   Numero de erro do pagseguro
     * @global string $_retPagSeguroErrStr  Texto descritivo do erro do pagseguro
     */
    function _confirma($tipoEnvio, $post) 
    {
        global $_retPagSeguroErrNo, $_retPagSeguroErrStr;
        $spost    = RetornoPagSeguro::_preparaDados($post);
        $confirma = false;
        // Usando a biblioteca cURL
        if ($tipoEnvio[0] === 'curl') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tipoEnvio[1]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $spost);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // Deve funcionar apenas em teste
            if (defined('PAGSEGURO_AMBIENTE_DE_TESTE')) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            $resp = curl_exec($ch);
            if (!RetornoPagSeguro::notNull($resp)) {
                curl_setopt($ch, CURLOPT_URL, $tipoEnvio[1]);
                $resp = curl_exec($ch);
            }
            curl_close($ch);
            $confirma = (strcmp($resp, 'VERIFICADO') == 0);

            // Usando fsocket
        } elseif ($tipoEnvio[0] === 'fsocket') {
            if (!$tipoEnvio[2]) {
                die ("{$_retPagSeguroErrStr} ($_retPagSeguroErrNo)");
            } else {
                $cabecalho  = "POST {$tipoEnvio[1]} HTTP/1.0\r\n";
                $cabecalho .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $cabecalho .= "Content-Length: " . strlen($spost) . "\r\n\r\n";
                $resp       = '';
                fwrite($tipoEnvio[2], "{$cabecalho}{$spost}");
                while (!feof($tipoEnvio[2])) {
                    $resp = fgets($tipoEnvio[2], 1024);
                    if (strcmp($resp, 'VERIFICADO') == 0) {
                        $confirma = true;
                        break;
                    }
                }
                fclose($tipoEnvio[2]);
            }
        }
        return $confirma;
    }

    /**
     * notNull
     *
     * Extraido de OScommerce 2.2 com base no original do pagseguro,
     * Checa se o valor e nulo
     *
     * @param mixed $value Variável a ser checada se é nula
     *
     * @return bool
     * @access public
     */
    function notNull($value)
    {
        if (is_array($value)) {
            if (sizeof($value) > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            if (($value != '') && (strtolower($value) != 'null') &&
                (strlen(trim($value)) > 0)
            ) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * verifica
     *
     * Verifica o tipo de conexão aberta e envia os dados vindos
     * do post
     *
     * @param array $post      Array contendo os posts do pagseguro
     * @param bool  $tipoEnvio (opcional) Verifica o tipo de envio do post
     *
     * @access public
     * @use RetornoPagSeguro::_tipoenvio()
     * @use RetornoPagSeguro::_confirma()
     * @return bool
     */

    function verifica($post, $tipoEnvio=false)
    {
        if ('array' !== gettype($tipoEnvio)) {
            $tipoEnvio = RetornoPagSeguro::_tipoEnvio();
        }
        if (!in_array($tipoEnvio[0], array('curl', 'fsocket'))) {
            return false;
        }
        $confirma = RetornoPagSeguro::_confirma($tipoEnvio, $post);

        if ($confirma && function_exists('retorno_automatico')) {
            $itens = array (
                    'VendedorEmail', 'TransacaoID', 'Referencia', 'TipoFrete',
                    'ValorFrete', 'Anotacao', 'DataTransacao', 'TipoPagamento',
                    'StatusTransacao', 'CliNome', 'CliEmail', 'CliEndereco',
                    'CliNumero', 'CliComplemento', 'CliBairro', 'CliCidade',
                    'CliEstado', 'CliCEP', 'CliTelefone', 'NumItens',
                    );
            foreach ($itens as $item) {
                if (!isset($post[$item])) {
                    $post[$item] = '';
                }
                if ($item=='ValorFrete') {
                    $post[$item] = str_replace(',', '.', $post[$item]);
                }
            }
            $produtos = array ();
            for ($i=1;isset($post["ProdID_{$i}"]);$i++) {
                $produtos[] = self::makeProd($post, $i);
            }
            retorno_automatico($post['Referencia'], 
                    $post['StatusTransacao'], 
                    (object) $post);
        }
        return $confirma;
    }

    /**
     * Gera o produto baseado no post e no id enviados
     *
     * @param array $post O post enviado pelo PagSeguro
     * @param int   $i    ID do produto que deseja gerar
     *
     * @return array
     */
    public function makeProd ($post, $i)
    {
        $post += array ("ProdFrete_{$i}"=>0, "ProdExtras_{$i}" => 0);
        return array (
                'ProdID'          => $post["ProdID_{$i}"],
                'ProdDescricao'   => $post["ProdDescricao_{$i}"],
                'ProdValor'       => self::convertNumber($post["ProdValor_{$i}"]),
                'ProdQuantidade'  => $post["ProdQuantidade_{$i}"],
                'ProdFrete'       => self::convertNumber($post["ProdFrete_{$i}"]),
                'ProdExtras'      => self::convertNumber($post["ProdExtras_{$i}"]),
                );
    }

    /**
     * Converte o numero enviado para padrão numerico
     *
     * @param string|int|double $number Numero que deseja converter
     * 
     * @return double
     */

    function convertNumber ($number)
    {
        return (double) (str_replace(',', '.', $number));
    }
    
    /**
     * Grava histórias no arquivo de log do sistema
     *
     * @param mixed $message Mensagem que gostaria de gravar no log
     *
     * @return void
     */
    public function keepLog($message)
    {
        file_put_contents(dirname(__FILE__) . "/" . __FILE__ . ".log",
            var_export($message, true), FILE_APPEND);
    }
}

if ($_POST) {
    RetornoPagSeguro::verifica($_POST);
    die();
}
