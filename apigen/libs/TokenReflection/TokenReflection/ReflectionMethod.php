<?php
/**
 * PHP Token Reflection
 *
 * Version 1.3.1
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this library in the file LICENSE.
 *
 * @author Ondřej Nešpor
 * @author Jaroslav Hanslík
 */

namespace TokenReflection;

use TokenReflection\Exception, TokenReflection\Stream\StreamBase as Stream;
use ReflectionMethod as InternalReflectionMethod, ReflectionClass as InternalReflectionClass;

/**
 * Tokenized class method reflection.
 */
class ReflectionMethod extends ReflectionFunctionBase implements IReflectionMethod
{
	/**
	 * An implemented abstract method.
	 *
	 * @see http://svn.php.net/viewvc/php/php-src/branches/PHP_5_3/Zend/zend_compile.h?revision=306939&view=markup#l114
	 * ZEND_ACC_IMPLICIT_PUBLIC
	 *
	 * @var integer
	 */
	const IS_IMPLEMENTED_ABSTRACT = 0x08;

	/**
	 * Access level of this method has changed from the original implementation.
	 *
	 * @see http://svn.php.net/viewvc/php/php-src/branches/PHP_5_3/Zend/zend_compile.h?revision=306939&view=markup#l134
	 * ZEND_ACC_CHANGED
	 *
	 * @var integer
	 */
	const ACCESS_LEVEL_CHANGED = 0x800;

	/**
	 * Method is constructor.
	 *
	 * Legacy constructors are not supported.
	 *
	 * @see http://svn.php.net/viewvc/php/php-src/branches/PHP_5_3/Zend/zend_compile.h?revision=306939&view=markup#l138
	 * ZEND_ACC_CTOR
	 *
	 * @var integer
	 */
	const IS_CONSTRUCTOR = 0x2000;

	/**
	 * Method is destructor.
	 *
	 * @see http://svn.php.net/viewvc/php/php-src/branches/PHP_5_3/Zend/zend_compile.h?revision=306939&view=markup#l139
	 * ZEND_ACC_DTOR
	 *
	 * @var integer
	 */
	const IS_DESTRUCTOR = 0x4000;

	/**
	 * Method is __clone().
	 *
	 * @see http://svn.php.net/viewvc/php/php-src/branches/PHP_5_3/Zend/zend_compile.h?revision=306939&view=markup#l140
	 * ZEND_ACC_CLONE
	 *
	 * @var integer
	 */
	const IS_CLONE = 0x8000;

	/**
	 * Method can be called statically (although not defined static).
	 *
	 * @see http://svn.php.net/viewvc/php/php-src/branches/PHP_5_3/Zend/zend_compile.h?revision=306939&view=markup#l143
	 * ZEND_ACC_ALLOW_STATIC
	 *
	 * @var integer
	 */
	const IS_ALLOWED_STATIC = 0x10000;

	/**
	 * Declaring class name.
	 *
	 * @var string
	 */
	private $declaringClassName;

	/**
	 * Method prototype reflection.
	 *
	 * @var \TokenReflection\IReflectionMethod
	 */
	private $prototype;

	/**
	 * Method modifiers.
	 *
	 * @var integer
	 */
	protected $modifiers = 0;

	/**
	 * Determined if the method is accessible.
	 *
	 * @var boolean
	 */
	private $accessible = false;

	/**
	 * Determines if modifiers are complete.
	 *
	 * @var boolean
	 */
	private $modifiersComplete = false;

	/**
	 * The original name when importing from a trait.
	 *
	 * @var string|null
	 */
	private $originalName = null;

	/**
	 * The original method when importing from a trait.
	 *
	 * @var \TokenReflection\IReflectionMethod|null
	 */
	private $original = null;

	/**
	 * The original modifiers value when importing from a trait.
	 *
	 * @var integer|null
	 */
	private $originalModifiers = null;

	/**
	 * Declaring trait name.
	 *
	 * @var string
	 */
	private $declaringTraitName;

	/**
	 * Returns the declaring class reflection.
	 *
	 * @return \TokenReflection\ReflectionClass|null
	 */
	public function getDeclaringClass()
	{
		return null === $this->declaringClassName ? null : $this->getBroker()->getClass($this->declaringClassName);
	}

	/**
	 * Returns the declaring class name.
	 *
	 * @return string|null
	 */
	public function getDeclaringClassName()
	{
		return $this->declaringClassName;
	}

	/**
	 * Returns method modifiers.
	 *
	 * @return integer
	 */
	public function getModifiers()
	{
		if (!$this->modifiersComplete && !($this->modifiers & (self::ACCESS_LEVEL_CHANGED | self::IS_IMPLEMENTED_ABSTRACT))) {
			$declaringClass = $this->getDeclaringClass();
			$parentClass = $declaringClass->getParentClass();
			if (false !== $parentClass && $parentClass->hasMethod($this->name)) {
				$parentClassMethod = $parentClass->getMethod($this->name);

				// Access level changed
				if (($this->isPublic() || $this->isProtected()) && $parentClassMethod->is(self::ACCESS_LEVEL_CHANGED | InternalReflectionMethod::IS_PRIVATE)) {
					$this->modifiers |= self::ACCESS_LEVEL_CHANGED;
				}

				// Implemented abstract
				if ($parentClassMethod->isAbstract() && !$this->isAbstract()) {
					$this->modifiers |= self::IS_IMPLEMENTED_ABSTRACT;
				}
			} else {
				// Check if it is an implementation of an interface method
				foreach ($declaringClass->getInterfaces() as $interface) {
					if ($interface->hasOwnMethod($this->name)) {
						$this->modifiers |= self::IS_IMPLEMENTED_ABSTRACT;
						break;
					}
				}
			}

			// Set if modifiers definition is complete
			$this->modifiersComplete = $this->isComplete() || (($this->modifiers & self::IS_IMPLEMENTED_ABSTRACT) && ($this->modifiers & self::ACCESS_LEVEL_CHANGED));
		}

		return $this->modifiers;
	}

	/**
	 * Returns if the method is abstract.
	 *
	 * @return boolean
	 */
	public function isAbstract()
	{
		return (bool) ($this->modifiers & InternalReflectionMethod::IS_ABSTRACT);
	}

	/**
	 * Returns if the method is final.
	 *
	 * @return boolean
	 */
	public function isFinal()
	{
		return (bool) ($this->modifiers & InternalReflectionMethod::IS_FINAL);
	}

	/**
	 * Returns if the method is private.
	 *
	 * @return boolean
	 */
	public function isPrivate()
	{
		return (bool) ($this->modifiers & InternalReflectionMethod::IS_PRIVATE);
	}

	/**
	 * Returns if the method is protected.
	 *
	 * @return boolean
	 */
	public function isProtected()
	{
		return (bool) ($this->modifiers & InternalReflectionMethod::IS_PROTECTED);
	}

	/**
	 * Returns if the method is public.
	 *
	 * @return boolean
	 */
	public function isPublic()
	{
		return (bool) ($this->modifiers & InternalReflectionMethod::IS_PUBLIC);
	}

	/**
	 * Returns if the method is static.
	 *
	 * @return boolean
	 */
	public function isStatic()
	{
		return (bool) ($this->modifiers & InternalReflectionMethod::IS_STATIC);
	}

	/**
	 * Shortcut for isPublic(), ... methods that allows or-ed modifiers.
	 *
	 * The {@see getModifiers()} method is called only when really necessary making this
	 * a more efficient way of doing
	 * <code>
	 *     if ($method->getModifiers() & $filter) {
	 *        ...
	 *     }
	 * </code>
	 *
	 * @param integer $filter Filter
	 * @return boolean
	 */
	public function is($filter = null)
	{
		// See self::ACCESS_LEVEL_CHANGED | self::IS_IMPLEMENTED_ABSTRACT
		static $computedModifiers = 0x808;

		if (null === $filter || ($this->modifiers & $filter)) {
			return true;
		} elseif (($filter & $computedModifiers) && !$this->modifiersComplete) {
			return (bool) ($this->getModifiers() & $filter);
		}

		return false;
	}

	/**
	 * Returns if the method is a constructor.
	 *
	 * @return boolean
	 */
	public function isConstructor()
	{
		return (bool) ($this->modifiers & self::IS_CONSTRUCTOR);
	}

	/**
	 * Returns if the method is a destructor.
	 *
	 * @return boolean
	 */
	public function isDestructor()
	{
		return (bool) ($this->modifiers & self::IS_DESTRUCTOR);
	}

	/**
	 * Returns the method prototype.
	 *
	 * @return \TokenReflection\ReflectionMethod
	 * @throws \TokenReflection\Exception\RuntimeException If the method has no prototype.
	 */
	public function getPrototype()
	{
		if (null === $this->prototype) {
			$prototype = null;

			$declaring = $this->getDeclaringClass();
			if (($parent = $declaring->getParentClass()) && $parent->hasMethod($this->name)) {
				$method = $parent->getMethod($this->name);

				if (!$method->isPrivate()) {
					try {
						$prototype = $method->getPrototype();
					} catch (Exception\RuntimeException $e) {
						$prototype = $method;
					}
				}
			}

			if (null === $prototype) {
				foreach ($declaring->getOwnInterfaces() as $interface) {
					if ($interface->hasMethod($this->name)) {
						$prototype = $interface->getMethod($this->name);
						break;
					}
				}
			}

			$this->prototype = $prototype ?: ($this->isComplete() ? false : null);
		}

		if (empty($this->prototype)) {
			throw new Exception\RuntimeException('Method has no prototype.', Exception\RuntimeException::DOES_NOT_EXIST, $this);
		}

		return $this->prototype;
	}

	/**
	 * Returns an element pretty (docblock compatible) name.
	 *
	 * @return string
	 */
	public function getPrettyName()
	{
		return sprintf('%s::%s', $this->declaringClassName ?: $this->declaringTraitName, parent::getPrettyName());
	}

	/**
	 * Returns the string representation of the reflection object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$internal = '';
		$overwrite = '';
		$prototype = '';

		$declaringClassParent = $this->getDeclaringClass()->getParentClass();
		try {
			$prototype = ', prototype ' . $this->getPrototype()->getDeclaringClassName();
		} catch (Exception\RuntimeException $e) {
			if ($declaringClassParent && $declaringClassParent->isInternal()) {
				$internal = 'internal:' . $parentClass->getExtensionName();
			}
		}

		if ($declaringClassParent && $declaringClassParent->hasMethod($this->name)) {
			$parentMethod = $declaringClassParent->getMethod($this->name);
			$overwrite = ', overwrites ' . $parentMethod->getDeclaringClassName();
		}

		if ($this->isConstructor()) {
			$cdtor = ', ctor';
		} elseif ($this->isDestructor()) {
			$cdtor = ', dtor';
		} else {
			$cdtor = '';
		}

		$parameters = '';
		if ($this->getNumberOfParameters() > 0) {
			$buffer = '';
			foreach ($this->getParameters() as $parameter) {
				$buffer .= "\n    " . $parameter->__toString();
			}
			$parameters = sprintf(
				"\n\n  - Parameters [%d] {%s\n  }",
				$this->getNumberOfParameters(),
				$buffer
			);
		}
		// @todo support inherits
		return sprintf(
			"%sMethod [ <%s%s%s%s> %s%s%s%s%s%s method %s%s ] {\n  @@ %s %d - %d%s\n}\n",
			$this->getDocComment() ? $this->getDocComment() . "\n" : '',
			!empty($internal) ? $internal : 'user',
			$overwrite,
			$prototype,
			$cdtor,
			$this->isAbstract() ? 'abstract ' : '',
			$this->isFinal() ? 'final ' : '',
			$this->isStatic() ? 'static ' : '',
			$this->isPublic() ? 'public' : '',
			$this->isPrivate() ? 'private' : '',
			$this->isProtected() ? 'protected' : '',
			$this->returnsReference() ? '&' : '',
			$this->getName(),
			$this->getFileName(),
			$this->getStartLine(),
			$this->getEndLine(),
			$parameters
		);
	}

	/**
	 * Exports a reflected object.
	 *
	 * @param \TokenReflection\Broker $broker Broker instance
	 * @param string|object $class Class name or class instance
	 * @param string $method Method name
	 * @param boolean $return Return the export instead of outputting it
	 * @return string|null
	 * @throws \TokenReflection\Exception\RuntimeException If requested parameter doesn't exist.
	 */
	public static function export(Broker $broker, $class, $method, $return = false)
	{
		$className = is_object($class) ? get_class($class) : $class;
		$methodName = $method;

		$class = $broker->getClass($className);
		if ($class instanceof Invalid\ReflectionClass) {
			throw new Exception\RuntimeException('Class is invalid.', Exception\RuntimeException::UNSUPPORTED);
		} elseif ($class instanceof Dummy\ReflectionClass) {
			throw new Exception\RuntimeException(sprintf('Class %s does not exist.', $className), Exception\RuntimeException::DOES_NOT_EXIST);
		}
		$method = $class->getMethod($methodName);

		if ($return) {
			return $method->__toString();
		}

		echo $method->__toString();
	}

	/**
	 * Calls the method on an given instance.
	 *
	 * @param object $object Class instance
	 * @param mixed $args
	 * @return mixed
	 */
	public function invoke($object, $args)
	{
		$params = func_get_args();
		return $this->invokeArgs(array_shift($params), $params);
	}

	/**
	 * Calls the method on an given object.
	 *
	 * @param object $object Class instance
	 * @param array $args Method parameter values
	 * @return mixed
	 * @throws \TokenReflection\Exception\RuntimeException If it is not possible to invoke the method.
	 */
	public function invokeArgs($object, array $args = array())
	{
		$declaringClass = $this->getDeclaringClass();
		if (!$declaringClass->isInstance($object)) {
			throw new Exception\RuntimeException(sprintf('Expected instance of or subclass of "%s".', $this->declaringClassName), Exception\RuntimeException::INVALID_ARGUMENT, $this);
		}

		if ($this->isPublic()) {
			return call_user_func_array(array($object, $this->getName()), $args);
		} elseif ($this->isAccessible()) {
			$refClass = new InternalReflectionClass($object);
			$refMethod = $refClass->getMethod($this->name);

			$refMethod->setAccessible(true);
			$value = $refMethod->invokeArgs($object, $args);
			$refMethod->setAccessible(false);

			return $value;
		}

		throw new Exception\RuntimeException('Only public methods can be invoked.', Exception\RuntimeException::NOT_ACCESSBILE, $this);
	}

	/**
	 * Returns if the property is set accessible.
	 *
	 * @return boolean
	 */
	public function isAccessible()
	{
		return $this->accessible;
	}

	/**
	 * Sets a method to be accessible or not.
	 *
	 * @param boolean $accessible
	 */
	public function setAccessible($accessible)
	{
		$this->accessible = (bool) $accessible;
	}

	/**
	 * Returns if the definition is complete.
	 *
	 * Technically returns if the declaring class definition is complete.
	 *
	 * @return boolean
	 */
	private function isComplete()
	{
		return $this->getDeclaringClass()->isComplete();
	}

	/**
	 * Returns imported namespaces and aliases from the declaring namespace.
	 *
	 * @return array
	 */
	public function getNamespaceAliases()
	{
		return $this->getDeclaringClass()->getNamespaceAliases();
	}

	/**
	 * Returns the function/method as closure.
	 *
	 * @param object $object Object
	 * @return \Closure
	 */
	public function getClosure($object)
	{
		$declaringClass = $this->getDeclaringClass();
		if (!$declaringClass->isInstance($object)) {
			throw new Exception\RuntimeException(sprintf('Expected instance of or subclass of "%s".', $this->declaringClassName), Exception\RuntimeException::INVALID_ARGUMENT, $this);
		}

		$that = $this;
		return function() use ($object, $that) {
			return $that->invokeArgs($object, func_get_args());
		};
	}

	/**
	 * Creates a method alias of the given name and access level for the given class.
	 *
	 * @param \TokenReflection\ReflectionClass $parent New parent class
	 * @param string $name New method name
	 * @param integer $accessLevel New access level
	 * @return \TokenReflection\ReflectionMethod
	 * @throws \TokenReflection\Exception\RuntimeException If an invalid method access level was found.
	 */
	public function alias(ReflectionClass $parent, $name = null, $accessLevel = null)
	{
		static $possibleLevels = array(InternalReflectionMethod::IS_PUBLIC => true, InternalReflectionMethod::IS_PROTECTED => true, InternalReflectionMethod::IS_PRIVATE => true);

		$method = clone $this;

		$method->declaringClassName = $parent->getName();
		if (null !== $name) {
			$method->originalName = $this->name;
			$method->name = $name;
		}
		if (null !== $accessLevel) {
			if (!isset($possibleLevels[$accessLevel])) {
				throw new Exception\RuntimeException(sprintf('Invalid method access level: "%s".', $accessLevel), Exception\RuntimeException::INVALID_ARGUMENT, $this);
			}

			$method->modifiers &= ~(InternalReflectionMethod::IS_PUBLIC | InternalReflectionMethod::IS_PROTECTED | InternalReflectionMethod::IS_PRIVATE);
			$method->modifiers |= $accessLevel;

			$method->originalModifiers = $this->getModifiers();
		}

		foreach ($this->parameters as $parameterName => $parameter) {
			$method->parameters[$parameterName] = $parameter->alias($method);
		}

		return $method;
	}

	/**
	 * Returns the original name when importing from a trait.
	 *
	 * @return string|null
	 */
	public function getOriginalName()
	{
		return $this->originalName;
	}

	/**
	 * Returns the original method when importing from a trait.
	 *
	 * @return \TokenReflection\IReflectionMethod|null
	 */
	public function getOriginal()
	{
		return $this->original;
	}

	/**
	 * Returns the original modifiers value when importing from a trait.
	 *
	 * @return integer|null
	 */
	public function getOriginalModifiers()
	{
		return $this->originalModifiers;
	}

	/**
	 * Returns the defining trait.
	 *
	 * @return \TokenReflection\IReflectionClass|null
	 */
	public function getDeclaringTrait()
	{
		return null === $this->declaringTraitName ? null : $this->getBroker()->getClass($this->declaringTraitName);
	}

	/**
	 * Returns the declaring trait name.
	 *
	 * @return string|null
	 */
	public function getDeclaringTraitName()
	{
		return $this->declaringTraitName;
	}

	/**
	 * Processes the parent reflection object.
	 *
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionElement
	 * @throws \TokenReflection\Exception\ParseException If an invalid parent reflection object was provided.
	 */
	protected function processParent(IReflection $parent, Stream $tokenStream)
	{
		if (!$parent instanceof ReflectionClass) {
			throw new Exception\ParseException($this, $tokenStream, 'The parent object has to be an instance of TokenReflection\ReflectionClass.', Exception\ParseException::INVALID_PARENT);
		}

		$this->declaringClassName = $parent->getName();
		if ($parent->isTrait()) {
			$this->declaringTraitName = $parent->getName();
		}
		return parent::processParent($parent, $tokenStream);
	}

	/**
	 * Parses reflected element metadata from the token stream.
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @param \TokenReflection\IReflection $parent Parent reflection object
	 * @return \TokenReflection\ReflectionMethod
	 * @throws \TokenReflection\Exception\Parse If the class could not be parsed.
	 */
	protected function parse(Stream $tokenStream, IReflection $parent)
	{
		return $this
			->parseBaseModifiers($tokenStream)
			->parseReturnsReference($tokenStream)
			->parseName($tokenStream)
			->parseInternalModifiers($parent);
	}

	/**
	 * Parses base method modifiers (abstract, final, public, ...).
	 *
	 * @param \TokenReflection\Stream\StreamBase $tokenStream Token substream
	 * @return \TokenReflection\ReflectionMethod
	 */
	private function parseBaseModifiers(Stream $tokenStream)
	{
		while (true) {
			switch ($tokenStream->getType()) {
				case T_ABSTRACT:
					$this->modifiers |= InternalReflectionMethod::IS_ABSTRACT;
					break;
				case T_FINAL:
					$this->modifiers |= InternalReflectionMethod::IS_FINAL;
					break;
				case T_PUBLIC:
					$this->modifiers |= InternalReflectionMethod::IS_PUBLIC;
					break;
				case T_PRIVATE:
					$this->modifiers |= InternalReflectionMethod::IS_PRIVATE;
					break;
				case T_PROTECTED:
					$this->modifiers |= InternalReflectionMethod::IS_PROTECTED;
					break;
				case T_STATIC:
					$this->modifiers |= InternalReflectionMethod::IS_STATIC;
					break;
				case T_FUNCTION:
				case null:
					break 2;
				default:
					break;
			}

			$tokenStream->skipWhitespaces();
		}

		if (!($this->modifiers & (InternalReflectionMethod::IS_PRIVATE | InternalReflectionMethod::IS_PROTECTED))) {
			$this->modifiers |= InternalReflectionMethod::IS_PUBLIC;
		}

		return $this;
	}

	/**
	 * Parses internal PHP method modifiers (abstract, final, public, ...).
	 *
	 * @param \TokenReflection\ReflectionClass $class Parent class
	 * @return \TokenReflection\ReflectionMethod
	 */
	private function parseInternalModifiers(ReflectionClass $class)
	{
		$name = strtolower($this->name);
		// In PHP 5.3.3+ the ctor can be named only __construct in namespaced classes
		if ('__construct' === $name || ((!$class->inNamespace() || PHP_VERSION_ID < 50303) && strtolower($class->getShortName()) === $name)) {
			$this->modifiers |= self::IS_CONSTRUCTOR;
		} elseif ('__destruct' === $name) {
			$this->modifiers |= self::IS_DESTRUCTOR;
		} elseif ('__clone' === $name) {
			$this->modifiers |= self::IS_CLONE;
		}

		if ($class->isInterface()) {
			$this->modifiers |= InternalReflectionMethod::IS_ABSTRACT;
		} else {
			// Can be called statically, see http://svn.php.net/viewvc/php/php-src/branches/PHP_5_3/Zend/zend_API.c?revision=309853&view=markup#l1795
			static $notAllowed = array('__clone' => true, '__tostring' => true, '__get' => true, '__set' => true, '__isset' => true, '__unset' => true);
			if (!$this->isStatic() && !$this->isConstructor() && !$this->isDestructor() && !isset($notAllowed[$name])) {
				$this->modifiers |= self::IS_ALLOWED_STATIC;
			}
		}

		return $this;
	}
}
