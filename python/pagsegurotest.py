# -*- encoding: utf-8 -*-
import unittest,doctest
import pagseguro

class pagseguroTest(unittest.TestCase):
  '''Testes para pagseguro'''
  def testpagseguro(self):
    pass

class DocTest(unittest.TestCase):
  '''Roda o doctest de pagseguro'''
  def testdoc(self):
    '''doctest de pagseguro'''
    t = doctest.testmod(pagseguro)
    self.assertEqual(t[0],0)
    if not t[0] == 0:
      print doctest.__doc__

if __name__ == '__main__':
  unittest.main()

