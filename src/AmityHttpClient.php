<?php
namespace Kir\Amity;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AmityHttpClient {
	/** @var AmitySignatureGenerator */
	private $signatureGenerator;
	/** @var string */
	private $contactEmail;
	/** @var LoggerInterface */
	private $logger;
	
	/**
	 * @param AmitySignatureGenerator $signatureGenerator
	 * @param string $contactEmail
	 * @param LoggerInterface $logger
	 */
	public function __construct(AmitySignatureGenerator $signatureGenerator, $contactEmail = 'admin@localhost', LoggerInterface $logger = null) {
		$this->signatureGenerator = $signatureGenerator;
		$this->contactEmail = $contactEmail;
		if($logger === null) {
			$logger = new NullLogger();
		}
		$this->logger = $logger;
	}
	
	/**
	 * @param string $cid
	 * @param string $url
	 * @param array $params
	 * @return array
	 */
	public function get($cid, $url, array $params) {
		return $this->request($cid, $url, 'GET', $params, null);
	}
	
	/**
	 * @param string $cid
	 * @param string $url
	 * @param array $params
	 * @param array $data
	 * @return array
	 */
	public function put($cid, $url, array $params, array $data) {
		$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return $this->request($cid, $url, 'PUT', $params, $data);
	}
	
	/**
	 * @param string $cid
	 * @param string $url
	 * @param array $params
	 * @param array $data
	 * @return array
	 */
	public function post($cid, $url, array $params, array $data) {
		$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return $this->request($cid, $url, 'POST', $params, $data);
	}
	
	/**
	 * @param string $cid
	 * @param string $url
	 * @param string $method
	 * @param string $params
	 * @param mixed $body
	 * @return array
	 * @throws Exception
	 */
	private function request($cid, $url, $method, $params, $body = null) {
		$path = parse_url($url, PHP_URL_PATH);
		$signature = $this->signatureGenerator->create($method, $path, $params, $body);
		
		$parameters = http_build_query($params, '', '&');
		$endPoint = $url . ($parameters ? "?{$parameters}" : '');
		
		$this->logger->debug($endPoint, $body !== null ? json_decode($body, true) : []);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endPoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if($body !== null) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, "Amity-Client <{$this->contactEmail}> https://github.com/rkrx/php-dialogue1-amity-client");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: application/json',
			'Content-Type: application/json; charset=UTF-8',
			"X-Client: {$cid}",
			"X-Signature: {$signature}"
		]);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		
		$content = curl_exec($ch);
		$error = curl_errno($ch);
		$code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		
		if($code >= 300) {
			if(strpos($content, '{') === 0) {
				$status = json_decode($content, true);
				$status = array_merge(['status' => -1, 'message' => 'Unknown error'], $status);
				throw new AmityException($status['message'], $status['status']);
			}
		}
		
		if(!strlen($content)) {
			throw new \Exception('Response was empty');
		}
		
		if($error) {
			$curlError = curl_error($ch);
			$curlErrorNo = curl_errno($ch);
			throw new \Exception($curlError, $curlErrorNo);
		}
		
		curl_close($ch);
		
		return json_decode($content, true);
	}
}
