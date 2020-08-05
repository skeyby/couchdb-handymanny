<?php

include('./httpful.phar');
use Httpful\Request;

class CouchDB_Connector {

	private $CouchURL;

	function __construct($CouchURL, $CouchUser, $CouchPassword) {

		if (substr($CouchURL, -1) != "/") {
			$this->CouchURL = $CouchURL."/";
		} else {
			$this->CouchURL = $CouchURL;
		}

		$template = \Httpful\Request::init()
					->authenticateWith($CouchUser, $CouchPassword);
		\Httpful\Request::ini($template);

	}


	function pingDB() {

		$response = \Httpful\Request::get($this->CouchURL)->send();

		if (! isset($response->body->couchdb)) {
			return false;
		} else {
			return $response->body;
		}

	}

	function getDB($database) {

		$response = \Httpful\Request::get($this->CouchURL.$database)->send();

		if (isset($response->body->db_name)) {
			return $response->body;
		} else {
			if (isset($response->body->error) && isset($response->body->reason)) {
				return $response->body->error." - ".$response->body->reason;
			} else {
				return false;
			}
		}

	}

	function getDBShards($database) {

		$response = \Httpful\Request::get($this->CouchURL.$database."/_shards")->send();

		if (isset($response->body->shards)) {
			return $response->body;
		} else {
			if (isset($response->body->error) && isset($response->body->reason)) {
				return $response->body->error." - ".$response->body->reason;
			} else {
				return false;
			}
		}

	}

	function getDBPermissions($database) {

		$response = \Httpful\Request::get($this->CouchURL.$database."/_security")->send();

		if (isset($response->body) OR
			isset($response->body->members) OR
			isset($response->body->admins)) {
			return $response->body;
		} else {
			if (isset($response->body->error) && isset($response->body->reason)) {
				return $response->body->error." - ".$response->body->reason;
			} else {
				return false;
			}
		}

	}

	function deleteDB($database) {

		$response = \Httpful\Request::delete($this->CouchURL.$database)->send();

		if (! isset($response->body->ok) OR ($response->body->ok != true)) {
			if (isset($response->body->error) && isset($response->body->reason)) {
				return $response->body->error." - ".$response->body->reason;
			} else {
				return false;
			}
		} else {
			return true;
		}

	}


}