<?php namespace WeAreNotMachines\TogglClient;

use GuzzleHttp\Client AS GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class TogglClient {

	private $http;
	private $apiToken;
	private $request;
	private $response;
	private $workspace;

	public function __construct($token) {
		$this->init($token);
	}

	public function init($token) {
		$this->apiToken = $token;
		$this->http = new GuzzleClient(['base_url'=>['https://www.toggl.com/api/{version}/', ['version'=>'v8']]]);
		$this->headers = [
						"auth"=> [
									$this->apiToken ,"api_token"
								],
						"headers" => [
							"Content-Type" , "application/json"
								]
						];
	}

	public function getUserData() {
		return $this->execute("me")->getResponseBody();
	}

	public function setWorkspace() {
		$data = $this->getUserData();
		$this->workspace = current($data['data']['workspaces']);
		return $this;
	}

	private function execute($endpoint, $data=null) {
		try {
			$this->response = $this->http->get($endpoint, $this->headers);	
			if (!$this->response) {
				throw new \RuntimeException("No response returned from toggle");
			}
		} catch (\RequestException $e) {
			$this->request = $e->getRequest();
		    if ($e->hasResponse()) {
		        $this->response = $e->getResponse();
		    }
		}	
		return $this;
	}

	private function validateResponse() {
		if ($this->response && $this->response->getStatusCode()>=200 && $this->response->getStatusCode()<=400) {
			return true;
		} else {
			return false;
		}
	}

	public function getRequest() {
		return $this->request;
	}

	public function getResponse() {
		return json_encode(["status"=>$this->response->getStatusCode(), "reason"=>$this->response->getReasonPhrase(), "body"=>$this->response->json()], JSON_PRETTY_PRINT);
	}

	public function getResponseBody() {
		if ($this->validateResponse()) {
			return $this->response->json();
		} else {
			return null;
		}
	}

	public function getResponseStatus() {
		if ($this->validateResponse()) {
			return $this->response->getStatusCode();
		} else {
			return null;
		}
	}

	public function getWorkspace() {
		if (empty($this->workspace)) {
			$this->setWorkspace();
		}
		return $this->workspace;
	}

	public function getAllProjects() {
		$workspace = $this->getWorkspace();
		return $this->execute("workspaces/".$this->workspace['id']."/projects")->getResponseBody();
	}

	public function getAllClients() {
		$workspace = $this->getWorkspace();
		return $this->execute("workspaces/".$this->workspace['id']."/clients")->getResponseBody();
	}

	public function getClientsAndProjects() {
		$clients = [];
		foreach ($this->getAllClients() AS $client) {
			$clients[$client['id']] = $client;
		}
		$projects = $this->getAllProjects();

		foreach ($projects AS $project) {
			if (empty($project['cid'])) {
				$project['cid'] = "no client";
			}
			if (empty($clients[$project['cid']]['projects'])) {
				$clients[$project['cid']]['projects'] = [];
			}
			$clients[$project['cid']]['projects'][$project['id']] = $project;
		}
		return $clients;
	}

	public function getCurrentTask($forUser=null) {
		if ($forUser) {
			$this->init($forUser);
		}
		return $this->execute("time_entries/current")->getResponseBody();
	}

	public function getAllUsers() {
		$workspace = $this->getWorkspace();
		return $this->execute("workspaces/".$this->workspace['id']."/workspace_users")->getResponseBody();
	}




}