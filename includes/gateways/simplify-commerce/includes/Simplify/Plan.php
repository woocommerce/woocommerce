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


class Simplify_Plan extends Simplify_Object {
	/**
	 * Creates an Simplify_Plan object
	 * @param     array $hash a map of parameters; valid keys are:<dl style="padding-left:10px;">
	 *     <dt><tt>amount</tt></dt>    <dd>Amount of payment for the plan in the smallest unit of your currency. Example: 100 = $1.00USD <strong>required </strong></dd>
	 *     <dt><tt>billingCycle</tt></dt>    <dd>How the plan is billed to the customer. Values must be AUTO (indefinitely until the customer cancels) or FIXED (a fixed number of billing cycles). [default: AUTO] </dd>
	 *     <dt><tt>billingCycleLimit</tt></dt>    <dd>The number of fixed billing cycles for a plan. Only used if the billingCycle parameter is set to FIXED. Example: 4 </dd>
	 *     <dt><tt>currency</tt></dt>    <dd>Currency code (ISO-4217) for the plan. Must match the currency associated with your account. [default: USD] <strong>required </strong></dd>
	 *     <dt><tt>frequency</tt></dt>    <dd>Frequency of payment for the plan. Used in conjunction with frequencyPeriod. Valid values are "DAILY", "WEEKLY", "MONTHLY" and "YEARLY". [default: MONTHLY] <strong>required </strong></dd>
	 *     <dt><tt>frequencyPeriod</tt></dt>    <dd>Period of frequency of payment for the plan. Example: if the frequency is weekly, and periodFrequency is 2, then the subscription is billed bi-weekly. [min value: 1, default: 1] <strong>required </strong></dd>
	 *     <dt><tt>name</tt></dt>    <dd>Name of the plan [max length: 50, min length: 2] <strong>required </strong></dd>
	 *     <dt><tt>renewalReminderLeadDays</tt></dt>    <dd>If set, how many days before the next billing cycle that a renewal reminder is sent to the customer. If null, then no emails are sent. Minimum value is 7 if set. </dd>
	 *     <dt><tt>trialPeriod</tt></dt>    <dd>Plan free trial period selection.  Must be Days, Weeks, or Month [default: NONE] <strong>required </strong></dd>
	 *     <dt><tt>trialPeriodQuantity</tt></dt>    <dd>Quantity of the trial period.  Must be greater than 0 and a whole number. [min value: 1] </dd></dl>
	 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.<i/>
	 * @return    Plan a Plan object.
	 */
	static public function createPlan($hash, $authentication = null) {

		$args = func_get_args();
		$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

		$instance = new Simplify_Plan();
		$instance->setAll($hash);

		$object = Simplify_PaymentsApi::createObject($instance, $authentication);
		return $object;
	}




	   /**
		* Deletes an Simplify_Plan object.
		*
		* @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		*/
		public function deletePlan($authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

			$obj = Simplify_PaymentsApi::deleteObject($this, $authentication);
			$this->properties = null;
			return true;
		}


	   /**
		* Retrieve Simplify_Plan objects.
		* @param     array criteria a map of parameters; valid keys are:<dl style="padding-left:10px;">
		*     <dt><tt>filter</tt></dt>    <dd>Filters to apply to the list.  </dd>
		*     <dt><tt>max</tt></dt>    <dd>Allows up to a max of 50 list items to return. [min value: 0, max value: 50, default: 20]  </dd>
		*     <dt><tt>offset</tt></dt>    <dd>Used in paging of the list.  This is the start offset of the page. [min value: 0, default: 0]  </dd>
		*     <dt><tt>sorting</tt></dt>    <dd>Allows for ascending or descending sorting of the list.  The value maps properties to the sort direction (either <tt>asc</tt> for ascending or <tt>desc</tt> for descending).  Sortable properties are: <tt> dateCreated</tt><tt> amount</tt><tt> frequency</tt><tt> name</tt><tt> id</tt>.</dd></dl>
		* @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		* @return    ResourceList a ResourceList object that holds the list of Plan objects and the total
		*            number of Plan objects available for the given criteria.
		* @see       ResourceList
		*/
		static public function listPlan($criteria = null, $authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

			$val = new Simplify_Plan();
			$list = Simplify_PaymentsApi::listObject($val, $criteria, $authentication);

			return $list;
		}


		/**
		 * Retrieve a Simplify_Plan object from the API
		 *
		 * @param     string id  the id of the Plan object to retrieve
		 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		 * @return    Plan a Plan object
		 */
		static public function findPlan($id, $authentication = null) {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

			$val = new Simplify_Plan();
			$val->id = $id;

			$obj = Simplify_PaymentsApi::findObject($val, $authentication);

			return $obj;
		}


		/**
		 * Updates an Simplify_Plan object.
		 *
		 * The properties that can be updated:
		 * <dl style="padding-left:10px;">
		 *     <dt><tt>name</tt></dt>    <dd>Name of the plan. [min length: 2] <strong>required </strong></dd></dl>
		 * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
		 * @return    Plan a Plan object.
		 */
		public function updatePlan($authentication = null)  {

			$args = func_get_args();
			$authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

			$object = Simplify_PaymentsApi::updateObject($this, $authentication);
			return $object;
		}

	/**
	 * @ignore
	 */
	public function getClazz() {
		return "Plan";
	}
}
