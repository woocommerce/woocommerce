<?php
/*
 * Copyright (c) 2013 - 2015 MasterCard International Incorporated
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of
 * conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its
 * contributors may be used to endorse or promote products derived from this software
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */


/**
 *
 * Base class for all API exceptions.
 *
 */
class Simplify_ApiException extends Exception
{

	protected $errorData;
	protected $status;
	protected $errorCode;
	protected $reference;

	/**
	 * @ignore
	 */
	function __construct($message, $status = null, $errorData = null) {
		parent::__construct($message);

		$this->status = $status;
		$this->errorCode = null;
		$this->reference = null;

		if ($errorData != null) {

			$this->reference = $errorData['reference'];
			$this->errorData = $errorData;

			$error = $errorData['error'];
			if ($error != null) {

				$m = $error['message'];
				if ($m != null) {
					$this->message = $m;
				}

				$this->errorCode = $error['code'];
			}
		}
	}

	/**
	 * Returns a map of all error data returned by the API.
	 * @return array a map containing API error data.
	 */
	function getErrorData() {
		return $this->errorData;
	}

	/**
	 * Returns the HTTP status for the request.
	 * @return string HTTP status code (or null if there is no status).
	 */
	function getStatus() {
		return $this->status;
	}

	/**
	 * Returns unique reference for the API error.
	 * @return string a reference (or null if there is no reference).
	 */
	function getReference() {
		return $this->reference;
	}

	/**
	 * Returns an code for the API error.
	 * @return string the error code.
	 */
	function getErrorCode() {
		return $this->errorCode;
	}

	/**
	 * Returns a description of the error.
	 * @return string Description of the error.
	 */
	function describe() {
		return get_class($this) . ": \""
			. $this->getMessage() . "\" (status: "
			. $this->getStatus() . ", error code: "
			. $this->getErrorCode() . ", reference: "
			. $this->getReference() . ")";
	}

}


/**
 * Exception raised when there are communication problems contacting the API.
 */
class Simplify_ApiConnectionException extends Simplify_ApiException {

	/**
	 * @ignore
	 */
	function __construct($message, $status = null, $errorData = null) {
		parent::__construct($message, $status, $errorData);
	}
}

/**
 * Exception raised where there are problems authenticating a request.
 */
class Simplify_AuthenticationException extends Simplify_ApiException {

	/**
	 * @ignore
	 */
	function __construct($message, $status = null, $errorData = null) {
		parent::__construct($message, $status, $errorData);
	}
}

/**
 * Exception raised when the API request contains errors.
 */
class Simplify_BadRequestException extends Simplify_ApiException {

	protected $fieldErrors;

	/**
	 * @ignore
	 */
	function __construct($message, $status = null, $errorData = null) {
		parent::__construct($message, $status, $errorData);

		$fieldErrors = array();

		if ($errorData != null) {
			$error = $errorData['error'];
			if ($error != null) {
				$fieldErrors = $error['fieldErrors'];
				if ($fieldErrors != null) {
					$this->fieldErrors = array();
					foreach ($fieldErrors as $fieldError) {
						array_push($this->fieldErrors, new Simplify_FieldError($fieldError));
					}
				}
			}
		}
	}

	/**
	 * Returns a boolean indicating whether there are any field errors.
	 * @return boolean true if there are field errors; false otherwise.
	 */
	function hasFieldErrors() {
		return count($this->fieldErrors) > 0;
	}

	/**
	 * Returns a list containing all field errors.
	 * @return array list of field errors.
	 */
	function getFieldErrors() {
		return $this->fieldErrors;
	}

	/**
	 * Returns a description of the error.
	 * @return string description of the error.
	 */
	function describe() {
		$s = parent::describe();
		foreach ($this->getFieldErrors() as $fieldError) {
			$s = $s . "\n" . (string) $fieldError;
		}
		return $s . "\n";
	}

}

/**
 * Represents a single error in a field of a request sent to the API.
 */
class Simplify_FieldError {

	protected $field;
	protected $code;
	protected $message;

	/**
	 * @ignore
	 */
	function __construct($errorData) {

		$this->field = $errorData['field'];
		$this->code = $errorData['code'];
		$this->message = $errorData['message'];
	}

	/**
	 * Returns the name of the field with the error.
	 * @return string the field name.
	 */
	function getFieldName() {
		return $this->field;
	}

	/**
	 * Returns the code for the error.
	 * @return string the error code.
	 */
	function getErrorCode() {
		return $this->code;
	}

	/**
	 * Returns a description of the error.
	 * @return string description of the error.
	 */
	function getMessage() {
		return $this->message;
	}


	function __toString() {
		return "Field error: " . $this->getFieldName() . "\"" . $this->getMessage() . "\" (" . $this->getErrorCode() . ")";
	}

}

/**
 * Exception when a requested object cannot be found.
 */
class Simplify_ObjectNotFoundException extends Simplify_ApiException {

	/**
	 * @ignore
	 */
	function __construct($message, $status = null, $errorData = null) {
		parent::__construct($message, $status, $errorData);
	}
}

/**
 * Exception when a request was not allowed.
 */
class Simplify_NotAllowedException extends Simplify_ApiException {

	/**
	 * @ignore
	 */
	function __construct($message, $status = null, $errorData = null) {
		parent::__construct($message, $status, $errorData);
	}
}

/**
 * Exception when there was a system error processing a request.
 */
class Simplify_SystemException extends Simplify_ApiException {

	/**
	 * @ignore
	 */
	function __construct($message, $status = null, $errorData = null) {
		parent::__construct($message, $status, $errorData);
	}
}
