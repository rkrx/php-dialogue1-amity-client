<?php
namespace Kir\Amity;

class AmityEndPoint {
	/** @var string */
	private $url;
	
	/**
	 * @param $url
	 */
	public function __construct($url) {
		$this->url = $url;
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return $this->url;
	}
}
