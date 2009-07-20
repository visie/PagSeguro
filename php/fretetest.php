<?php
require_once ('simpletest/autorun.php');
require_once ('frete.php');

class RequisicaoTest extends UnitTestCase 
{
    function testSetUse()
    {
        $frete = new PgsFrete;
        $this->assertTrue($frete instanceof PgsFrete);
        $this->assertEqual('curl', $frete->getUse(), 'Por padrao, usa o CURL. Apenas se o CURL existir este teste eh executado.');
        $frete->setUse('curl');
        $this->assertEqual('curl', $frete->getUse(), 'Foi setado o CURL');
        $this->expectException();
        $frete->setUse('invalido');
        $this->assertEqual('curl', $frete->getUse(), 'Mantem o ultimo.');
        $this->expectException();
        $frete->setUse(array());
        $this->assertEqual('curl', $frete->getUse(), 'Mantem o ultimo.');
    }

    function testDebugger() {
        $frete = new PgsFrete;
        $this->assertFalse($frete->debug(), 'O metodo de debug por padrão é false');
        $frete->debug(true);
        $this->assertTrue($frete->debug(), 'O metodo de debug foi setado para true');
        ob_start(); $frete->setUse('curl');
        $this->assertEqual("\nMethod changed to CURL", ob_get_clean(), 'O Debug funciona para setUse. %s');
        ob_end_clean();
    }

    function testRequestDebug() {
        $frete = new PgsFrete;
        $frete->setUse('curl');
        $frete->debug(true);
        ob_start(); $frete->request('http://google.com');
        $this->assertEqual("\nTrying to get 'http://google.com' using CURL", ob_get_clean(), 'O Debug funciona para setUse. %s');
    }
}
