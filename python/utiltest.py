# -*- encoding: utf-8 -*-
import unittest,doctest
import util

class utilTest(unittest.TestCase):
  '''Testes para util'''
  def testutil(self):
    '''util'''
    pass

class DocTest(unittest.TestCase):
  '''Roda o doctest de util'''
  def testdoc(self):
    '''doctest de util'''
    t = doctest.testmod(util)
    self.assertEqual(t[0],0)
    if not t[0] == 0:
      print doctest.__doc__

if __name__ == '__main__':
  unittest.main()

