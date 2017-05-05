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


class Simplify_PaymentsApi
{

	/**
	 * @ignore
	 */
	public static $methodMap = array(
		'create' => 'POST',
		'delete' => 'DELETE',
		'list' => 'GET',
		'show' => 'GET',
		'update' => 'PUT'
	);

	/**
	 * @ignore
	 */
	static public function createObject($object, $authentication = null)
	{
		$paymentsApi = new Simplify_PaymentsApi();

		$jsonObject = $paymentsApi->execute("create", $object, $authentication);

		$o = $paymentsApi->convertFromHashToObject($jsonObject, $object->getClazz());

		return $o;
	}

	/**
	 * @ignore
	 */
	static public function findObject($object, $authentication = null)
	{
		$paymentsApi = new Simplify_PaymentsApi();

		$jsonObject = $paymentsApi->execute("show", $object, $authentication);
		$o = $paymentsApi->convertFromHashToObject($jsonObject, $object->getClazz());

		return $o;
	}

	/**
	 * @ignore
	 */
	static public function updateObject($object, $authentication = null) {
		$paymentsApi = new Simplify_PaymentsApi();

		$jsonObject = $paymentsApi->execute("update", $object, $authentication);
		$o = $paymentsApi->convertFromHashToObject($jsonObject, $object->getClazz());

		return $o;
	}

	/**
	 * @ignore
	 */
	static public function deleteObject($object, $authentication = null) {
		$paymentsApi = new Simplify_PaymentsApi();

		$jsonObject = $paymentsApi->execute("delete", $object, $authentication);

		return $jsonObject;
	}

	/**
	 * @ignore
	 */
	static public function listObject($object, $criteria = null, $authentication =null) {
		if ($criteria != null) {
			if (isset($criteria['max'])) {
				$object->max = $criteria['max'];
			}
			if (isset($criteria['offset'])) {
				$object->offset = $criteria['offset'];
			}
			if (isset($criteria['sorting'])) {
				$object->sorting = $criteria['sorting'];
			}
			if (isset($criteria['filter'])) {
				$object->filter = $criteria['filter'];
			}
		}

		$paymentsApi = new Simplify_PaymentsApi();
		$jsonObject = $paymentsApi->execute("list", $object, $authentication);

		$ret = new Simplify_ResourceList();
		if (array_key_exists('list', $jsonObject) & is_array($jsonObject['list'])) {
			foreach ($jsonObject['list'] as $obj) {
				array_push($ret->list, $paymentsApi->convertFromHashToObject($obj, $object->getClazz()));
			}
			$ret->total = $jsonObject['total'];
		}

		return $ret;
	}

	/**
	 * @ignore
	 */
	public function convertFromHashToObject($from, $toClazz)
	{
		$clazz = 'stdClass';
		$toClazz = "Simplify_" . $toClazz;
		if ("stdClass" != $toClazz && class_exists("{$toClazz}", false)) {
			$clazz = "{$toClazz}";
		}
		$object = new $clazz();

		foreach ($from as $key => $value) {
			if (is_array($value) && count(array_keys($value))) {
				$newClazz = "Simplify_" . ucfirst($key);
				if (!class_exists($newClazz, false)) {
					$newClazz = 'stdClass';
				}

				$object->$key = $this->convertFromHashToObject($value, $newClazz);
			} else {
				$object->$key = $value;
			}
		}

		return $object;
	}

	/**
	 * @ignore
	 */
	public function getUrl($publicKey, $action, $object)
	{
		$url = $this->fixUrl(Simplify::$apiBaseSandboxUrl);
		if ($this->isLiveKey($publicKey)) {
			$url = $this->fixUrl(Simplify::$apiBaseLiveUrl);
		}
		$url = $this->fixUrl($url) . urlencode(lcfirst($object->getClazz())) . '/';

		$queryParams = array();
		if ($action == "show") {
			$url .= urlencode($object->id);
		} elseif ($action == "list") {
			$queryParams = array_merge($queryParams, array('max' => $object->max, 'offset' => $object->offset));
			if (is_array($object->filter) && count(array_keys($object->filter))) {
				foreach ($object->filter as $key => $value) {
			$queryParams["filter[$key]"] = $value;
				}
			}
			if (is_array($object->sorting) && count(array_keys($object->sorting))) {
				foreach ($object->sorting as $key => $value) {
					$queryParams["sorting[$key]"] = $value;
				}
			}
			$query = http_build_query($queryParams);
			if ($query != '') {
				if (strpos($url, '?', strlen($url)) === false) $url .= '?';
				$url .= $query;
			}

		} elseif ($action == "delete") {
			$url .= urlencode($object->id);
		} elseif ($action == "update") {
			$url .= urlencode($object->id);
		} elseif ($action == "create") {
		}
		return $url;
	}

	/**
	 * @ignore
	 */
	public function getMethod($action)
	{
		if (array_key_exists(strtolower($action), self::$methodMap)) {
			return self::$methodMap[strtolower($action)];
		}
		return 'GET';
	}

	/**
	 * @ignore
	 */
	private function execute($action, $object, $authentication)
	{
		$http = new Simplify_HTTP();

		return $http->apiRequest($this->getUrl($authentication->publicKey, $action, $object), $this->getMethod($action),
			$authentication, json_encode($object->getProperties()));
	}

	/**
	 * @ignore
	 */
	public function jwsDecode($hash, $authentication)
	{
		$http = new Simplify_HTTP();

		$data = $http->jwsDecode($authentication, $hash);

		return json_decode($data, true);
	}

	/**
	 * @ignore
	 */
	private function fixUrl($url)
	{
		if ($this->endsWith($url, '/')) {
			return $url;
		}
		return $url . '/';
	}

	/**
	 * @ignore
	 */
	private function isLiveKey($k) {
		return strpos($k, "lvpb") === 0;
	}

	/**
	 * @ignore
	 */
	private function endsWith($s, $c)
	{
		return substr($s, -strlen($c)) == $c;
	}

	/**
	 * Helper function to build the Authentication object for backwards compatibility.
	 * An array of all the arguments passed to one of the API functions is checked against what
	 * we expect to received.  If it's greater, then we're assuming that the user is using the older way of
	 * passing the keys. i.e as two separate strings.  We take those two string and create the Authentication object
	 *
	 * @ignore
	 * @param $authentication
	 * @param $args
	 * @param $expectedArgCount
	 * @return Simplify_Authentication
	 */
	static function buildAuthenticationObject($authentication = null, $args, $expectedArgCount){

		if(sizeof($args) > $expectedArgCount) {
			$authentication = new Simplify_Authentication($args[$expectedArgCount-1], $args[$expectedArgCount]);
		}

		if ($authentication == null){
			$authentication = new Simplify_Authentication();
		}

		// check that the keys have been set, if not use the global keys
		if ( empty($authentication->publicKey)){
			$authentication->publicKey = Simplify::$publicKey;
		}
		if ( empty($authentication->privateKey)){
			$authentication->privateKey = Simplify::$privateKey;
		}

		return $authentication;
	}

}

