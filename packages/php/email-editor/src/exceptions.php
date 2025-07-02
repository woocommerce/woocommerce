<?php declare(strict_types = 1);

// phpcs:ignoreFile PSR1.Classes.ClassDeclaration
namespace Automattic\WooCommerce\EmailEditor;

/**
 * Provides information for converting exceptions to HTTP responses.
 */
interface HttpAwareException {
  public function getHttpStatusCode(): int;
}


/**
 * Frames all exceptions ("$e instanceof Automattic\WooCommerce\EmailEditor\Exception").
 */
abstract class Exception extends \Exception {
  /** @var string[] */
  private $errors = [];

  final public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null) {
    parent::__construct($message, $code, $previous);
  }

  /** @return static */
  public static function create(?\Throwable $previous = null) {
    return new static('', 0, $previous);
  }

  /** @return static */
  public function withMessage(string $message) {
    $this->message = $message;
    return $this;
  }

  /** @return static */
  public function withCode(int $code) {
    $this->code = $code;
    return $this;
  }

  /**
   * @param string[] $errors
   * @return static
   */
  public function withErrors(array $errors) {
    $this->errors = $errors;
    return $this;
  }

  /** @return static */
  public function withError(string $id, string $error) {
    $this->errors[$id] = $error;
    return $this;
  }

  /**
   * @return string[]
   */
  public function getErrors(): array {
    return $this->errors;
  }
}


/**
 * USE: Generic runtime error. When possible, use a more specific exception instead.
 * API: 500 Server Error (not HTTP-aware)
 */
class RuntimeException extends Exception {}


/**
 * USE: When wrong data VALUE is received.
 * API: 400 Bad Request
 */
class UnexpectedValueException extends RuntimeException implements HttpAwareException {
  public function getHttpStatusCode(): int {
    return 400;
  }
}


/**
 * USE: When an action is forbidden for given actor (although generally valid).
 * API: 403 Forbidden
 */
class AccessDeniedException extends UnexpectedValueException implements HttpAwareException {
  public function getHttpStatusCode(): int {
    return 403;
  }
}


/**
 * USE: When the main resource we're interested in doesn't exist.
 * API: 404 Not Found
 */
class NotFoundException extends UnexpectedValueException implements HttpAwareException {
  public function getHttpStatusCode(): int {
    return 404;
  }
}


/**
 * USE: When the main action produces conflict (i.e. duplicate key).
 * API: 409 Conflict
 */
class ConflictException extends UnexpectedValueException implements HttpAwareException {
  public function getHttpStatusCode(): int {
    return 409;
  }
}


/**
 * USE: An application state that should not occur. Can be subclassed for feature-specific exceptions.
 * API: 500 Server Error (not HTTP-aware)
 */
class InvalidStateException extends RuntimeException {}

class NewsletterProcessingException extends Exception {}
