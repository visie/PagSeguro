# -*- encoding: utf-8 -*-

# para executar este teste, o PagSeguroTestServer deve estar rodando junto com a loja modelo

import retorno
import twill

b = twill.get_browser()
b._browser._factory.is_html = True
twill.browser = b
t = twill.shell.commands

def base(uri=''):
  return 'http://0.0.0.0:8080/'+uri

# Escolha do produto
t.go (base())
t.fv(1, 'id', '1')
t.submit()

# Submete o formulário
t.fv(2, 'submit', '')
t.submit('submit')

# Na área de retorno, definir como será o envio dos dados
t.fv(1, 'TipoPagamento', 'Boleto')
t.fv(1, 'StatusTransacao', 'Aprovado')
t.submit()

t.show()