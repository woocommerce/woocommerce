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
 * Simplify_Authentication - encapsulates the credentials needed to make a request to the Simplify API.
 *
 * @var $publicKey - this is your API public key
 * @var $privateKey - this is your API private key
 * @var $accessToken - Oauth access token that is needed to make API requests on behalf of another user
 */
class Simplify_Authentication {

	public $privateKey;
	public $publicKey;
	public $accessToken;

	function __construct() {
		$args = func_get_args();
		switch( func_num_args() ) {
			case 1:
				self::__construct1( $args[0] );
				break;
			case 2:
				self::__construct2( $args[0], $args[1] );
				break;
			case 3:
				self::__construct3( $args[0], $args[1], $args[2] );
		}
	}

	function __construct1($accessToken) {
		$this->accessToken = $accessToken;
	}

	function __construct2($publicKey, $privateKey) {
		$this->publicKey = $publicKey;
		$this->privateKey = $privateKey;
	}

	function __construct3($publicKey, $privateKey, $accessToken) {
		$this->publicKey = $publicKey;
		$this->privateKey = $privateKey;
		$this->accessToken = $accessToken;
	}
}
