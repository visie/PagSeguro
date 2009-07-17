<?php
include ('simpletest/autorun.php');
include ('pgs.php');

class PgsTest extends UnitTestCase
{
    function testMkArgs()
    {
        $string = 'nome=Mike';
        $result = Pgs::mkArgs($string);
        $this->assertEqual(array('nome' => 'Mike'), $result, 'Retornou o valor esperado para array simples. %s');

        $string = 'nome=Mike&idade=12';
        $result = Pgs::mkArgs($string);
        $this->assertEqual(array('nome' => 'Mike', 'idade' => 12), $result, 'Retornou o valor esperado para array com mais de um valor. %s');

        $string = 'nome=Michael Granados';
        $result = Pgs::mkArgs($string);
        $this->assertEqual(array('nome' => 'Michael Granados'), $result, 'Trabalhando com nomes com espaço entre eles. %s');

        $string = 'nome=Michael Granados';
        $result = Pgs::mkArgs($string, 'nome=Thiago');
        $this->assertEqual(array('nome' => 'Michael Granados'), $result, 'Sobrescrevendo o valor default. %s');

        $string = '';
        $result = Pgs::mkArgs($string, 'nome=Thiago');
        $this->assertEqual(array('nome' => 'Thiago'), $result, 'Trabalhando com o valor default. %s');

        $string = 'idade=12';
        $result = Pgs::mkArgs($string, 'nome=Thiago&idade=15');
        $this->assertEqual(array('nome' => 'Thiago', 'idade' => 12), $result, 'Brincando com os dois: default e args. Args deve sobrescrever default. %s');

        $array = array('nome' => 'Luciano');
        $result = Pgs::mkArgs($array, 'nome=Thiago&idade=15');
        $this->assertEqual(array('nome' => 'Luciano', 'idade' => 15), $result, 'Deve aceitar array para args. Args deve sobrescrever default. %s');

        $string = 'nome=Mike&idade=13';
        $result = Pgs::mkArgs($string, 'nome=Luciano&idade=15&sexo=m');
        $this->assertEqual(array('nome' => 'Mike', 'idade' => 13, 'sexo' => 'm',), $result, 'Deve aceitar string para default. Args deve sobrescrever default. %s');
    }

    function testDump() {
        $carrinho = new Pgs();
        $result = $carrinho->dump('config');
        $this->assertEqual("[CONFIG email_cobranca: '', tipo: 'CP', moeda: 'BRL']", $result, 'Fez o dump do config com os valores padrão. %s');
        $result = $carrinho->dump('config', 'moeda');
        $this->assertEqual("BRL", $result, 'Fez o dump do config com oo valor de moeda. %s');
    }

    function testCliente () {
        $carrinho = new Pgs();
        $carrinho->cliente('nome', 'Michael');
        $result = $carrinho->dump('cliente');
        $this->assertEqual("[CLIENTE nome: 'Michael']", $result, 'Adiciona cliente usando valor e chave. %s');

        $carrinho = new Pgs();
        $carrinho->cliente('nome=Eduardo');
        $result = $carrinho->dump('cliente');
        $this->assertEqual("[CLIENTE nome: 'Eduardo']", $result, 'Adiciona cliente usando string. %s');

        $carrinho = new Pgs();
        $carrinho->cliente('nome=André&email=andre@gmail.com');
        $result = $carrinho->dump('cliente');
        $this->assertEqual("[CLIENTE nome: 'André', email: 'andre@gmail.com']", $result, 'Aceita varios valores usando string. %s');

        $carrinho->cliente('nome=Lucas');
        $result = $carrinho->dump('cliente');
        $this->assertEqual("[CLIENTE nome: 'Lucas', email: 'andre@gmail.com']", $result, 'Altera o valor, mas mantém os anteriores. %s');

        $carrinho->cliente(array ('nome' => 'Gabriel'));
        $result = $carrinho->dump('cliente');
        $this->assertEqual("[CLIENTE nome: 'Gabriel', email: 'andre@gmail.com']", $result, 'Aceita array como valor. %s');

        $carrinho->cliente(array ('nome' => 'Henrique'), 'luciano');
        $result = $carrinho->dump('cliente');
        $this->assertEqual("[CLIENTE nome: 'Henrique', email: 'andre@gmail.com']", $result, 'Ignora o segundo valor, se o primeiro for um array. %s');

        $carrinho = new Pgs();
        $this->expectError('Valor para cliente invalido: idade', 'Não deveria ter aceitado esta chave (idade). %s');
        $carrinho->cliente('idade', 25);

        $carrinho = new Pgs();
        $this->expectError('Valor para cliente invalido: telefone', 'Não deveria ter aceitado esta chave (telefone). %s');
        $carrinho->cliente('nome=Michael&email=michaelgranados@gmail.com&telefone=25');
    }

}