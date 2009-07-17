<?php
include('frete.php');
$frete = new PgsFrete;

$r = $frete->gerar('03823-010', '1.300', '19,30', '28030-120');

print '<pre class="debug">'.print_r($r, true)."</pre>";


/*

https://pagseguro.uol.com.br/CalculaFrete.aspx
?CepOrigem=03823-010
&Peso=1.300
&Valor=19,30

-----

__VIEWSTATE=/wEPDwULLTIwOTI3NDY1MzkPZBYCAgEPZBYSAgcPDxYCHgRUZXh0BQxTUCAoQ2FwaXRhbClkZAIJDw8WAh4HVmlzaWJsZWdkZAIMDw8WAh8BZxYCHgpvbktleVByZXNzBS5mb3JtYXRhQ0VQKCdDRVAnLCB3aW5kb3cuZXZlbnQua2V5Q29kZSwgdGhpcyk7ZAIODw8WAh8ABQYxLDMga2dkZAIQDw8WAh8ABQUxOSwzMBYCHwIFL3JldHVybihjdXJyZW5jeUZvcm1hdCh0aGlzLCAnJywgJywnLCA5LCBldmVudCkpZAIUDw8WAh8BaGRkAhYPZBYCAgEPD2QWAh4Hb25jbGljawUScmV0dXJuIEdlcmFIVE1MKCk7ZAIYDxYCHwAFCTAzODIzLTAxMGQCGQ8WAh8ABQMxLDNkZLcqjr0JNK8WpogPCOp2sGej0lBr

&txtValor=19,30

&txtCepDestino=02022-000
&btnCalcular=Calcular

*/
