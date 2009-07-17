<?php
include('retorno.php');

function retorno_automatico ($dados) {
  print_r ($dados);
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 STRICT//EN"
  "http://www.w3.org/TR/xml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Recebemos seu pedido</title>
  </head>
  <body>
    <h1>Recibo personalizado</h1>
    <p>Quando não vierem dados via POST, a biblioteca não deve chamar função retorno_automatico, acima, nem executar die(), de modo que recibo HTML que eu escrevo aqui embaixo seja exibido automaticamente.</p>
  </body>
</html>
