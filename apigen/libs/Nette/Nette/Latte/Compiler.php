<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Latte;

use Nette,
	Nette\Utils\Strings;



/**
 * Latte compiler.
 *
 * @author     David Grudl
 */
class Compiler extends Nette\Object
{
	/** @var string default content type */
	public $defaultContentType = self::CONTENT_XHTML;

	/** @var Token[] */
	private $tokens;

	/** @var string pointer to current node content */
	private $output;

	/** @var int  position on source template */
	private $position;

	/** @var array of [name => IMacro[]] */
	private $macros;

	/** @var \SplObjectStorage */
	private $macroHandlers;

	/** @var HtmlNode[] */
	private $htmlNodes = array();

	/** @var MacroNode[] */
	private $macroNodes = array();

	/** @var string[] */
	private $attrCodes = array();

	/** @var string */
	private $contentType;

	/** @var array [context, subcontext] */
	private $context;

	/** @var string */
	private $templateId;

	/** Context-aware escaping content types */
	const CONTENT_HTML = 'html',
		CONTENT_XHTML = 'xhtml',
		CONTENT_XML = 'xml',
		CONTENT_JS = 'js',
		CONTENT_CSS = 'css',
		CONTENT_ICAL = 'ical',
		CONTENT_TEXT = 'text';

	/** @internal Context-aware escaping HTML contexts */
	const CONTEXT_COMMENT = 'comment',
		CONTEXT_SINGLE_QUOTED = "'",
		CONTEXT_DOUBLE_QUOTED = '"';


	public function __construct()
	{
		$this->macroHandlers = new \SplObjectStorage;
	}



	/**
	 * Adds new macro.
	 * @param  string
	 * @return Compiler  provides a fluent interface
	 */
	public function addMacro($name, IMacro $macro)
	{
		$this->macros[$name][] = $macro;
		$this->macroHandlers->attach($macro);
		return $this;
	}



	/**
	 * Compiles tokens to PHP code.
	 * @param  Token[]
	 * @return string
	 */
	public function compile(array $tokens)
	{
		$this->templateId = Strings::random();
		$this->tokens = $tokens;
		$output = '';
		$this->output = & $output;
		$this->htmlNodes = $this->macroNodes = array();
		$this->setContentType($this->defaultContentType);

		foreach ($this->macroHandlers as $handler) {
			$handler->initialize($this);
		}

		try {
			foreach ($tokens as $this->position => $token) {
				if ($token->type === Token::TEXT) {
					$this->output .= $token->text;

				} elseif ($token->type === Token::MACRO_TAG) {
					$isRightmost = !isset($tokens[$this->position + 1])
						|| substr($tokens[$this->position + 1]->text, 0, 1) === "\n";
					$this->writeMacro($token->name, $token->value, $token->modifiers, $isRightmost);

				} elseif ($token->type === Token::HTML_TAG_BEGIN) {
					$this->processHtmlTagBegin($token);

				} elseif ($token->type === Token::HTML_TAG_END) {
					$this->processHtmlTagEnd($token);

				} elseif ($token->type === Token::HTML_ATTRIBUTE) {
					$this->processHtmlAttribute($token);

				} elseif ($token->type === Token::COMMENT) {
					$this->processComment($token);
				}
			}
		} catch (CompileException $e) {
			$e->sourceLine = $token->line;
			throw $e;
		}


		foreach ($this->htmlNodes as $htmlNode) {
			if (!empty($htmlNode->macroAttrs)) {
				throw new CompileException("Missing end tag </$htmlNode->name> for macro-attribute " . Parser::N_PREFIX
					. implode(' and ' . Parser::N_PREFIX, array_keys($htmlNode->macroAttrs)) . ".", 0, $token->line);
			}
		}

		$prologs = $epilogs = '';
		foreach ($this->macroHandlers as $handler) {
			$res = $handler->finalize();
			$handlerName = get_class($handler);
			$prologs .= empty($res[0]) ? '' : "<?php\n// prolog $handlerName\n$res[0]\n?>";
			$epilogs = (empty($res[1]) ? '' : "<?php\n// epilog $handlerName\n$res[1]\n?>") . $epilogs;
		}
		$output = ($prologs ? $prologs . "<?php\n//\n// main template\n//\n?>\n" : '') . $output . $epilogs;

		if ($this->macroNodes) {
			throw new CompileException("There are unclosed macros.", 0, $token->line);
		}

		$output = $this->expandTokens($output);
		return $output;
	}



	/**
	 * @return Compiler  provides a fluent interface
	 */
	public function setContentType($type)
	{
		$this->contentType = $type;
		$this->context = NULL;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}



	/**
	 * @return Compiler  provides a fluent interface
	 */
	public function setContext($context, $sub = NULL)
	{
		$this->context = array($context, $sub);
		return $this;
	}



	/**
	 * @return array [context, subcontext]
	 */
	public function getContext()
	{
		return $this->context;
	}



	/**
	 * @return string
	 */
	public function getTemplateId()
	{
		return $this->templateId;
	}



	/**
	 * Returns current line number.
	 * @return int
	 */
	public function getLine()
	{
		return $this->tokens ? $this->tokens[$this->position]->line : NULL;
	}



	public function expandTokens($s)
	{
		return strtr($s, $this->attrCodes);
	}



	private function processHtmlTagBegin(Token $token)
	{
		if ($token->closing) {
			do {
				$htmlNode = array_pop($this->htmlNodes);
				if (!$htmlNode) {
					$htmlNode = new HtmlNode($token->name);
				}
				if (strcasecmp($htmlNode->name, $token->name) === 0) {
					break;
				}
				if ($htmlNode->macroAttrs) {
					throw new CompileException("Unexpected </$token->name>.", 0, $token->line);
				}
			} while (TRUE);
			$this->htmlNodes[] = $htmlNode;
			$htmlNode->closing = TRUE;
			$htmlNode->offset = strlen($this->output);
			$this->setContext(NULL);

		} elseif ($token->text === '<!--') {
			$this->setContext(self::CONTEXT_COMMENT);

		} else {
			$this->htmlNodes[] = $htmlNode = new HtmlNode($token->name);
			$htmlNode->isEmpty = in_array($this->contentType, array(self::CONTENT_HTML, self::CONTENT_XHTML))
				&& isset(Nette\Utils\Html::$emptyElements[strtolower($token->name)]);
			$htmlNode->offset = strlen($this->output);
			$this->setContext(NULL);
		}
		$this->output .= $token->text;
	}



	private function processHtmlTagEnd(Token $token)
	{
		if ($token->text === '-->') {
			$this->output .= $token->text;
			$this->setContext(NULL);
			return;
		}

		$htmlNode = end($this->htmlNodes);
		$isEmpty = !$htmlNode->closing && (Strings::contains($token->text, '/') || $htmlNode->isEmpty);

		if ($isEmpty && in_array($this->contentType, array(self::CONTENT_HTML, self::CONTENT_XHTML))) { // auto-correct
			$token->text = preg_replace('#^.*>#', $this->contentType === self::CONTENT_XHTML ? ' />' : '>', $token->text);
		}

		if (empty($htmlNode->macroAttrs)) {
			$this->output .= $token->text;
		} else {
			$code = substr($this->output, $htmlNode->offset) . $token->text;
			$this->output = substr($this->output, 0, $htmlNode->offset);
			$this->writeAttrsMacro($code, $htmlNode);
			if ($isEmpty) {
				$htmlNode->closing = TRUE;
				$this->writeAttrsMacro('', $htmlNode);
			}
		}

		if ($isEmpty) {
			$htmlNode->closing = TRUE;
		}

		if (!$htmlNode->closing && (strcasecmp($htmlNode->name, 'script') === 0 || strcasecmp($htmlNode->name, 'style') === 0)) {
			$this->setContext(strcasecmp($htmlNode->name, 'style') ? self::CONTENT_JS : self::CONTENT_CSS);
		} else {
			$this->setContext(NULL);
			if ($htmlNode->closing) {
				array_pop($this->htmlNodes);
			}
		}
	}



	private function processHtmlAttribute(Token $token)
	{
		$htmlNode = end($this->htmlNodes);
		if (Strings::startsWith($token->name, Parser::N_PREFIX)) {
			$name = substr($token->name, strlen(Parser::N_PREFIX));
			if (isset($htmlNode->macroAttrs[$name])) {
				throw new CompileException("Found multiple macro-attributes $token->name.", 0, $token->line);
			}
			$htmlNode->macroAttrs[$name] = $token->value;
			
		} else {
			$htmlNode->attrs[$token->name] = TRUE;
			$this->output .= $token->text;
			if ($token->value) { // quoted
				$context = NULL;
				if (strncasecmp($token->name, 'on', 2) === 0) {
					$context = self::CONTENT_JS;
				} elseif ($token->name === 'style') {
					$context = self::CONTENT_CSS;
				}
				$this->setContext($token->value, $context);
			}
		}
	}



	private function processComment(Token $token)
	{
		$isLeftmost = trim(substr($this->output, strrpos("\n$this->output", "\n"))) === '';
		if (!$isLeftmost) {
			$this->output .= substr($token->text, strlen(rtrim($token->text, "\n")));
		}
	}



	/********************* macros ****************d*g**/



	/**
	 * Generates code for {macro ...} to the output.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @param  bool
	 * @return MacroNode
	 */
	public function writeMacro($name, $args = NULL, $modifiers = NULL, $isRightmost = FALSE, HtmlNode $htmlNode = NULL, $prefix = NULL)
	{
		if ($name[0] === '/') { // closing
			$node = end($this->macroNodes);

			if (!$node || ("/$node->name" !== $name && '/' !== $name) || $modifiers
				|| ($args && $node->args && !Strings::startsWith("$node->args ", "$args "))
			) {
				$name .= $args ? ' ' : '';
				throw new CompileException("Unexpected macro {{$name}{$args}{$modifiers}}"
					. ($node ? ", expecting {/$node->name}" . ($args && $node->args ? " or eventually {/$node->name $node->args}" : '') : ''));
			}

			array_pop($this->macroNodes);
			if (!$node->args) {
				$node->setArgs($args);
			}

			$isLeftmost = $node->content ? trim(substr($this->output, strrpos("\n$this->output", "\n"))) === '' : FALSE;

			$node->closing = TRUE;
			$node->macro->nodeClosed($node);

			$this->output = & $node->saved[0];
			$this->writeCode($node->openingCode, $this->output, $node->saved[1]);
			$this->writeCode($node->closingCode, $node->content, $isRightmost, $isLeftmost);
			$this->output .= $node->content;

		} else { // opening
			$node = $this->expandMacro($name, $args, $modifiers, $htmlNode, $prefix);
			if ($node->isEmpty) {
				$this->writeCode($node->openingCode, $this->output, $isRightmost);

			} else {
				$this->macroNodes[] = $node;
				$node->saved = array(& $this->output, $isRightmost);
				$this->output = & $node->content;
			}
		}
		return $node;
	}



	private function writeCode($code, & $output, $isRightmost, $isLeftmost = NULL)
	{
		if ($isRightmost) {
			$leftOfs = strrpos("\n$output", "\n");
			$isLeftmost = $isLeftmost === NULL ? trim(substr($output, $leftOfs)) === '' : $isLeftmost;
			if ($isLeftmost && substr($code, 0, 11) !== '<?php echo ') {
				$output = substr($output, 0, $leftOfs); // alone macro without output -> remove indentation
			} elseif (substr($code, -2) === '?>') {
				$code .= "\n"; // double newline to avoid newline eating by PHP
			}
		}
		$output .= $code;
	}



	/**
	 * Generates code for macro <tag n:attr> to the output.
	 * @param  string
	 * @return void
	 */
	public function writeAttrsMacro($code, HtmlNode $htmlNode)
	{
		$attrs = $htmlNode->macroAttrs;
		$left = $right = array();
		$attrCode = '';

		foreach ($this->macros as $name => $foo) {
			$attrName = MacroNode::PREFIX_INNER . "-$name";
			if (isset($attrs[$attrName])) {
				if ($htmlNode->closing) {
					$left[] = array("/$name", '', MacroNode::PREFIX_INNER);
				} else {
					array_unshift($right, array($name, $attrs[$attrName], MacroNode::PREFIX_INNER));
				}
				unset($attrs[$attrName]);
			}
		}

		foreach (array_reverse($this->macros) as $name => $foo) {
			$attrName = MacroNode::PREFIX_TAG . "-$name";
			if (isset($attrs[$attrName])) {
				$left[] = array($name, $attrs[$attrName], MacroNode::PREFIX_TAG);
				array_unshift($right, array("/$name", '', MacroNode::PREFIX_TAG));
				unset($attrs[$attrName]);
			}
		}

		foreach ($this->macros as $name => $foo) {
			if (isset($attrs[$name])) {
				if ($htmlNode->closing) {
					$right[] = array("/$name", '', NULL);
				} else {
					array_unshift($left, array($name, $attrs[$name], NULL));
				}
				unset($attrs[$name]);
			}
		}

		if ($attrs) {
			throw new CompileException("Unknown macro-attribute " . Parser::N_PREFIX
				. implode(' and ' . Parser::N_PREFIX, array_keys($attrs)));
		}

		if (!$htmlNode->closing) {
			$htmlNode->attrCode = & $this->attrCodes[$uniq = ' n:' . Nette\Utils\Strings::random()];
			$code = substr_replace($code, $uniq, strrpos($code, '/>') ?: strrpos($code, '>'), 0);
		}

		foreach ($left as $item) {
			$node = $this->writeMacro($item[0], $item[1], NULL, NULL, $htmlNode, $item[2]);
			if ($node->closing || $node->isEmpty) {
				$htmlNode->attrCode .= $node->attrCode;
				if ($node->isEmpty) {
					unset($htmlNode->macroAttrs[$node->name]);
				}
			}
		}

		$this->output .= $code;

		foreach ($right as $item) {
			$node = $this->writeMacro($item[0], $item[1], NULL, NULL, $htmlNode);
			if ($node->closing) {
				$htmlNode->attrCode .= $node->attrCode;
			}
		}

		if ($right && substr($this->output, -2) === '?>') {
			$this->output .= "\n";
		}
	}



	/**
	 * Expands macro and returns node & code.
	 * @param  string
	 * @param  string
	 * @param  string
	 * @return MacroNode
	 */
	public function expandMacro($name, $args, $modifiers = NULL, HtmlNode $htmlNode = NULL, $prefix = NULL)
	{
		if (empty($this->macros[$name])) {
			$cdata = $this->htmlNodes && in_array(strtolower(end($this->htmlNodes)->name), array('script', 'style'));
			throw new CompileException("Unknown macro {{$name}}" . ($cdata ? " (in JavaScript or CSS, try to put a space after bracket.)" : ''));
		}
		foreach (array_reverse($this->macros[$name]) as $macro) {
			$node = new MacroNode($macro, $name, $args, $modifiers, $this->macroNodes ? end($this->macroNodes) : NULL, $htmlNode, $prefix);
			if ($macro->nodeOpened($node) !== FALSE) {
				return $node;
			}
		}
		throw new CompileException("Unhandled macro {{$name}}");
	}

}
