<?php

/**
 * FSHL 2.1.0                                  | Fast Syntax HighLighter |
 * -----------------------------------------------------------------------
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

namespace FSHL\Lexer;

use FSHL, FSHL\Generator;

/**
 * Python lexer.
 *
 * @copyright Copyright (c) 2002-2005 Juraj 'hvge' Durech
 * @copyright Copyright (c) 2006 Drekin
 * @copyright Copyright (c) 2011-2012 Jaroslav Hanslík
 * @license http://fshl.kukulich.cz/#license
 */
class Python implements FSHL\Lexer
{
	/**
	 * Returns language name.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return 'Python';
	}

	/**
	 * Returns initial state.
	 *
	 * @return string
	 */
	public function getInitialState()
	{
		return 'OUT';
	}

	/**
	 * Returns states.
	 *
	 * @return array
	 */
	public function getStates()
	{
		return array(
			'OUT' => array(
				array(
					'ALPHA' => array('KEYWORD', Generator::BACK),
					'_' => array('KEYWORD', Generator::BACK),
					'\'\'\'' => array('DOCSTRING1', Generator::NEXT),
					'"""' => array('DOCSTRING2', Generator::NEXT),
					'\'' => array('QUOTE_SINGLE', Generator::NEXT),
					'"' => array('QUOTE_DOUBLE', Generator::NEXT),
					'#' => array('COMMENT_LINE', Generator::NEXT),
					'NUM' => array('NUMBER', Generator::NEXT),
					'DOTNUM' => array('NUMBER', Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_NONE,
				null,
				null
			),
			'KEYWORD' => array(
				array(
					'!ALNUM_' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_KEYWORD | Generator::STATE_FLAG_RECURSION,
				null,
				null
			),
			'DOCSTRING1' => array(
				array(
					'\'\'\'' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'\\\\' => array(Generator::STATE_SELF, Generator::NEXT),
					'\\\'\'\'' => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'py-docstring',
				null
			),
			'DOCSTRING2' => array(
				array(
					'"""' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'\\\\' => array(Generator::STATE_SELF, Generator::NEXT),
					'\\"""' => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'py-docstring',
				null
			),
			'QUOTE_SINGLE' => array(
				array(
					'\'' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'\\\\' => array(Generator::STATE_SELF, Generator::NEXT),
					'\\\'' => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'py-quote',
				null
			),
			'QUOTE_DOUBLE' => array(
				array(
					'"' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'\\\\' => array(Generator::STATE_SELF, Generator::NEXT),
					'\\"' => array(Generator::STATE_SELF, Generator::NEXT),
					'LINE' => array(Generator::STATE_SELF, Generator::NEXT),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'py-quote',
				null
			),
			'COMMENT_LINE' => array(
				array(
					'LINE' => array(Generator::STATE_RETURN, Generator::BACK),
					'TAB' => array(Generator::STATE_SELF, Generator::NEXT)
				),
				Generator::STATE_FLAG_RECURSION,
				'py-comment',
				null
			),
			'NUMBER' => array(
				array(
					'DOTNUM' => array('FRACTION', Generator::NEXT),
					'l' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'L' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'j' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'J' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'e-' => array('EXPONENT', Generator::NEXT),
					'e+' => array('EXPONENT', Generator::NEXT),
					'e' => array('EXPONENT', Generator::NEXT),
					'x' => array('HEXA', Generator::NEXT),
					'X' => array('HEXA', Generator::NEXT),
					'ALL' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'py-num',
				null
			),
			'FRACTION' => array(
				array(
					'j' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'J' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'e-' => array('EXPONENT', Generator::NEXT),
					'e+' => array('EXPONENT', Generator::NEXT),
					'e' => array('EXPONENT', Generator::NEXT),
					'ALL' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'py-num',
				null
			),
			'EXPONENT' => array(
				array(
					'j' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'J' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'!NUM' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_NONE,
				'py-num',
				null
			),
			'HEXA' => array(
				array(
					'L' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'l' => array(Generator::STATE_RETURN, Generator::CURRENT),
					'!HEXNUM' => array(Generator::STATE_RETURN, Generator::BACK)
				),
				Generator::STATE_FLAG_RECURSION,
				'py-num',
				null
			)
		);
	}

	/**
	 * Returns special delimiters.
	 *
	 * @return array
	 */
	public function getDelimiters()
	{
		return array();
	}

	/**
	 * Returns keywords.
	 *
	 * @return array
	 */
	public function getKeywords()
	{
		return array(
			'py-keyword',
			array(
				'and' => 1,
				'as' => 1,
				'assert' => 1,
				'break' => 1,
				'class' => 1,
				'continue' => 1,
				'def' => 1,
				'del' => 1,
				'elif' => 1,
				'else' => 1,
				'except' => 1,
				'exec' => 1,
				'finally' => 1,
				'for' => 1,
				'from' => 1,
				'global' => 1,
				'if' => 1,
				'import' => 1,
				'in' => 1,
				'is' => 1,
				'lambda' => 1,
				'not' => 1,
				'or' => 1,
				'pass' => 1,
				'print' => 1,
				'raise' => 1,
				'return' => 1,
				'try' => 1,
				'while' => 1,
				'with' => 1,
				'yield' => 1,

				'abs' => 2,
				'all' => 2,
				'any' => 2,
				'apply' => 2,
				'basestring' => 2,
				'bool' => 2,
				'buffer' => 2,
				'callable' => 2,
				'chr' => 2,
				'classmethod' => 2,
				'cmp' => 2,
				'coerce' => 2,
				'compile' => 2,
				'complex' => 2,
				'delattr' => 2,
				'dict' => 2,
				'dir' => 2,
				'divmod' => 2,
				'enumerate' => 2,
				'eval' => 2,
				'execfile' => 2,
				'file' => 2,
				'filter' => 2,
				'float' => 2,
				'frozenset' => 2,
				'getattr' => 2,
				'globals' => 2,
				'hasattr' => 2,
				'hash' => 2,
				'hex' => 2,
				'id' => 2,
				'input' => 2,
				'int' => 2,
				'intern' => 2,
				'isinstance' => 2,
				'issubclass' => 2,
				'iter' => 2,
				'len' => 2,
				'list' => 2,
				'locals' => 2,
				'long' => 2,
				'map' => 2,
				'max' => 2,
				'min' => 2,
				'object' => 2,
				'oct' => 2,
				'open' => 2,
				'ord' => 2,
				'pow' => 2,
				'property' => 2,
				'range' => 2,
				'raw_input' => 2,
				'reduce' => 2,
				'reload' => 2,
				'repr' => 2,
				'reversed' => 2,
				'round' => 2,
				'set' => 2,
				'setattr' => 2,
				'slice' => 2,
				'sorted' => 2,
				'staticmethod' => 2,
				'str' => 2,
				'sum' => 2,
				'super' => 2,
				'tuple' => 2,
				'type' => 2,
				'unichr' => 2,
				'unicode' => 2,
				'vars' => 2,
				'xrange' => 2,
				'zip' => 2,

				'ArithmeticError' => 3,
				'AssertionError' => 3,
				'AttributeError' => 3,
				'BaseException' => 3,
				'DeprecationWarning' => 3,
				'EOFError' => 3,
				'Ellipsis' => 3,
				'EnvironmentError' => 3,
				'Exception' => 3,
				'FloatingPointError' => 3,
				'FutureWarning' => 3,
				'GeneratorExit' => 3,
				'IOError' => 3,
				'ImportError' => 3,
				'ImportWarning' => 3,
				'IndentationError' => 3,
				'IndexError' => 3,
				'KeyError' => 3,
				'KeyboardInterrupt' => 3,
				'LookupError' => 3,
				'MemoryError' => 3,
				'NameError' => 3,
				'NotImplemented' => 3,
				'NotImplementedError' => 3,
				'OSError' => 3,
				'OverflowError' => 3,
				'OverflowWarning' => 3,
				'PendingDeprecationWarning' => 3,
				'ReferenceError' => 3,
				'RuntimeError' => 3,
				'RuntimeWarning' => 3,
				'StandardError' => 3,
				'StopIteration' => 3,
				'SyntaxError' => 3,
				'SyntaxWarning' => 3,
				'SystemError' => 3,
				'SystemExit' => 3,
				'TabError' => 3,
				'TypeError' => 3,
				'UnboundLocalError' => 3,
				'UnicodeDecodeError' => 3,
				'UnicodeEncodeError' => 3,
				'UnicodeError' => 3,
				'UnicodeTranslateError' => 3,
				'UnicodeWarning' => 3,
				'UserWarning' => 3,
				'ValueError' => 3,
				'Warning' => 3,
				'WindowsError' => 3,
				'ZeroDivisionError' => 3,

				'BufferType' => 3,
				'BuiltinFunctionType' => 3,
				'BuiltinMethodType' => 3,
				'ClassType' => 3,
				'CodeType' => 3,
				'ComplexType' => 3,
				'DictProxyType' => 3,
				'DictType' => 3,
				'DictionaryType' => 3,
				'EllipsisType' => 3,
				'FileType' => 3,
				'FloatType' => 3,
				'FrameType' => 3,
				'FunctionType' => 3,
				'GeneratorType' => 3,
				'InstanceType' => 3,
				'IntType' => 3,
				'LambdaType' => 3,
				'ListType' => 3,
				'LongType' => 3,
				'MethodType' => 3,
				'ModuleType' => 3,
				'NoneType' => 3,
				'ObjectType' => 3,
				'SliceType' => 3,
				'StringType' => 3,
				'StringTypes' => 3,
				'TracebackType' => 3,
				'TupleType' => 3,
				'TypeType' => 3,
				'UnboundMethodType' => 3,
				'UnicodeType' => 3,
				'XRangeType' => 3,

				'False' => 3,
				'None' => 3,
				'True' => 3,

				'__abs__' => 3,
				'__add__' => 3,
				'__all__' => 3,
				'__author__' => 3,
				'__bases__' => 3,
				'__builtins__' => 3,
				'__call__' => 3,
				'__class__' => 3,
				'__cmp__' => 3,
				'__coerce__' => 3,
				'__contains__' => 3,
				'__debug__' => 3,
				'__del__' => 3,
				'__delattr__' => 3,
				'__delitem__' => 3,
				'__delslice__' => 3,
				'__dict__' => 3,
				'__div__' => 3,
				'__divmod__' => 3,
				'__doc__' => 3,
				'__eq__' => 3,
				'__file__' => 3,
				'__float__' => 3,
				'__floordiv__' => 3,
				'__future__' => 3,
				'__ge__' => 3,
				'__getattr__' => 3,
				'__getattribute__' => 3,
				'__getitem__' => 3,
				'__getslice__' => 3,
				'__gt__' => 3,
				'__hash__' => 3,
				'__hex__' => 3,
				'__iadd__' => 3,
				'__import__' => 3,
				'__imul__' => 3,
				'__init__' => 3,
				'__int__' => 3,
				'__invert__' => 3,
				'__iter__' => 3,
				'__le__' => 3,
				'__len__' => 3,
				'__long__' => 3,
				'__lshift__' => 3,
				'__lt__' => 3,
				'__members__' => 3,
				'__metaclass__' => 3,
				'__mod__' => 3,
				'__mro__' => 3,
				'__mul__' => 3,
				'__name__' => 3,
				'__ne__' => 3,
				'__neg__' => 3,
				'__new__' => 3,
				'__nonzero__' => 3,
				'__oct__' => 3,
				'__or__' => 3,
				'__path__' => 3,
				'__pos__' => 3,
				'__pow__' => 3,
				'__radd__' => 3,
				'__rdiv__' => 3,
				'__rdivmod__' => 3,
				'__reduce__' => 3,
				'__repr__' => 3,
				'__rfloordiv__' => 3,
				'__rlshift__' => 3,
				'__rmod__' => 3,
				'__rmul__' => 3,
				'__ror__' => 3,
				'__rpow__' => 3,
				'__rrshift__' => 3,
				'__rsub__' => 3,
				'__rtruediv__' => 3,
				'__rxor__' => 3,
				'__setattr__' => 3,
				'__setitem__' => 3,
				'__setslice__' => 3,
				'__self__' => 3,
				'__slots__' => 3,
				'__str__' => 3,
				'__sub__' => 3,
				'__truediv__' => 3,
				'__version__' => 3,
				'__xor__' => 3
			),
			Generator::CASE_SENSITIVE
		);
	}
}
