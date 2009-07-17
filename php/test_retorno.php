<?php
define('TOKEN', '123456');

include_once('simpletest/autorun.php');
include_once('simpletest/web_tester.php');
include_once('retorno.php');

function url($url) {
  return "http://visie/pagseguro/php/branches/retorno{$url}";
}

class TesteWeb extends WebTestCase {
  function testPageExists() {
    $this->assertTrue($this->get(url('/user_retorno.php')));
    $this->assertTitle('Recebemos seu pedido');
  }
}

class RetornoClass extends UnitTestCase {
  function post() {
    return array (
      'VendedorEmail'    => 'emaildovendedor@nasa.gov',
      'TransacaoID'      => 'TRANSACAO597',
      'Referencia'       => '238',
      'TipoFrete'        => 'EN',
      'ValorFrete'       => '17,80',
      'Anotacao'         => 'Alguma anotação enviada pelo clien',
      'DataTransacao'    => '25/03/2009 18:30:28',
      'TipoPagamento'    => 'Boleto',
      'StatusTransacao'  => 'Aprovado',
      'CliNome'          => 'Cliente de Testes',
      'CliEmail'         => 'teste@nasa.gov',
      'CliEndereco'      => 'Rua Anatômica do sul',
      'CliNumero'        => '75',
      'CliComplemento'   => 'apto 580',
      'CliBairro'        => 'Palacio de Alcantara',
      'CliCidade'        => 'São Paulo',
      'CliEstado'        => 'SP',
      'CliCep'           => '28000000',
      'CliTelefone'      => '11 12345678',
      'ProdID_1'         => '150',
      'ProdDescricao_1'  => 'Um simples produto com uma descrição',
      'ProdValor_1'      => '1580,35',
      'ProdQuantidade_1' => '1',
      'ProdFrete'        => '150,00',
      'NumItens'         => '1',
    );
  }
  function testTipoEnviadoAoPrepara() {
    foreach (array('', new stdClass, 123, 12.15) as $item)
      $this->assertIdentical('', RetornoPagSeguro::_preparaDados($item, false), 'Nao eh aceito este tipo de dado: ' . gettype($item));
  }
  function testPeparaDados() {
    $post = $this->post();
    $retorno = RetornoPagSeguro::_preparaDados($post);
    foreach ($post as $key=>$value) {
      $value = urlencode(stripslashes($value));
      $value = preg_replace('/[^a-z]/', '.', $value);
      $this->assertPattern("@{$key}@", $retorno, "Possui {$key} na string preparada.");
      $this->assertPattern(":{$value}:", $retorno, "Possui {$value} na string preparada.");
    }
  }
  function testPreparaValidar() {
    $this->assertPattern('@Token=123456@', RetornoPagSeguro::_preparaDados('', true), 'O Token foi inserido');
    $this->assertPattern('@Comando=validar@', RetornoPagSeguro::_preparaDados('', true), 'Adicinado o Comando');
  }
  function testEnviarDadosCurl() {
    $post = $this->post();
    $this->assertTrue( RetornoPagSeguro::verifica( $post, array('curl', url('/verificar.php')) ) , 'O retorno foi um sussesso via cURL');
  }
  function testEnviarDadosFsocket() {
    global $_retPagSeguroErrNo, $_retPagSeguroErrStr;
    $post = $this->post();
    $socket = fsockopen('localhost', 80, $_retPagSeguroErrNo, $_retPagSeguroErrStr, 30);
    $tipo = array ('fsocket', url('/verificar.php'), $socket);
    $this->assertTrue( RetornoPagSeguro::verifica($post, $tipo), 'O retorno foi um sussesso via fSocket');
  }
  function testRunFunction () {
    $post = $this->post();
    $file='file_write_by_test_function';
    @unlink($file);
    RetornoPagSeguro::verifica( $post, array('curl', url('/verificar.php')));
    $this->assertTrue(file_exists($file), 'Foi chamada a funcao de retorno automatico');
    @unlink($file);
  }
}

function retorno_automatico ($referencia, $status, $post) {
  $s = print_r ($post, true);
  $f=fopen('file_write_by_test_function', 'a+');
  fwrite($f, $s);
  fclose($f);
}
