<?php
namespace Kir\Amity;

class AmitySignatureGenerator {
	/** @var string */
	private $secret;
	
	/**
	 * @param string $secret
	 */
	public function __construct($secret) {
		$this->secret = $secret;
	}
	
	/**
	 * @param string $method
	 * @param string $uri
	 * @param array $params
	 * @param string|null $body
	 * @return string
	 */
	public function create($method, $uri, array $params = array(), $body = null) {
		// make sure the parameters in the query string have a well-defined order and that
		// spaces are escaped as %20 instead of +.
		ksort($params);
		
		$params = http_build_query($params, '', '&');
		$params = str_replace(array(' ', '+'), '%20', $params);
		
		// the payload is what needs to be signed and should include all relevant request data
		$payload = array($method, $uri, $params);
		
		// append the request body, if any, to the payload
		if ($body !== null) {
			$payload[] = $body;
		}
		
		$payload = implode("\n", $payload);
		// $payload now looks like this:
		
		/*
		POST
		/api/v2/contacts/lm9
		abc=xyz&param=foo
		{"contact":{"name1":"Amy","name2":"Pond","email":"amy@tardis.org"}}
		*/
		
		// calculate the HMAC for this payload using the known, good $secret
		return hash_hmac('sha256', $payload, $this->secret);
		// 2.3. Example Code 10 amity REST API, Release 2.8.0
	}
}
