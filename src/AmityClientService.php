<?php
namespace Kir\Amity;

use Generator;

class AmityClientService {
	/** @var AmityHttpClient */
	private $client;
	/** @var AmityEndPoint */
	private $endPoint;
	/** @var string */
	private $cid;
	
	/**
	 * @param AmityHttpClient $client
	 * @param AmityEndPoint $endPoint
	 * @param string $cid
	 */
	public function __construct(AmityHttpClient $client, AmityEndPoint $endPoint, $cid) {
		$this->client = $client;
		$this->endPoint = $endPoint;
		$this->cid = $cid;
	}
	
	/**
	 * @param array $filter
	 * @return Generator|array[]
	 */
	public function getContacts(array $filter = []) {
		$params = array_merge(['size' => 200], $filter);
		$params['page'] = 0;
		do {
			$params['page']++;
			$response = $this->client->get($this->cid, sprintf("%s%s", rtrim($this->endPoint, '/'), "/api/v2/contacts"), $params);
			foreach($response['data'] as $contact) {
				yield $contact;
			}
		} while(count($response));
	}
	
	/**
	 * Fetch a single contact
	 *
	 * @param string $id
	 * @return array
	 */
	public function getContact($id) {
		$response = $this->client->get($this->cid, sprintf("%s%s", rtrim($this->endPoint, '/'), "/api/v2/contacts/{$id}"), []);
		$data = json_decode($response, true);
		return $data;
	}
	
	/**
	 * Create a new contact
	 *
	 * @param array $data
	 * @param array $associatedLists
	 * @param array $events
	 * @return array
	 */
	public function addContact(array $data, array $associatedLists = [], array $events = []) {
		$payload = ['contact' => $data];
		if(count($associatedLists)) {
			$payload['lists'] = $associatedLists;
		}
		if(count($events)) {
			$payload['events'] = $events;
		}
		$response = $this->client->post($this->cid, sprintf("%s%s", rtrim($this->endPoint, '/'), "/api/v2/contacts"), [], $payload);
		if(!isset($response['data']['id'])) {
			throw new AmityException('Invalid response', -1);
		}
		return $response['data'];
	}
	
	/**
	 * Update a contact
	 *
	 * @param int $id
	 * @param array $data
	 * @param array $events
	 * @return array
	 */
	public function updateContact($id, array $data, array $events = []) {
		$data = [
			'contact' => $data,
			'events' => $events
		];
		$response = $this->client->put($this->cid, sprintf("%s%s", rtrim($this->endPoint, '/'), "/api/v2/contacts/{$id}"), [], $data);
		return $response['data'];
	}
	
	/**
	 * @return array
	 */
	public function getLists() {
		$response = $this->client->get($this->cid, sprintf("%s%s", rtrim($this->endPoint, '/'), "/api/v2/lists"), []);
		return $response['data'];
	}
	
	/**
	 * @param string $id
	 * @return array
	 */
	public function getList($id) {
		$response = $this->client->get($this->cid, sprintf("%s%s%s", rtrim($this->endPoint, '/'), "/api/v2/lists/", $id), []);
		return $response['data'];
	}
	
	/**
	 * @param string $label
	 * @return array
	 */
	public function getListIdByLabel($label) {
		$response = $this->client->get($this->cid, sprintf("%s%s", rtrim($this->endPoint, '/'), "/api/v2/lists"), []);
		foreach($response['data'] as $list) {
			if($list['label'] === $label) {
				return $list['id'];
			}
		}
		return null;
	}
}
