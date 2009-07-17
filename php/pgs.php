<?php

/**
 * Biblioteca PagSeguro para implementar o carrinho de compras
 *
 * PHP version 5
 *
 * @category  PagSeguro
 * @package   PagSeguro
 * @author    DGmike <mike@visie.com.br>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link      http://visie.com.br/pagseguro
 */

if (!defined('PAGSEGURO_AMBIENTE')) {
    define('PAGSEGURO_AMBIENTE', 'PRODUCAO');
}

/**
 * Carrinho do PagSeguro
 *
 * Classe de manipulação para efetuar o carrinho de compras do PagSeguro
 *
 * @category PagSeguro
 * @package  PagSeguro
 * @author   Dgmike <mike@visie.com.br>
 * @license  http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link     http://visie.com.br/pagseguro
 */
class Pgs
{
    /**
     * Configurações do Carrinho de compras
     * @access private
     */
    private $_config = array();

    /**
     * Configurações do Carrinho de compras
     * @access private
     */
    private $_cliente = array();

    /**
     * Instancia o novo objeto de carrinho
     *
     * você pode passar os parâmetros padrão alterando as informações padrão
     * como o tipo de moeda ou o tipo de carrinho (próprio ou do pagseguro)
     *
     * Ex:
     * <code>
     * array (
     *   'email_cobranca' => 'raposa@vermelha.com.br',
     *   'tipo'           => 'CBR',
     *   'ref_transacao'  => 'A36',
     *   'tipo_frete'     => 'PAC',
     * )
     * </code>
     *
     * @param string|array $args Argumentos de configuração do carrinho
     *
     * @uses   Pgs::mkArgs
     * @access public
     * @return void
     */
    public function __construct ($args=null)
    {
        $args          = self::mkArgs($args, array(
            'email_cobranca' => '',
            'tipo'           => 'CP',
            'moeda'          => 'BRL',
        ));
        $this->_config = $args;
    }

    /**
     * Cria array de configurações, você pode usar um array ou uma string como
     * parâmetro inicial. Ele fará as devidas substituições no default.
     *
     * Retorna a mensagem de erro
     *
     * @param array|string $args    Argumentos passados pelo usuário
     * @param array|string $default Argumentos que serão colocados caso o usuário
     *                              não passe
     *
     * @access public
     * @return array
     */
    static function mkArgs($args, $default = array())
    {
        foreach (array('args', 'default') as $item) {
            if (!in_array(gettype($$item), array('array', 'string'))) {
                $$item = array();
            }
            if ('string' === gettype($$item)) {
                parse_str($$item, $$item);
            }
        }
        return $args + $default;
    }

    /**
     * Quer saber como está a configuração do Objeto e todos os seus argumentos
     *
     * Se você não passar nenhum parâmetro, ele retornará uma string com o resumo
     * geral do seu carrinho
     *
     * @param string $area  Área que você deseja ver os detalhes.
     * @param string $valor Valor da área que você deseja receber
     * 
     * @access public
     * @return string
     */
    public function dump($area=null, $valor = null)
    {
        if (!in_array($area, array(null, 'config', 'cliente'))) {
            return "[area nao encontrada: $area]";
        }
        if ($valor  == null) {
            $base   = '%s: \'%s\'';
            $return = array();
            foreach ($this->{"_$area"} as $key => $value) {
                $return[] = sprintf($base, $key, $value);
            }
            return '['.strtoupper($area).' '.implode(', ', $return).']';
        } else {
            return isset($this->{"_$area"}[$valor]) ? $this->{"_$area"}[$valor] : '';
        }
    }

    /**
     * Adicione valores para seu cliente. Assim, ao chegar na tela de pagamento
     * do PagSeguro, ele não precisará preencher com todos os seus dados.
     *
     * A chave pode ser um array de configurações (desta forma, o segundo 
     * parâmetro será ignorado) Também pode ser uma string de configuração no 
     * formato query_string. Caso seja passado um segundo parâmetro para a 
     * função, a chave será usada como chave mesmo.
     *
     * @param array|string $chave Chave do valor do cliente
     * @param string       $valor (opcional) Valor que será adicionado à chave
     *
     * @return object Pgs
     */
    public function cliente ($chave, $valor=null)
    {
        if (2===func_num_args() && 'string' === gettype($chave)) {
            $chave = array($chave => $valor);
        }
        $args    = self::mkArgs($chave, $this->_cliente);
        $validos = array('nome', 'cep', 'end', 'num', 'compl', 'bairro', 'cidade', 
                         'uf', 'pais', 'ddd', 'tel', 'email');
        foreach (array_keys($args) as $key) {
            if (!in_array($key, $validos)) {
                trigger_error("Valor para cliente invalido: $key");
            }
        }
        $this->_cliente = $args;
        return $this;
    }
}
