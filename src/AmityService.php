<?php
namespace Kir\Amity;

class AmityService {
	/** @var AmityHttpClient */
	private $client;
	/** @var AmityEndPoint */
	private $endPoint;
	
	/**
	 * @param AmityHttpClient $client
	 * @param AmityEndPoint $endPoint
	 */
	public function __construct(AmityHttpClient $client, AmityEndPoint $endPoint) {
		$this->client = $client;
		$this->endPoint = $endPoint;
	}
	
	/**
	 * @param string $cid
	 * @return AmityClientService
	 */
	public function withClient($cid) {
		return new AmityClientService($this->client, $this->endPoint, $cid);
	}
}
