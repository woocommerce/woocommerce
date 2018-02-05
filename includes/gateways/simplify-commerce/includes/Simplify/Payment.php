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


class Simplify_Payment extends Simplify_Object {
	/**
	 * Creates an Simplify_Payment object
	 * @param     array $hash a map of parameters; valid keys are:<dl style="padding-left:10px;">
	 *     <dt><tt>amount</tt></dt>    <dd>Amount of the payment (in the smallest unit of your currency). Example: 100 = $1.00USD </dd>
	 *     <dt><tt>authorization</tt></dt>    <dd>The ID of the authorization being used to capture the payment. </dd>
	 *     <dt><tt>card.addressCity</tt></dt>    <dd>City of the cardholder. [max length: 50, min length: 2] </dd>
	 *     <dt><tt>card.addressCountry</tt></dt>    <dd>Country code (ISO-3166-1-alpha-2 code) of residence of the cardholder. [max length: 2, min length: 2] </dd>
	 *     <dt><tt>card.addressLine1</tt></dt>    <dd>Address of the cardholder. [max length: 255] </dd>
	 *     <dt><tt>card.addressLine2</tt></dt>    <dd>Address of the cardholder if needed. [max length: 255] </dd>
	 *     <dt><tt>card.addressState</tt></dt>    <dd>State of residence of the cardholder. For the US, this is a 2-digit USPS code. [max length: 255, min length: 2] </dd>
	 *     <dt><tt>card.addressZip</tt></dt>    <dd>Postal code of the cardholder. The postal code size is between 5 and 9 in length and only contain numbers or letters. [max length: 9, min length: 3] </dd>
	 *     <dt><tt>card.cvc</tt></dt>    <dd>CVC security code of the card. This is the code on the back of the card. Example: 123 </dd>
	 *     <dt><tt>card.expMonth</tt></dt>    <dd>Expiration month of the card. Format is MM. Example: January = 01 [min value: 1, max value: 12] <strong>required </strong></dd>
	 *     <dt><tt>card.expYear</tt></dt>    <dd>Expiration year of the card. Format is YY. Example: 2013 = 13 [min value: 0, max value: 99] <strong>required </strong></dd>
	 *     <dt><tt>card.name</tt></dt>    <dd>Name as it appears on the card. [max length: 50, min length: 2] </dd>
	 *     <dt><tt>card.number</tt></dt>    <dd>Card number as it appears on the card. [max length: 19, min length: 13] <strong>required </strong></dd>
	 *     <dt><tt>currency</tt></dt>    <dd>Currency code (ISO-4217) for the transaction. Must match the currency associated with your account. [default: USD] <strong>required </strong></dd>
	 *     <dt><tt>customer</tt></dt>    <dd>ID of customer. If specified, card on file of customer will be used. </dd>
	 *     <dt><tt>description</tt></dt>    <dd>Free form text field to be used as a description of the payment. This field is echoed back with the payment on any find or list operations. [max length: 1024] </dd>
	 *     <dt><tt>invoice</tt></dt>    <dd>ID of invoice for which this payment is being made. </dd>
	 *     <dt><tt>reference</tt></dt>    <dd>Custom reference field to be used with outside systems. </dd>
	 *     <dt><tt>replayId</tt></dt>    <dd>An identifier that can be sent to uniquely identify a payment request to facilitate retries due to I/O related issues. This identifier must be unique for your account (sandbox or live) across all of your payments. If supplied, we will check for a payment on your account that matches this identifier. If found will attempt to return an identical response of the original request. [max length: 50, min length: 1] </dd>
	 *     <dt><tt>statementDescription.name</tt></dt>    <dd>Merchant name. <strong>required </strong></dd>
	 *     <dt><tt>statementDescription.phoneNumber</tt></dt>    <dd>Merchant contact phone number. </dd>
	 *     <dt><tt>token</tt></dt>    <dd>If specified, card associated with card token will be used. [max length: 255] </dd></dl>
	 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.<i/>
	 * @return    Payment a Payment object.
	 */
	static public function createPayment($hash, $authentication = null) {

		$args = func_get_args();
		$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

		$instance = new Simplify_Payment();
		$instance->setAll($hash);

		$object = Simplify_PaymentsApi::createObject($instance, $authentication);
		return $object;
	}



	   /**
		* Retrieve Simplify_Payment objects.
		* @param     array criteria a map of parameters; valid keys are:<dl style="padding-left:10px;">
		*     <dt><tt>filter</tt></dt>    <dd>Filters to apply to the list.  </dd>
		*     <dt><tt>max</tt></dt>    <dd>Allows up to a max of 50 list items to return. [min value: 0, max value: 50, default: 20]  </dd>
		*     <dt><tt>offset</tt></dt>    <dd>Used in paging of the list.  This is the start offset of the page. [min value: 0, default: 0]  </dd>
		*     <dt><tt>sorting</tt></dt>    <dd>Allows for ascending or descending sorting of the list.  The value maps properties to the sort direction (either <tt>asc</tt> for ascending or <tt>desc</tt> for descending).  Sortable properties are: <tt> dateCreated</tt><tt> createdBy</tt><tt> amount</tt><tt> id</tt><tt> description</tt><tt> paymentDate</tt>.</dd></dl>
		* @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		* @return    ResourceList a ResourceList object that holds the list of Payment objects and the total
		*            number of Payment objects available for the given criteria.
		* @see       ResourceList
		*/
		static public function listPayment($criteria = null, $authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

			$val = new Simplify_Payment();
			$list = Simplify_PaymentsApi::listObject($val, $criteria, $authentication);

			return $list;
		}


		/**
		 * Retrieve a Simplify_Payment object from the API
		 *
		 * @param     string id  the id of the Payment object to retrieve
		 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		 * @return    Payment a Payment object
		 */
		static public function findPayment($id, $authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

			$val = new Simplify_Payment();
			$val->id = $id;

			$obj = Simplify_PaymentsApi::findObject($val, $authentication);

			return $obj;
		}


		/**
		 * Updates an Simplify_Payment object.
		 *
		 * The properties that can be updated:
		 * <dl style="padding-left:10px;"></dl>
		 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		 * @return    Payment a Payment object.
		 */
		public function updatePayment($authentication = null)  {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

			$object = Simplify_PaymentsApi::updateObject($this, $authentication);
			return $object;
		}

	/**
	 * @ignore
	 */
	public function getClazz() {
		return "Payment";
	}
}
