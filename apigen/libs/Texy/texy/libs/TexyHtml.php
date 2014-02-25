<?php

/**
 * Texy! - human-readable text to HTML converter.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE version 2 or 3
 * @link       http://texy.info
 * @package    Texy
 */



/**
 * HTML helper.
 *
 * usage:
 *       $anchor = TexyHtml::el('a')->href($link)->setText('Texy');
 *       $el->class = 'myclass';
 *
 *       echo $el->startTag(), $el->endTag();
 *
 * @property   mixed element's attributes
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Texy
 */
class TexyHtml extends TexyObject implements ArrayAccess, /* Countable, */ IteratorAggregate
{
	/** @var string  element's name */
	private $name;

	/** @var bool  is element empty? */
	private $isEmpty;

	/** @var array  element's attributes */
	public $attrs = array();

	/** @var array  of TexyHtml | string nodes */
	protected $children = array();

	/** @var bool  use XHTML syntax? */
	public static $xhtml = TRUE;

	/** @var array  empty elements */
	public static $emptyElements = array('img'=>1,'hr'=>1,'br'=>1,'input'=>1,'meta'=>1,'area'=>1,
		'base'=>1,'col'=>1,'link'=>1,'param'=>1,'basefont'=>1,'frame'=>1,'isindex'=>1,'wbr'=>1,'embed'=>1);

	/** @var array  %inline; elements; replaced elements + br have value '1' */
	public static $inlineElements = array('ins'=>0,'del'=>0,'tt'=>0,'i'=>0,'b'=>0,'big'=>0,'small'=>0,'em'=>0,
		'strong'=>0,'dfn'=>0,'code'=>0,'samp'=>0,'kbd'=>0,'var'=>0,'cite'=>0,'abbr'=>0,'acronym'=>0,
		'sub'=>0,'sup'=>0,'q'=>0,'span'=>0,'bdo'=>0,'a'=>0,'object'=>1,'img'=>1,'br'=>1,'script'=>1,
		'map'=>0,'input'=>1,'select'=>1,'textarea'=>1,'label'=>0,'button'=>1,
		'u'=>0,'s'=>0,'strike'=>0,'font'=>0,'applet'=>1,'basefont'=>0, // transitional
		'embed'=>1,'wbr'=>0,'nobr'=>0,'canvas'=>1, // proprietary
	);

	/** @var array  elements with optional end tag in HTML */
	public static $optionalEnds = array('body'=>1,'head'=>1,'html'=>1,'colgroup'=>1,'dd'=>1,
		'dt'=>1,'li'=>1,'option'=>1,'p'=>1,'tbody'=>1,'td'=>1,'tfoot'=>1,'th'=>1,'thead'=>1,'tr'=>1);

	/** @see http://www.w3.org/TR/xhtml1/prohibitions.html */
	public static $prohibits = array(
		'a' => array('a','button'),
		'img' => array('pre'),
		'object' => array('pre'),
		'big' => array('pre'),
		'small' => array('pre'),
		'sub' => array('pre'),
		'sup' => array('pre'),
		'input' => array('button'),
		'select' => array('button'),
		'textarea' => array('button'),
		'label' => array('button', 'label'),
		'button' => array('button'),
		'form' => array('button', 'form'),
		'fieldset' => array('button'),
		'iframe' => array('button'),
		'isindex' => array('button'),
	);



	/**
	 * Static factory.
	 * @param  string element name (or NULL)
	 * @param  array|string element's attributes (or textual content)
	 * @return TexyHtml
	 */
	public static function el($name = NULL, $attrs = NULL)
	{
		$el = new self;
		$el->setName($name);
		if (is_array($attrs)) {
			$el->attrs = $attrs;
		} elseif ($attrs !== NULL) {
			$el->setText($attrs);
		}
		return $el;
	}



	/**
	 * Changes element's name.
	 * @param  string
	 * @param  bool  Is element empty?
	 * @return TexyHtml  provides a fluent interface
	 * @throws InvalidArgumentException
	 */
	final public function setName($name, $empty = NULL)
	{
		if ($name !== NULL && !is_string($name)) {
			throw new InvalidArgumentException("Name must be string or NULL.");
		}

		$this->name = $name;
		$this->isEmpty = $empty === NULL ? isset(self::$emptyElements[$name]) : (bool) $empty;
		return $this;
	}



	/**
	 * Returns element's name.
	 * @return string
	 */
	final public function getName()
	{
		return $this->name;
	}



	/**
	 * Is element empty?
	 * @return bool
	 */
	final public function isEmpty()
	{
		return $this->isEmpty;
	}



	/**
	 * Overloaded setter for element's attribute.
	 * @param  string    property name
	 * @param  mixed     property value
	 * @return void
	 */
	final public function __set($name, $value)
	{
		$this->attrs[$name] = $value;
	}



	/**
	 * Overloaded getter for element's attribute.
	 * @param  string    property name
	 * @return mixed    property value
	 */
	final public function &__get($name)
	{
		return $this->attrs[$name];
	}



	/**
	 * Overloaded setter for element's attribute.
	 * @param  string attribute name
	 * @param  array value
	 * @return TexyHtml  provides a fluent interface
	 */
/*
	final public function __call($m, $args)
	{
		if (count($args) !== 1) {
			throw new InvalidArgumentException("Just one argument is required.");
		}
		$this->attrs[$m] = $args[0];
		return $this;
	}
*/


	/**
	 * Special setter for element's attribute.
	 * @param  string path
	 * @param  array query
	 * @return TexyHtml  provides a fluent interface
	 */
	final public function href($path, $query = NULL)
	{
		if ($query) {
			$query = http_build_query($query, NULL, '&');
			if ($query !== '') $path .= '?' . $query;
		}
		$this->attrs['href'] = $path;
		return $this;
	}



	/**
	 * Sets element's textual content.
	 * @param  string
	 * @return TexyHtml  provides a fluent interface
	 */
	final public function setText($text)
	{
		if (is_scalar($text)) {
			$this->removeChildren();
			$this->children = array($text);
		} elseif ($text !== NULL) {
			throw new InvalidArgumentException('Content must be scalar.');
		}
		return $this;
	}



	/**
	 * Gets element's textual content.
	 * @return string
	 */
	final public function getText()
	{
		$s = '';
		foreach ($this->children as $child) {
			if (is_object($child)) return FALSE;
			$s .= $child;
		}
		return $s;
	}



	/**
	 * Adds new element's child.
	 * @param  TexyHtml|string child node
	 * @return TexyHtml  provides a fluent interface
	 */
	final public function add($child)
	{
		return $this->insert(NULL, $child);
	}



	/**
	 * Creates and adds a new TexyHtml child.
	 * @param  string  elements's name
	 * @param  array|string element's attributes (or textual content)
	 * @return TexyHtml  created element
	 */
	final public function create($name, $attrs = NULL)
	{
		$this->insert(NULL, $child = self::el($name, $attrs));
		return $child;
	}



	/**
	 * Inserts child node.
	 * @param  int
	 * @param  TexyHtml node
	 * @param  bool
	 * @return TexyHtml  provides a fluent interface
	 * @throws Exception
	 */
	public function insert($index, $child, $replace = FALSE)
	{
		if ($child instanceof TexyHtml || is_string($child)) {
			if ($index === NULL)  { // append
				$this->children[] = $child;

			} else { // insert or replace
				array_splice($this->children, (int) $index, $replace ? 1 : 0, array($child));
			}

		} else {
			throw new /*::*/InvalidArgumentException('Child node must be scalar or TexyHtml object.');
		}

		return $this;
	}



	/**
	 * Inserts (replaces) child node (ArrayAccess implementation).
	 * @param  int
	 * @param  TexyHtml node
	 * @return void
	 */
	final public function offsetSet($index, $child)
	{
		$this->insert($index, $child, TRUE);
	}



	/**
	 * Returns child node (ArrayAccess implementation).
	 * @param  int index
	 * @return mixed
	 */
	final public function offsetGet($index)
	{
		return $this->children[$index];
	}



	/**
	 * Exists child node? (ArrayAccess implementation).
	 * @param  int index
	 * @return bool
	 */
	final public function offsetExists($index)
	{
		return isset($this->children[$index]);
	}



	/**
	 * Removes child node (ArrayAccess implementation).
	 * @param  int index
	 * @return void
	 */
	public function offsetUnset($index)
	{
		if (isset($this->children[$index])) {
			array_splice($this->children, (int) $index, 1);
		}
	}



	/**
	 * Required by the Countable interface.
	 * @return int
	 */
	final public function count()
	{
		return count($this->children);
	}



	/**
	 * Removed all children.
	 * @return void
	 */
	public function removeChildren()
	{
		$this->children = array();
	}



	/**
	 * Required by the IteratorAggregate interface.
	 * @return ArrayIterator
	 */
	final public function getIterator()
	{
		return new ArrayIterator($this->children);
	}



	/**
	 * Returns all of children.
	 * return array
	 */
	final public function getChildren()
	{
		return $this->children;
	}



	/**
	 * Renders element's start tag, content and end tag to internal string representation.
	 * @param  Texy
	 * @return string
	 */
	final public function toString(Texy $texy)
	{
		$ct = $this->getContentType();
		$s = $texy->protect($this->startTag(), $ct);

		// empty elements are finished now
		if ($this->isEmpty) {
			return $s;
		}

		// add content
		foreach ($this->children as $child) {
			if (is_object($child)) {
				$s .= $child->toString($texy);
			} else {
				$s .= $child;
			}
		}

		// add end tag
		return $s . $texy->protect($this->endTag(), $ct);
	}



	/**
	 * Renders to final HTML.
	 * @param  Texy
	 * @return string
	 */
	final public function toHtml(Texy $texy)
	{
		return $texy->stringToHtml($this->toString($texy));
	}



	/**
	 * Renders to final text.
	 * @param  Texy
	 * @return string
	 */
	final public function toText(Texy $texy)
	{
		return $texy->stringToText($this->toString($texy));
	}



	/**
	 * Returns element's start tag.
	 * @return string
	 */
	public function startTag()
	{
		if (!$this->name) {
			return '';
		}

		$s = '<' . $this->name;

		if (is_array($this->attrs)) {
			foreach ($this->attrs as $key => $value)
			{
				// skip NULLs and false boolean attributes
				if ($value === NULL || $value === FALSE) continue;

				// true boolean attribute
				if ($value === TRUE) {
					// in XHTML must use unminimized form
					if (self::$xhtml) $s .= ' ' . $key . '="' . $key . '"';
					// in HTML should use minimized form
					else $s .= ' ' . $key;
					continue;

				} elseif (is_array($value)) {

					// prepare into temporary array
					$tmp = NULL;
					foreach ($value as $k => $v) {
						// skip NULLs & empty string; composite 'style' vs. 'others'
						if ($v == NULL) continue;

						if (is_string($k)) $tmp[] = $k . ':' . $v;
						else $tmp[] = $v;
					}

					if (!$tmp) continue;
					$value = implode($key === 'style' ? ';' : ' ', $tmp);

				} else {
					$value = (string) $value;
				}

				// add new attribute
				$value = str_replace(array('&', '"', '<', '>', '@'), array('&amp;', '&quot;', '&lt;', '&gt;', '&#64;'), $value);
				$s .= ' ' . $key . '="' . Texy::freezeSpaces($value) . '"';
			}
		}

		// finish start tag
		if (self::$xhtml && $this->isEmpty) {
			return $s . ' />';
		}
		return $s . '>';
	}



	/**
	 * Returns element's end tag.
	 * @return string
	 */
	public function endTag()
	{
		if ($this->name && !$this->isEmpty) {
			return '</' . $this->name . '>';
		}
		return '';
	}



	/**
	 * Clones all children too.
	 */
	public function __clone()
	{
		foreach ($this->children as $key => $value) {
			if (is_object($value)) {
				$this->children[$key] = clone $value;
			}
		}
	}



	/**
	 * @return int
	 */
	final public function getContentType()
	{
		if (!isset(self::$inlineElements[$this->name])) return Texy::CONTENT_BLOCK;

		return self::$inlineElements[$this->name] ? Texy::CONTENT_REPLACED : Texy::CONTENT_MARKUP;
	}



	/**
	 * @param  array
	 * @return void
	 */
	final public function validateAttrs($dtd)
	{
		if (isset($dtd[$this->name])) {
			$allowed = $dtd[$this->name][0];
			if (is_array($allowed)) {
				foreach ($this->attrs as $attr => $foo) {
					if (!isset($allowed[$attr])) unset($this->attrs[$attr]);
				}
			}
		}
	}



	public function validateChild($child, $dtd)
	{
		if (isset($dtd[$this->name])) {
			if ($child instanceof TexyHtml) $child = $child->name;
			return isset($dtd[$this->name][1][$child]);
		} else {
			return TRUE; // unknown element
		}
	}



	/**
	 * Parses text as single line.
	 * @param  Texy
	 * @param  string
	 * @return void
	 */
	final public function parseLine(Texy $texy, $s)
	{
		// TODO!
		// special escape sequences
		$s = str_replace(array('\)', '\*'), array('&#x29;', '&#x2A;'), $s);

		$parser = new TexyLineParser($texy, $this);
		$parser->parse($s);
		return $parser;
	}



	/**
	 * Parses text as block.
	 * @param  Texy
	 * @param  string
	 * @param  bool
	 * @return void
	 */
	final public function parseBlock(Texy $texy, $s, $indented = FALSE)
	{
		$parser = new TexyBlockParser($texy, $this, $indented);
		$parser->parse($s);
	}

}
