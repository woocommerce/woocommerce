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


class Simplify_Event extends Simplify_Object {

	/**
	 * Creates an Event object
	 * @param     array $hash A map of parameters; valid keys are:
	 *     <dt><code>paylod</code></dt>    <dd>The raw JWS payload. </dd> <strong>required</strong>
	 *     <dt><code>url</code></dt>    <dd>The URL for the webhook.  If present it must match the URL registered for the webhook.</dd>
	 * @param  $authentication Object that contains the API public and private keys.  If null the values of the static
	 *         Simplify::$publicKey and Simplify::$privateKey will be used.
	 * @return Payments_Event an Event object.
	 * @throws InvalidArgumentException
	 */
	static public function createEvent($hash, $authentication = null) {

		$args = func_get_args();
		$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

		$paymentsApi = new Simplify_PaymentsApi();

		$jsonObject = $paymentsApi->jwsDecode($hash, $authentication);

		if ($jsonObject['event'] == null) {
			throw new InvalidArgumentException("Incorect data in webhook event");
		}

		return  $paymentsApi->convertFromHashToObject($jsonObject['event'], self::getClazz());
	}

	/**
	 * @ignore
	 */
	static public function getClazz() {
		return "Event";
	}
}
