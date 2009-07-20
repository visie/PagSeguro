# -*- encoding: utf-8 -*-

from re import search

class Pagseguro:
  """
    Instancia um objeto com os dados do pagseguro

    Não é preciso passar parâmetros, já que você pode configurá-los usando
    os métodos apropriados, mas você pode passar objetos para setup e usuário

    Campos úteis para o construtor:

    - ref_transacao (Referencia da transação feita pela sua loja, para reconhecimento futuro)
    - tipo_frete (EN para PAC ou SD para Sedex)
    - email_cobranca (seu e-mail no pagseguro)

    - tipo (CBR (padrão) para carrinho Pagseguro e CP para carrinho próprio)
    - moeda (O pagseguro aceita apenas pagamento em moeda brasileira (BRL) por enquanto)

    Caso sua loja possua um sistema de cálculo de frete próprio, passe o valor de frete
    e peso no construtor, a partir daqui, ele ignorará as entradas de frete e peso inseridas
    pelo método "item"

    - frete (Se o valor de frete é fixo para todos os produtos. Você pode passar um
             inteiro um float ou uma string que tenha a seguinte sintaxe (regexp)
             "^\d+[,\.]\d{2}$") @todo
    - peso  (Se o valor do frete é calculado por peso, informe o peso total de todos 
             os itens deste carrinho. Informe o peso em gramas de toda a transação, 
             ou seja, para 1Kg use 1000)

    Para frete 
    Exemplo de uso:

    >>> carrinho = Pagseguro()
    >>> carrinho.data['setup']
    {'email_cobranca': '', 'moeda': 'BRL', 'tipo': 'CBR'}
    >>> carrinho2 = Pagseguro(email_cobranca='pagseguro@visie.com.br')
    >>> carrinho2.data['setup']
    {'email_cobranca': 'pagseguro@visie.com.br', 'moeda': 'BRL', 'tipo': 'CBR'}
    >>> carrinho3 = Pagseguro(tipo='CP')
    >>> carrinho3.data['setup']
    {'email_cobranca': '', 'moeda': 'BRL', 'tipo': 'CP'}
  """

  def __init__(self, **kws):
    self.data = {
      'setup' : {},
      'cliente': {},
      'items': [],
    }
    defaultValues = {
      'tipo' : 'CBR',
      'moeda': 'BRL',
      'email_cobranca': '',
    }
    self.setup(**defaultValues)
    self.setup(**kws)

  def _setar(self, area, **kws):
    """
      Seta valores para data, este método é usado por __init__, setup, cliente e item

      Exemplo:

      >>> carrinho = Pagseguro()
      >>> carrinho._setar('area', teste='testando')
      >>> carrinho.data['area']['teste']
      'testando'
    """
    if not area in self.data:
      self.data[area] = {}
    for k,v in kws.iteritems():
      self.data[area][k] = v
    
  def setup(self, **kws):
    """
      Aplica valores ao seu setup. Assim você pode reconfigurar as
      opções do seu carrinho.

      Exemplo:

      >>> carrinho = Pagseguro(email_cobranca='erro@visie.com.br')
      >>> carrinho.setup(email_cobranca='pagseguro@visie.com.br')
      >>> carrinho.data['setup']
      {'email_cobranca': 'pagseguro@visie.com.br', 'moeda': 'BRL', 'tipo': 'CBR'}
    """
    self._setar('setup', **kws)

  def cliente(self, **kws):
    """
      Adiciona valores para as configurações de cliente.

      Nota Importante: a classe irá ignorar o cliente caso o carrinho seja
      definido diferente de CP (ou carrinho próprio). Portanto, caso deseje
      aplicar as opções de cliente para o carrinho pagseguro passe o valor
      CP no construtor do carrinho (ou usando o método setup).

      Exemplos de formas de setar o carrinho:

      >>> carrinho = Pagseguro(tipo='CP')
      >>> carrinho.data['setup']['tipo']
      'CP'
      >>> carrinho2 = Pagseguro()
      >>> carrinho2.setup(tipo='CP')
      >>> carrinho2.data['setup']['tipo']
      'CP'


      Estes são os valores válidos:

      - nome
      - cep
      - end ou endereco
      - num ou numero
      - compl ou complemento
      - bairro
      - cidade
      - uf ou estado
      - pais
      - ddd
      - tel ou telefone
      - email

      Para valores que podem ser definidos por mais de uma forma, a primeira
      sempre sobrescreverá a seunda, logo, se você definir uf e estado, prevalecerá
      uf

      Exemplos de uso:

      >>> carrinho = Pagseguro(tipo='CP') # Para poder usar o método cliente
      >>> carrinho.cliente(nome='Michael', cep='12345678')
      >>> carrinho.data['cliente']
      {'cep': '12345678', 'nome': 'Michael'}

      Veja como funciona a sobrescrita de parâmetros deste método

      >>> carrinho = Pagseguro(tipo='CP')
      >>> carrinho.cliente(endereco='Rua das acácias', end='Rua do Tamarindo')
      >>> carrinho.data['cliente']['end']
      'Rua do Tamarindo'
      >>> 'endereco' in carrinho.data['cliente']
      False
      >>> carrinho.cliente(telefone='12345678')
      >>> 'telefone' in carrinho.data['cliente']
      False
      >>> carrinho.data['cliente']['tel']
      '12345678'
    """
    camposValidos = [ 'nome', 'cep', 'end', 'num', 'compl', 
                      'bairro', 'cidade', 'uf', 'pais',
                      'ddd', 'tel', 'email' ]
    camposSubstituiveis = {
      'endereco': 'end',
      'numero': 'num',
      'complemento': 'compl',
      'estado': 'uf',
      'telefone': 'tel',
    }
    inserir = {}
    for k,v in camposSubstituiveis.iteritems():
      if k in kws:
        inserir[v] = kws[k]

    for i in camposValidos:
      if i in kws:
        inserir[i] = kws[i]

    self._setar('cliente', **inserir)

  def item(self, *args, **kws):
    """
      Adiciona itens ao carrinho de compras
      
      Passe um carrinho ou uma lista de dictionaries contendo os itens dos carrinhos.

      O item deve ter os seguintes campos:

      - id (Número único que identifica o produto em sua loja - não pode se repetir)
      - descr ou desc ou descricao (Descrição e título do produto no carrinho)
      - quant ou quantidade ou qtd ou qty (Quantidade do produto adicionado ao carrinho)
      - valor ou price (Valor do produto em seu carrinho - double ou integer)

      Se o item não possuir qualquer um dos itens listandos acima, ele não é adicionado
      ao carrinho.

      Você ainda pode passar os seguintes valores, opcionais:

      - frete (Valor do frete da mercadoria - double ou integer)
      - peso (em gramas, ou seja 1000 equivale a 1Kg ou 30 equivalendo a 30 gramas)

      As opções que possuem mais de uma forma de adição são sobrescritas pelas opções
      peimárias, ou seja, se você definir qty e qtd qtd irá prevalecer, mas se você
      adicionar um valor para quant prevalecerá quant.

      Para frete, valor ou price você pode passar um inteiro um float ou uma string 
      que tenha a seguinte sintaxe (regexp) "^\d+[,\.]\d{2}$"

      O metodo item retorna o próprio objeto para que seja possivel a concatenada com
      outro método sequencialmente.

      Exemplo:

      >>> carrinho = Pagseguro(email_cobranca='pagseguro@visie.com.br')
      >>> carrinho.item(descr='Produto inválido', quant=3, valor=4) # Produto sem id
      <PagseguroCart - mail:pagseguro@visie.com.br - 0 items>
      >>> carrinho.item(id=1, descr='Um produto de exemplo', quant=5, valor=10)
      <PagseguroCart - mail:pagseguro@visie.com.br - 1 items>
      >>> carrinho.data['items']
      [{'quant': 5, 'id': 1, 'descr': 'Um produto de exemplo', 'valor': 10}]

      Exemplo, adicionando através de um substituto, além disso, verificando a importancia:

      >>> carrinho = Pagseguro(email_cobranca='pagseguro@visie.com.br')
      >>> carrinho.item(id=1, descr='Um produto de exemplo', qty=3, qtd=5, valor=10)
      <PagseguroCart - mail:pagseguro@visie.com.br - 1 items>
      >>> carrinho.data['items']
      [{'quant': 5, 'id': 1, 'descr': 'Um produto de exemplo', 'valor': 10}]

      Exemplo, adicionando campos de string válidos:

      >>> carrinho = Pagseguro(email_cobranca='pagseguro@visie.com.br')
      >>> carrinho.item(id=1, descr='Um produto de exemplo', qty='3', valor='103')
      <PagseguroCart - mail:pagseguro@visie.com.br - 0 items>
      >>> # não foi possivel inserir porque o valor não casa com o regexp proposto
      >>> carrinho.item(id=1, descr='Um produto de exemplo', qty='3', valor='10.30')
      <PagseguroCart - mail:pagseguro@visie.com.br - 1 items>
      >>> carrinho.data['items']
      [{'quant': '3', 'id': 1, 'descr': 'Um produto de exemplo', 'valor': '10.30'}]

      Exemplo inserindo dados opcionais:

      >>> carrinho = Pagseguro(email_cobranca='pagseguro@visie.com.br')
      >>> carrinho.item(id=1, descr='Um produto de exemplo', qty='3', valor='10.30',\
                        peso=13, frete=130)
      <PagseguroCart - mail:pagseguro@visie.com.br - 1 items>
      >>> carrinho.data['items']
      [{'descr': 'Um produto de exemplo', 'peso': 13, 'frete': 130, 'quant': '3', 'valor': '10.30', 'id': 1}]
    """
    camposObrigartorios = {
      'id': {
        'subs': (),
      },
      'descr': {
        'subs': ('desc', 'descricao'),
      },
      'quant': {
        'subs': ('quantidade', 'qtd', 'qty'),
        'type': [str, int],
        'regexp': r'^\d+$',
      },
      'valor': {
        'subs': ('price', ),
        'regexp': r'^\d+[,\.]\d{2}$'
      },
    }
    item = {}
    for k,v in camposObrigartorios.iteritems():
      for i in reversed(v['subs']):
        if i in kws:
          item[k] = kws[i]
      if k in kws:
        item[k] = kws[k]
      if not k in item:
        return self
      if 'type' in v and not type(item[k]) in v['type']:
        return self
      if type(item[k]) == str and 'regexp' in v and not search(v['regexp'], item[k]):
        return self
        
    camposOpcionais = {
      'frete': { 'type': [float, int], },
      'peso': { 'type': [int], }
    }
    for k,v in camposOpcionais.iteritems():
      if k in kws:
        item[k] = kws[k]
        if (
            'type' in v and not type(item[k]) in v['type']
          ) or (
            type(item[k]) == str and 'regexp' in v and not search(v['regexp'], item[k])
          ):
          del item[k]
    self.data['items'].append(item)

    return self

  def mostra(self, **kws):
    """
      Mostra o formulário com os campos gerados para enviar o POST para o pagseguro

      Esta opção recebe parâmetros de configuração:
      - abre      (default: True - usa <form action="https://pagseguro.uol...)
      - fecha     (default: True - usa </form>)
      - imprime   (default: True - imprime o formulário, o formulário será retornado
                     independente desta opção)
      - botao     (default: True - usa o botão de enviar)
      - imgBotao  (default: 1 - Pode ser um dos cinco botões do Pagseguro ou url de imagem)
      - extra     (default: '' string que você pode adicionar ao fim do formulário de
                   abertura, pode ser usado para colocar uma id ou classe desejada)

      Exemplo básico de uso:

      >>> carrinho = Pagseguro(email_cobranca='pagseguro@visie.com.br')
      >>> carrinho.mostra() # irá imprimir e retornar
      <form action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" target="pagseguro" method="post">
      <input type="hidden" name="email_cobranca" value="pagseguro@visie.com.br" />
      <input type="hidden" name="moeda" value="BRL" />
      <input type="hidden" name="tipo" value="CBR" />
      <input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/btnComprarBR.jpg" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!" />
      </form>
      '<form action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" target="pagseguro" method="post">\\n<input type="hidden" name="email_cobranca" value="pagseguro@visie.com.br" />\\n<input type="hidden" name="moeda" value="BRL" />\\n<input type="hidden" name="tipo" value="CBR" />\\n<input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/btnComprarBR.jpg" name="submit" alt="Pague com PagSeguro - \\xc3\\xa9 r\\xc3\\xa1pido, gr\\xc3\\xa1tis e seguro!" />\\n</form>'

      Exemplo, adicionando item ao carrinho (carrinho pagseguro):

      >>> carrinho = Pagseguro(email_cobranca="pagseguro@visie.com.br")
      >>> carrinho.item(id=1, descr='Um produto de exemplo', qty='3', valor='10.30',\
                        peso=13, frete=130)
      <PagseguroCart - mail:pagseguro@visie.com.br - 1 items>
      >>> carrinho.mostra(imprime=False) # pedindo para apenas retornar, assim é possível manipular a resposta
      '<form action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" target="pagseguro" method="post">\\n<input type="hidden" name="email_cobranca" value="pagseguro@visie.com.br" />\\n<input type="hidden" name="moeda" value="BRL" />\\n<input type="hidden" name="tipo" value="CBR" />\\n<input type="hidden" name="item_descr" value="Um produto de exemplo" />\\n<input type="hidden" name="item_peso" value="13" />\\n<input type="hidden" name="item_frete" value="130" />\\n<input type="hidden" name="item_quant" value="3" />\\n<input type="hidden" name="item_valor" value="1030" />\\n<input type="hidden" name="item_id" value="1" />\\n<input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/btnComprarBR.jpg" name="submit" alt="Pague com PagSeguro - \\xc3\\xa9 r\\xc3\\xa1pido, gr\\xc3\\xa1tis e seguro!" />\\n</form>'
      

      Exemplo, adicionando alguns itens ao carrinho (carrinho próprio):

      >>> carrinho = Pagseguro(email_cobranca="pagseguro@visie.com.br", tipo='CP')
      >>> carrinho.item(id=1, descr='Um produto de exemplo', qty='3', valor=10.3,\
                        peso=13, frete=130)
      <PagseguroCart - mail:pagseguro@visie.com.br - 1 items>
      >>> carrinho.item(id=2, descr='Um produto de exemplo 2', qty='13', valor='10.30')
      <PagseguroCart - mail:pagseguro@visie.com.br - 2 items>
      >>> carrinho.mostra(imprime=False) # pedindo para apenas retornar, assim é possível manipular a resposta
      '<form action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" target="pagseguro" method="post">\\n<input type="hidden" name="email_cobranca" value="pagseguro@visie.com.br" />\\n<input type="hidden" name="moeda" value="BRL" />\\n<input type="hidden" name="tipo" value="CP" />\\n<input type="hidden" name="item_descr_1" value="Um produto de exemplo" />\\n<input type="hidden" name="item_peso_1" value="13" />\\n<input type="hidden" name="item_frete_1" value="130" />\\n<input type="hidden" name="item_quant_1" value="3" />\\n<input type="hidden" name="item_valor_1" value="1030" />\\n<input type="hidden" name="item_id_1" value="1" />\\n<input type="hidden" name="item_quant_2" value="13" />\\n<input type="hidden" name="item_id_2" value="2" />\\n<input type="hidden" name="item_descr_2" value="Um produto de exemplo 2" />\\n<input type="hidden" name="item_valor_2" value="1030" />\\n<input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/btnComprarBR.jpg" name="submit" alt="Pague com PagSeguro - \\xc3\\xa9 r\\xc3\\xa1pido, gr\\xc3\\xa1tis e seguro!" />\\n</form>'
      
      Exemplo, usando o sistema de cálculo de frete automático:


      >>> carrinho = Pagseguro(email_cobranca="pagseguro@visie.com.br", tipo='CP',\
                        peso=13, frete=130)
      >>> carrinho.item(id=1, descr='Um produto de exemplo', qty='3', valor=10.3)
      <PagseguroCart - mail:pagseguro@visie.com.br - 1 items>
      >>> carrinho.item(id=2, descr='Um produto de exemplo 2', qty='13', valor='10.30',\
                        peso=123, frete=18) # frete e peso serão ignorados, pois aparecem \
                                            # no construtor
      <PagseguroCart - mail:pagseguro@visie.com.br - 2 items>
      >>> carrinho.mostra(imprime=False) # pedindo para apenas retornar, assim é possível manipular a resposta
      '<form action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" target="pagseguro" method="post">\\n<input type="hidden" name="item_frete_1" value="130" />\\n<input type="hidden" name="email_cobranca" value="pagseguro@visie.com.br" />\\n<input type="hidden" name="moeda" value="BRL" />\\n<input type="hidden" name="tipo" value="CP" />\\n<input type="hidden" name="item_peso_1" value="13" />\\n<input type="hidden" name="item_quant_1" value="3" />\\n<input type="hidden" name="item_id_1" value="1" />\\n<input type="hidden" name="item_descr_1" value="Um produto de exemplo" />\\n<input type="hidden" name="item_valor_1" value="1030" />\\n<input type="hidden" name="item_descr_2" value="Um produto de exemplo 2" />\\n<input type="hidden" name="item_quant_2" value="13" />\\n<input type="hidden" name="item_valor_2" value="1030" />\\n<input type="hidden" name="item_id_2" value="2" />\\n<input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/btnComprarBR.jpg" name="submit" alt="Pague com PagSeguro - \\xc3\\xa9 r\\xc3\\xa1pido, gr\\xc3\\xa1tis e seguro!" />\\n</form>'
    """
    data = {
      'abre'      : True,
      'fecha'     : True,
      'imprime'   : True,
      'botao'     : True,
      'imgBotao'  : 1,
      'extra'     : '',
    }
    data.update(kws)
    ret = []
    if data['abre']:
      ret.append('<form action="https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx" target="pagseguro" method="post"%s>' % ('extra' in data and data['extra']))
    
    input = '<input type="hidden" name="%s" value="%s" />'
    for k,v in self.data['setup'].iteritems():
      if k == 'peso' or k == 'frete':
        k = 'item_%s_1' % k
      ret.append(input % (k, v))
      
    for k,v in self.data['cliente'].iteritems():
      k = 'cliente_%s' % k
      ret.append(input % (k, v))

    item = 0
    for i in self.data['items']:
      item+=1
      for k,v in i.iteritems():
        if (k=='peso' and 'peso' in self.data['setup']):
          continue
        if (k=='frete' and 'frete' in self.data['setup']):
          continue
        if k == 'valor':
          v = ('%.2f' % float(v)).replace('.', '')
        if self.data['setup']['tipo'] == 'CP':
          k = k+'_'+str(item)
        ret.append(input % ('item_'+k, v))

    if data['botao']:
      input = '<input type="image" src="%s" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!" />"'
      if str == data['imgBotao']:
        ret.append(input % data['imgBotao'])
      input = '<input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/%s.jpg" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!" />'
      if data['imgBotao'] == 1:
        ret.append(input % 'btnComprarBR')
      if data['imgBotao'] == 2:
        ret.append(input % 'btnPagarBR')
      if data['imgBotao'] == 3:
        ret.append(input % 'btnPagueComBR')
      if data['imgBotao'] == 4:
        ret.append(input % 'btnComprar')
      if data['imgBotao'] == 5:
        ret.append(input % 'btnPagar')

    if data['fecha']:
      ret.append('</form>')

    ret = '\n'.join(ret)
    if data['imprime']:
      print ret
    return ret

  def __repr__(self):
    return "<PagseguroCart - mail:%s - %s items>" % (
              self.data['setup']['email_cobranca'] ,
              len(self.data['items'])
            )
