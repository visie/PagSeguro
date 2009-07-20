# -*- encoding: utf-8 -*-
from re import compile

def telefone(tel):
  """
    Trata telefones, retornando o ddd e o telefone necessário

    Exemplo:

    >>> telefone ('(11) 1234-5678')
    ('11', '12345678')
    >>> telefone ('11 1234.5678')
    ('11', '12345678')
    >>> telefone ('1187654321')
    ('11', '87654321')
    >>> telefone ('12345678')
    ('', '12345678')
  """
  c = compile (r'\D')
  tel = c.sub('', tel)
  if len(tel) <= 8:
    return '', tel
  return tel[:2], tel[2:]

