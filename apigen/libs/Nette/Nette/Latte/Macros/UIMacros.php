<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Latte\Macros;

use Nette,
	Nette\Latte,
	Nette\Latte\MacroNode,
	Nette\Latte\PhpWriter,
	Nette\Latte\CompileException,
	Nette\Utils\Strings;



/**
 * Macros for Nette\Application\UI.
 *
 * - {link destination ...} control link
 * - {plink destination ...} presenter link
 * - {snippet ?} ... {/snippet ?} control snippet
 * - {contentType ...} HTTP Content-Type header
 * - {status ...} HTTP status
 *
 * @author     David Grudl
 */
class UIMacros extends MacroSet
{
	/** @var array */
	private $namedBlocks = array();

	/** @var bool */
	private $extends;



	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);
		$me->addMacro('include', array($me, 'macroInclude'));
		$me->addMacro('includeblock', array($me, 'macroIncludeBlock'));
		$me->addMacro('extends', array($me, 'macroExtends'));
		$me->addMacro('layout', array($me, 'macroExtends'));
		$me->addMacro('block', array($me, 'macroBlock'), array($me, 'macroBlockEnd'));
		$me->addMacro('define', array($me, 'macroBlock'), array($me, 'macroBlockEnd'));
		$me->addMacro('snippet', array($me, 'macroBlock'), array($me, 'macroBlockEnd'));
		$me->addMacro('ifset', array($me, 'macroIfset'), 'endif');

		$me->addMacro('widget', array($me, 'macroControl')); // deprecated - use control
		$me->addMacro('control', array($me, 'macroControl'));

		$me->addMacro('href', NULL, NULL, function(MacroNode $node, PhpWriter $writer) use ($me) {
			return ' ?> href="<?php ' . $me->macroLink($node, $writer) . ' ?>"<?php ';
		});
		$me->addMacro('plink', array($me, 'macroLink'));
		$me->addMacro('link', array($me, 'macroLink'));
		$me->addMacro('ifCurrent', array($me, 'macroIfCurrent'), 'endif'); // deprecated; use n:class="$presenter->linkCurrent ? ..."

		$me->addMacro('contentType', array($me, 'macroContentType'));
		$me->addMacro('status', array($me, 'macroStatus'));
	}



	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	public function initialize()
	{
		$this->namedBlocks = array();
		$this->extends = NULL;
	}



	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		// try close last block
		try {
			$this->getCompiler()->writeMacro('/block');
		} catch (CompileException $e) {
		}

		$epilog = $prolog = array();

		if ($this->namedBlocks) {
			foreach ($this->namedBlocks as $name => $code) {
				$func = '_lb' . substr(md5($this->getCompiler()->getTemplateId() . $name), 0, 10) . '_' . preg_replace('#[^a-z0-9_]#i', '_', $name);
				$snippet = $name[0] === '_';
				$prolog[] = "//\n// block $name\n//\n"
					. "if (!function_exists(\$_l->blocks[" . var_export($name, TRUE) . "][] = '$func')) { "
					. "function $func(\$_l, \$_args) { "
					. (PHP_VERSION_ID > 50208 ? 'extract($_args)' : 'foreach ($_args as $__k => $__v) $$__k = $__v') // PHP bug #46873
					. ($snippet ? '; $_control->validateControl(' . var_export(substr($name, 1), TRUE) . ')' : '')
					. "\n?>$code<?php\n}}";
			}
			$prolog[] = "//\n// end of blocks\n//";
		}

		if ($this->namedBlocks || $this->extends) {
			$prolog[] = "// template extending and snippets support";

			$prolog[] = '$_l->extends = '
				. ($this->extends ? $this->extends : 'empty($template->_extended) && isset($_control) && $_control instanceof Nette\Application\UI\Presenter ? $_control->findLayoutTemplateFile() : NULL')
				. '; $template->_extended = $_extended = TRUE;';

			$prolog[] = '
if ($_l->extends) {
	' . ($this->namedBlocks ? 'ob_start();' : 'return Nette\Latte\Macros\CoreMacros::includeTemplate($_l->extends, get_defined_vars(), $template)->render();') . '

} elseif (!empty($_control->snippetMode)) {
	return Nette\Latte\Macros\UIMacros::renderSnippets($_control, $_l, get_defined_vars());
}';
		} else {
			$prolog[] = '
// snippets support
if (!empty($_control->snippetMode)) {
	return Nette\Latte\Macros\UIMacros::renderSnippets($_control, $_l, get_defined_vars());
}';
		}

		return array(implode("\n\n", $prolog), implode("\n", $epilog));
	}



	/********************* macros ****************d*g**/



	/**
	 * {include #block}
	 */
	public function macroInclude(MacroNode $node, PhpWriter $writer)
	{
		$destination = $node->tokenizer->fetchWord(); // destination [,] [params]
		if (substr($destination, 0, 1) !== '#') {
			return FALSE;
		}

		$destination = ltrim($destination, '#');
		$parent = $destination === 'parent';
		if ($destination === 'parent' || $destination === 'this') {
			for ($item = $node->parentNode; $item && $item->name !== 'block' && !isset($item->data->name); $item = $item->parentNode);
			if (!$item) {
				throw new CompileException("Cannot include $destination block outside of any block.");
			}
			$destination = $item->data->name;
		}

		$name = Strings::contains($destination, '$') ? $destination : var_export($destination, TRUE);
		if (isset($this->namedBlocks[$destination]) && !$parent) {
			$cmd = "call_user_func(reset(\$_l->blocks[$name]), \$_l, %node.array? + get_defined_vars())";
		} else {
			$cmd = 'Nette\Latte\Macros\UIMacros::callBlock' . ($parent ? 'Parent' : '') . "(\$_l, $name, %node.array? + " . ($parent ? 'get_defined_vars' : '$template->getParameters') . '())';
		}

		if ($node->modifiers) {
			return $writer->write("ob_start(); $cmd; echo %modify(ob_get_clean())");
		} else {
			return $writer->write($cmd);
		}
	}



	/**
	 * {includeblock "file"}
	 */
	public function macroIncludeBlock(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('Nette\Latte\Macros\CoreMacros::includeTemplate(%node.word, %node.array? + get_defined_vars(), $_l->templates[%var])->render()',
			$this->getCompiler()->getTemplateId());
	}



	/**
	 * {extends auto | none | $var | "file"}
	 */
	public function macroExtends(MacroNode $node, PhpWriter $writer)
	{
		if (!$node->args) {
			throw new CompileException("Missing destination in {extends}");
		}
		if (!empty($node->parentNode)) {
			throw new CompileException("{extends} must be placed outside any macro.");
		}
		if ($this->extends !== NULL) {
			throw new CompileException("Multiple {extends} declarations are not allowed.");
		}
		if ($node->args === 'none') {
			$this->extends = 'FALSE';
		} elseif ($node->args === 'auto') {
			$this->extends = '$_presenter->findLayoutTemplateFile()';
		} else {
			$this->extends = $writer->write('%node.word%node.args');
		}
		return;
	}



	/**
	 * {block [[#]name]}
	 * {snippet [name [,]] [tag]}
	 * {define [#]name}
	 */
	public function macroBlock(MacroNode $node, PhpWriter $writer)
	{
		$name = $node->tokenizer->fetchWord();

		if ($node->name === 'block' && $name === FALSE) { // anonymous block
			return $node->modifiers === '' ? '' : 'ob_start()';
		}

		$node->data->name = $name = ltrim($name, '#');
		if ($name == NULL) {
			if ($node->name !== 'snippet') {
				throw new CompileException("Missing block name.");
			}

		} elseif (Strings::contains($name, '$')) { // dynamic block/snippet
			if ($node->name === 'snippet') {
				for ($parent = $node->parentNode; $parent && $parent->name !== 'snippet'; $parent = $parent->parentNode);
				if (!$parent) {
					throw new CompileException("Dynamic snippets are allowed only inside static snippet.");
				}
				$parent->data->dynamic = TRUE;
				$node->data->leave = TRUE;
				$node->closingCode = "<?php \$_dynSnippets[\$_dynSnippetId] = ob_get_flush() ?>";

				if ($node->htmlNode) {
					$node->attrCode = $writer->write("<?php echo ' id=\"' . (\$_dynSnippetId = \$_control->getSnippetId({$writer->formatWord($name)})) . '\"' ?>");
					return $writer->write('ob_start()');
				}
				$tag = trim($node->tokenizer->fetchWord(), '<>');
				$tag = $tag ? $tag : 'div';
				$node->closingCode .= "\n</$tag>";
				return $writer->write("?>\n<$tag id=\"<?php echo \$_dynSnippetId = \$_control->getSnippetId({$writer->formatWord($name)}) ?>\"><?php ob_start()");

			} else {
				$node->data->leave = TRUE;
				$fname = $writer->formatWord($name);
				$node->closingCode = "<?php }} " . ($node->name === 'define' ? '' : "call_user_func(reset(\$_l->blocks[$fname]), \$_l, get_defined_vars())") . " ?>";
				$func = '_lb' . substr(md5($this->getCompiler()->getTemplateId() . $name), 0, 10) . '_' . preg_replace('#[^a-z0-9_]#i', '_', $name);
				return "\n\n//\n// block $name\n//\n"
					. "if (!function_exists(\$_l->blocks[$fname]['{$this->getCompiler()->getTemplateId()}'] = '$func')) { "
					. "function $func(\$_l, \$_args) { "
					. (PHP_VERSION_ID > 50208 ? 'extract($_args)' : 'foreach ($_args as $__k => $__v) $$__k = $__v'); // PHP bug #46873
			}
		}

		// static block/snippet
		if ($node->name === 'snippet') {
			$node->data->name = $name = '_' . $name;
		}

		if (isset($this->namedBlocks[$name])) {
			throw new CompileException("Cannot redeclare static block '$name'");
		}

		$prolog = $this->namedBlocks ? '' : "if (\$_l->extends) { ob_end_clean(); return Nette\\Latte\\Macros\\CoreMacros::includeTemplate(\$_l->extends, get_defined_vars(), \$template)->render(); }\n";
		$top = empty($node->parentNode);
		$this->namedBlocks[$name] = TRUE;

		$include = 'call_user_func(reset($_l->blocks[%var]), $_l, ' . ($node->name === 'snippet' ? '$template->getParameters()' : 'get_defined_vars()') . ')';
		if ($node->modifiers) {
			$include = "ob_start(); $include; echo %modify(ob_get_clean())";
		}

		if ($node->name === 'snippet') {
			if ($node->htmlNode) {
				$node->attrCode = $writer->write('<?php echo \' id="\' . $_control->getSnippetId(%var) . \'"\' ?>', (string) substr($name, 1));
				return $writer->write($prolog . $include, $name);
			}
			$tag = trim($node->tokenizer->fetchWord(), '<>');
			$tag = $tag ? $tag : 'div';
			return $writer->write("$prolog ?>\n<$tag id=\"<?php echo \$_control->getSnippetId(%var) ?>\"><?php $include ?>\n</$tag><?php ",
				(string) substr($name, 1), $name
			);

		} elseif ($node->name === 'define') {
			return $prolog;

		} else {
			return $writer->write($prolog . $include, $name);
		}
	}



	/**
	 * {/block}
	 * {/snippet}
	 * {/define}
	 */
	public function macroBlockEnd(MacroNode $node, PhpWriter $writer)
	{
		if (isset($node->data->name)) { // block, snippet, define
			if ($node->name === 'snippet' && $node->htmlNode && !$node->prefix // n:snippet -> n:inner-snippet
				&& preg_match("#^.*? n:\w+>\n?#s", $node->content, $m1) && preg_match("#[ \t]*<[^<]+$#sD", $node->content, $m2))
			{
				$node->openingCode = $m1[0] . $node->openingCode;
				$node->content = substr($node->content, strlen($m1[0]), -strlen($m2[0]));
				$node->closingCode .= $m2[0];
			}

			if (empty($node->data->leave)) {
				if (!empty($node->data->dynamic)) {
					$node->content .= '<?php if (isset($_dynSnippets)) return $_dynSnippets; ?>';
				}
				$this->namedBlocks[$node->data->name] = $tmp = rtrim(ltrim($node->content, "\n"), " \t");
				$node->content = substr_replace($node->content, $node->openingCode . "\n", strspn($node->content, "\n"), strlen($tmp));
				$node->openingCode = "<?php ?>";
			}

		} elseif ($node->modifiers) { // anonymous block with modifier
			return $writer->write('echo %modify(ob_get_clean())');
		}
	}



	/**
	 * {ifset #block}
	 */
	public function macroIfset(MacroNode $node, PhpWriter $writer)
	{
		if (!Strings::contains($node->args, '#')) {
			return FALSE;
		}
		$list = array();
		while (($name = $node->tokenizer->fetchWord()) !== FALSE) {
			$list[] = $name[0] === '#' ? '$_l->blocks["' . substr($name, 1) . '"]' : $name;
		}
		return 'if (isset(' . implode(', ', $list) . ')):';
	}



	/**
	 * {control name[:method] [params]}
	 */
	public function macroControl(MacroNode $node, PhpWriter $writer)
	{
		$pair = $node->tokenizer->fetchWord();
		if ($pair === FALSE) {
			throw new CompileException("Missing control name in {control}");
		}
		$pair = explode(':', $pair, 2);
		$name = $writer->formatWord($pair[0]);
		$method = isset($pair[1]) ? ucfirst($pair[1]) : '';
		$method = Strings::match($method, '#^\w*$#') ? "render$method" : "{\"render$method\"}";
		$param = $writer->formatArray();
		if (!Strings::contains($node->args, '=>')) {
			$param = substr($param, 6, -1); // removes array()
		}
		return ($name[0] === '$' ? "if (is_object($name)) \$_ctrl = $name; else " : '')
			. '$_ctrl = $_control->getComponent(' . $name . '); '
			. 'if ($_ctrl instanceof Nette\Application\UI\IRenderable) $_ctrl->validateControl(); '
			. "\$_ctrl->$method($param)";
	}



	/**
	 * {link destination [,] [params]}
	 * {plink destination [,] [params]}
	 * n:href="destination [,] [params]"
	 */
	public function macroLink(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('echo %escape(%modify(' . ($node->name === 'plink' ? '$_presenter' : '$_control') . '->link(%node.word, %node.array?)))');
	}



	/**
	 * {ifCurrent destination [,] [params]}
	 */
	public function macroIfCurrent(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write(($node->args ? 'try { $_presenter->link(%node.word, %node.array?); } catch (Nette\Application\UI\InvalidLinkException $e) {}' : '')
			. '; if ($_presenter->getLastCreatedRequestFlag("current")):');
	}



	/**
	 * {contentType ...}
	 */
	public function macroContentType(MacroNode $node, PhpWriter $writer)
	{
		if (Strings::contains($node->args, 'xhtml')) {
			$this->getCompiler()->setContentType(Latte\Compiler::CONTENT_XHTML);

		} elseif (Strings::contains($node->args, 'html')) {
			$this->getCompiler()->setContentType(Latte\Compiler::CONTENT_HTML);

		} elseif (Strings::contains($node->args, 'xml')) {
			$this->getCompiler()->setContentType(Latte\Compiler::CONTENT_XML);

		} elseif (Strings::contains($node->args, 'javascript')) {
			$this->getCompiler()->setContentType(Latte\Compiler::CONTENT_JS);

		} elseif (Strings::contains($node->args, 'css')) {
			$this->getCompiler()->setContentType(Latte\Compiler::CONTENT_CSS);

		} elseif (Strings::contains($node->args, 'calendar')) {
			$this->getCompiler()->setContentType(Latte\Compiler::CONTENT_ICAL);

		} else {
			$this->getCompiler()->setContentType(Latte\Compiler::CONTENT_TEXT);
		}

		// temporary solution
		if (Strings::contains($node->args, '/')) {
			return $writer->write('$netteHttpResponse->setHeader("Content-Type", %var)', $node->args);
		}
	}



	/**
	 * {status ...}
	 */
	public function macroStatus(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write((substr($node->args, -1) === '?' ? 'if (!$netteHttpResponse->isSent()) ' : '') .
			'$netteHttpResponse->setCode(%var)', (int) $node->args
		);
	}



	/********************* run-time writers ****************d*g**/



	/**
	 * Calls block.
	 * @return void
	 */
	public static function callBlock(\stdClass $context, $name, array $params)
	{
		if (empty($context->blocks[$name])) {
			throw new Nette\InvalidStateException("Cannot include undefined block '$name'.");
		}
		$block = reset($context->blocks[$name]);
		$block($context, $params);
	}



	/**
	 * Calls parent block.
	 * @return void
	 */
	public static function callBlockParent(\stdClass $context, $name, array $params)
	{
		if (empty($context->blocks[$name]) || ($block = next($context->blocks[$name])) === FALSE) {
			throw new Nette\InvalidStateException("Cannot include undefined parent block '$name'.");
		}
		$block($context, $params);
	}



	public static function renderSnippets(Nette\Application\UI\Control $control, \stdClass $local, array $params)
	{
		$control->snippetMode = FALSE;
		$payload = $control->getPresenter()->getPayload();
		if (isset($local->blocks)) {
			foreach ($local->blocks as $name => $function) {
				if ($name[0] !== '_' || !$control->isControlInvalid(substr($name, 1))) {
					continue;
				}
				ob_start();
				$function = reset($function);
				$snippets = $function($local, $params);
				$payload->snippets[$id = $control->getSnippetId(substr($name, 1))] = ob_get_clean();
				if ($snippets) {
					$payload->snippets += $snippets;
					unset($payload->snippets[$id]);
				}
			}
		}
		$control->snippetMode = TRUE;
		if ($control instanceof Nette\Application\UI\IRenderable) {
			$queue = array($control);
			do {
				foreach (array_shift($queue)->getComponents() as $child) {
					if ($child instanceof Nette\Application\UI\IRenderable) {
						if ($child->isControlInvalid()) {
							$child->snippetMode = TRUE;
							$child->render();
							$child->snippetMode = FALSE;
						}
					} elseif ($child instanceof Nette\ComponentModel\IContainer) {
						$queue[] = $child;
					}
				}
			} while ($queue);
		}
	}

}
