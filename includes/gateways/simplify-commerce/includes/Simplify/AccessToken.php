<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
 * AccessToken - used to manage OAuth access tokens
 */
class Simplify_AccessToken extends Simplify_Object {

	public function __construct($hash) {
		$this->setAll($hash);
	}

	/**
	 * Creates an OAuth access token
	 * @param $code - the authorisation code returned from the GET on /oauth/authorize
	 * @param $redirect_uri = this must be the redirect_uri set in the apps configuration
	 * @param $authentication - Authentication information to access the API.  If not value is passed the global key Simplify::$publicKey and Simplify::$privateKey are used
	 * @return Simplify_AccessToken
	 */
	public static function create($code, $redirect_uri, $authentication = null) {

		$args = func_get_args();
		$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 3);

		$props = 'code='.$code.'&redirect_uri='.$redirect_uri.'&grant_type=authorization_code';
		$resp = Simplify_AccessToken::sendRequest($props, "token", $authentication);

		return new Simplify_AccessToken($resp);
	}

	/**
	 * Refreshes the current token.  The access_token and refresh_token values will be updated.
	 * @param $authentication - Authentication information to access the API.  If not value is passed the global key Simplify::$publicKey and Simplify::$privateKey are used
	 * @return Simplify_AccessToken
	 * @throws InvalidArgumentException
	 */
	public function refresh($authentication = null) {
		$args = func_get_args();

		$refresh_token = $this->refresh_token;
		if (empty($refresh_token)){
			throw new InvalidArgumentException('Cannot refresh access token; refresh token is invalid');
		}

		$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

		$props = 'refresh_token='.$refresh_token.'&grant_type=refresh_token';
		$resp = Simplify_AccessToken::sendRequest($props, "token", $authentication);

		$this->setAll($resp);

		return $this;
	}

	/**
	 * <p>Revokes a token from further use.
	 * @param $authentication - Authentication information to access the API.  If not value is passed the global key Simplify::$publicKey and Simplify::$privateKey are used
	 * @return Simplify_AccessToken
	 * @throws InvalidArgumentException
	 */
	public function revoke($authentication = null) {
		$args = func_get_args();

		$access_token = $this->access_token;
		if (empty($access_token)){
			throw new InvalidArgumentException('Cannot revoke access token; access token is invalid');
		}

		$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

		$props = 'token='.$access_token.'';

		Simplify_AccessToken::sendRequest($props, "revoke", $authentication);

		$this->access_token = null;
		$this->refresh_token = null;
		$this->redirect_uri = null;

		return $this;
	}

	private static function sendRequest($props, $context, $authentication){

		$url = Simplify_Constants::OAUTH_BASE_URL.'/'.$context;
		$http = new Simplify_HTTP();
		$resp = $http->oauthRequest($url, $props, $authentication);

		return $resp;
	}

	/**
	 * @ignore
	 */
	static public function getClazz() {
		return "AccessToken";
	}

}
