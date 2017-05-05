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


class Simplify_Subscription extends Simplify_Object {
	/**
	 * Creates an Simplify_Subscription object
	 * @param     array $hash a map of parameters; valid keys are:<dl style="padding-left:10px;">
	 *     <dt><tt>amount</tt></dt>    <dd>Amount of the payment in the smallest unit of your currency. Example: 100 = $1.00USD </dd>
	 *     <dt><tt>billingCycle</tt></dt>    <dd>How the plan is billed to the customer. Values must be AUTO (indefinitely until the customer cancels) or FIXED (a fixed number of billing cycles). [default: AUTO] </dd>
	 *     <dt><tt>billingCycleLimit</tt></dt>    <dd>The number of fixed billing cycles for a plan. Only used if the billingCycle parameter is set to FIXED. Example: 4 </dd>
	 *     <dt><tt>coupon</tt></dt>    <dd>Coupon ID associated with the subscription </dd>
	 *     <dt><tt>currency</tt></dt>    <dd>Currency code (ISO-4217). Must match the currency associated with your account. [default: USD] </dd>
	 *     <dt><tt>customer</tt></dt>    <dd>Customer that is enrolling in the subscription. </dd>
	 *     <dt><tt>frequency</tt></dt>    <dd>Frequency of payment for the plan. Used in conjunction with frequencyPeriod. Valid values are "DAILY", "WEEKLY", "MONTHLY" and "YEARLY". </dd>
	 *     <dt><tt>frequencyPeriod</tt></dt>    <dd>Period of frequency of payment for the plan. Example: if the frequency is weekly, and periodFrequency is 2, then the subscription is billed bi-weekly. </dd>
	 *     <dt><tt>name</tt></dt>    <dd>Name describing subscription </dd>
	 *     <dt><tt>plan</tt></dt>    <dd>The ID of the plan that should be used for the subscription. </dd>
	 *     <dt><tt>quantity</tt></dt>    <dd>Quantity of the plan for the subscription. [min value: 1] </dd>
	 *     <dt><tt>renewalReminderLeadDays</tt></dt>    <dd>If set, how many days before the next billing cycle that a renewal reminder is sent to the customer. If null, then no emails are sent. Minimum value is 7 if set. </dd></dl>
	 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.<i/>
	 * @return    Subscription a Subscription object.
	 */
	static public function createSubscription($hash, $authentication = null) {

		$args = func_get_args();
		$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

		$instance = new Simplify_Subscription();
		$instance->setAll($hash);

		$object = Simplify_PaymentsApi::createObject($instance, $authentication);
		return $object;
	}




	   /**
		* Deletes an Simplify_Subscription object.
		*
		* @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		*/
		public function deleteSubscription($authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

			$obj = Simplify_PaymentsApi::deleteObject($this, $authentication);
			$this->properties = null;
			return true;
		}


	   /**
		* Retrieve Simplify_Subscription objects.
		* @param     array criteria a map of parameters; valid keys are:<dl style="padding-left:10px;">
		*     <dt><tt>filter</tt></dt>    <dd>Filters to apply to the list.  </dd>
		*     <dt><tt>max</tt></dt>    <dd>Allows up to a max of 50 list items to return. [min value: 0, max value: 50, default: 20]  </dd>
		*     <dt><tt>offset</tt></dt>    <dd>Used in paging of the list.  This is the start offset of the page. [min value: 0, default: 0]  </dd>
		*     <dt><tt>sorting</tt></dt>    <dd>Allows for ascending or descending sorting of the list.  The value maps properties to the sort direction (either <tt>asc</tt> for ascending or <tt>desc</tt> for descending).  Sortable properties are: <tt> id</tt><tt> plan</tt>.</dd></dl>
		* @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		* @return    ResourceList a ResourceList object that holds the list of Subscription objects and the total
		*            number of Subscription objects available for the given criteria.
		* @see       ResourceList
		*/
		static public function listSubscription($criteria = null, $authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

			$val = new Simplify_Subscription();
			$list = Simplify_PaymentsApi::listObject($val, $criteria, $authentication);

			return $list;
		}


		/**
		 * Retrieve a Simplify_Subscription object from the API
		 *
		 * @param     string id  the id of the Subscription object to retrieve
		 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		 * @return    Subscription a Subscription object
		 */
		static public function findSubscription($id, $authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

			$val = new Simplify_Subscription();
			$val->id = $id;

			$obj = Simplify_PaymentsApi::findObject($val, $authentication);

			return $obj;
		}


		/**
		 * Updates an Simplify_Subscription object.
		 *
		 * The properties that can be updated:
		 * <dl style="padding-left:10px;">
		 *     <dt><tt>amount</tt></dt>    <dd>Amount of the payment in the smallest unit of your currency. Example: 100 = $1.00USD </dd>
		 *     <dt><tt>billingCycle</tt></dt>    <dd>How the plan is billed to the customer. Values must be AUTO (indefinitely until the customer cancels) or FIXED (a fixed number of billing cycles). [default: AUTO] </dd>
		 *     <dt><tt>billingCycleLimit</tt></dt>    <dd>The number of fixed billing cycles for a plan. Only used if the billingCycle parameter is set to FIXED. Example: 4 </dd>
		 *     <dt><tt>coupon</tt></dt>    <dd>Coupon being assigned to this subscription </dd>
		 *     <dt><tt>currency</tt></dt>    <dd>Currency code (ISO-4217). Must match the currency associated with your account. [default: USD] </dd>
		 *     <dt><tt>frequency</tt></dt>    <dd>Frequency of payment for the plan. Used in conjunction with frequencyPeriod. Valid values are "DAILY", "WEEKLY", "MONTHLY" and "YEARLY". </dd>
		 *     <dt><tt>frequencyPeriod</tt></dt>    <dd>Period of frequency of payment for the plan. Example: if the frequency is weekly, and periodFrequency is 2, then the subscription is billed bi-weekly. [min value: 1] </dd>
		 *     <dt><tt>name</tt></dt>    <dd>Name describing subscription </dd>
		 *     <dt><tt>plan</tt></dt>    <dd>Plan that should be used for the subscription. </dd>
		 *     <dt><tt>prorate</tt></dt>    <dd>Whether to prorate existing subscription. [default: true] <strong>required </strong></dd>
		 *     <dt><tt>quantity</tt></dt>    <dd>Quantity of the plan for the subscription. [min value: 1] </dd>
		 *     <dt><tt>renewalReminderLeadDays</tt></dt>    <dd>If set, how many days before the next billing cycle that a renewal reminder is sent to the customer. If null or 0, no emails are sent. Minimum value is 7 if set. </dd></dl>
		 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		 * @return    Subscription a Subscription object.
		 */
		public function updateSubscription($authentication = null)  {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

			$object = Simplify_PaymentsApi::updateObject($this, $authentication);
			return $object;
		}

	/**
	 * @ignore
	 */
	public function getClazz() {
		return "Subscription";
	}
}
