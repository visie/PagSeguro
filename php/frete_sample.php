<?php
include ('frete.php');
$frete = new PgsFrete;
print '<pre>';
$frete->debug(true);
$saida = $frete->gerar('28030-120', '2.300', '300', '02022-000');
print "\n";
print_r ($saida);
