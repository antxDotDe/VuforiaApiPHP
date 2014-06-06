<?php

require_once 'SignatureBuilder.php';
require_once 'HTTP/Request2.php';

class VuforiaCloud {
	
	# Cloud server secret and access codes
	private $secret;
	private $access;
	
	# Request object to be used across methods
	private $request;

	public $result;

	# Constants
	const URL = "https://vws.vuforia.com";
	
	public function __construct( $access, $secret ) {
		$this->secret = $secret;
		$this->access = $access;
		$this->result = NULL;
	}
	
# -------------------------------------------------------------------------- #
# Utility methods common to all functions                                    #
# -------------------------------------------------------------------------- #
	
	# Transforms image in Base64 for transmission
	private function getImageAsBase64($file_path) {
		$contents = file_get_contents($file_path);
		if( $contents ) {
			$contents = base64_encode($contents);
		}
		return $contents;
	}

	# Build common headers for the packet
	private function buildHeaders() {
		$sb = new SignatureBuilder();
		$date = new DateTime("now", new DateTimeZone("GMT"));
		$this->request->setHeader('Date', $date->format("D, d M Y H:i:s") . " GMT" );
		$this->request->setHeader("Content-Type", "application/json");
		$this->request->setHeader("Authorization", "VWS " . $this->access . ":" 
			. $sb->tmsSignature($this->request, $this->secret));
	}

	private function buildRequestHeaders() {
		$sb = new SignatureBuilder();
		$date = new DateTime("now", new DateTimeZone("GMT"));
		$this->request->setHeader('Date', $date->format("D, d M Y H:i:s") . " GMT" );
		$this->request->setHeader("Authorization", "VWS " . $this->access . ":" 
			. $sb->tmsSignature($this->request, $this->secret));
	}

# -------------------------------------------------------------------------- #
# Results analyzing methods                                                  #
# -------------------------------------------------------------------------- #

	# Retrieves response body
	private function result_body() {
		if($this->result == NULL) {
			return NULL;
		}
		return $this->result->getBody();
	}
		
	# Retrieves result code
	public function result_code() {
		if($this->result == NULL) {
			return NULL;
		}
		$result = json_decode($this->result_body());
		$result_code = $result->result_code;
		return $result_code;
	}
	
	# Check if last operation was successfull
	public function result_successful() {
		if($this->result == NULL) {
			return false;
		}
		
		$result_code = $this->result_code();
		if($result_code == "Success" || $result_code == "TargetCreated") {
			return true;
		}
		return false;
	}
	
	public function result_get_target() {
		if($this->result == NULL) {
			return NULL;
		}

		$result = json_decode($this->result_body());
		$result_target = $result->target_id;
		return $result_target;
	}

# -------------------------------------------------------------------------- #
# REST request API methods                                                   #
# -------------------------------------------------------------------------- #
	
	# Add a target to Vuforia Cloud
	public function send($name, $image_path, $width, $metadata=NULL, $active=true) {
		$requestPath = "/targets";
		$image = $this->getImageAsBase64($image_path);
		$metadata = base64_encode($metadata);
		if($metadata == NULL) {
			$json = json_encode( array ( 'width' => $width, 
						'name' => $name, 
						'image' => $image,
						'active_flag' => ($active ? "true":"false") ));
		} else {
			$json = json_encode( array ( 'width' => $width,
						'name' => $name,
						'image' => $image,
						'active_flag' => ($active ? "true":"false"),
						'application_metadata' => $metadata));
		}
		$this->request = new HTTP_Request2();
		$this->request->setMethod(HTTP_Request2::METHOD_POST);
		$this->request->setBody($json);
		$this->request->setConfig(array('ssl_verify_peer' => false));
		
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->buildHeaders();
		
		try {
			$this->result = $this->request->send();
			return $this->result;
		} catch( HTTP_Request2_Exception $e) {
			echo "<h2>Fatal error! HTTP_Request2_Exception</h2><p>$e</p>";
			die();
		}
	}
	
	# List targets in Vuforia Cloud
	public function list_targets() {
		$requestPath = "/targets";
		$this->request = new HTTP_Request2();
		$this->request->setMethod(HTTP_Request2::METHOD_GET);
		$this->request->setConfig(array('ssl_verify_peer' => false));
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->buildRequestHeaders();
		try {
			$this->result = $this->request->send();
			$resultBody = $this->result->getBody();
			$json = json_decode($resultBody);
			return $json->results;
		} catch( HTTP_Request2_Exception $e) {
			echo "<h2>Fatal error! HTTP_Request2_Exception</h2><p>$e</p>";
			die();
		}
	}

	public function get_dups($id) {
		$requestPath = "/duplicates/$id";
		$this->request = new HTTP_Request2();
		$this->request->setMethod(HTTP_Request2::METHOD_GET);
		$this->request->setConfig(array('ssl_verify_peer' => false));
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->buildRequestHeaders();
		try {
			$this->result = $this->request->send();
			$resultBody = $this->result->getBody();
			$json = json_decode($resultBody);
			return $json->results;
		} catch( HTTP_Request2_Exception $e) {
			echo "<h2>Fatal error! HTTP_Request2_Exception</h2><p>$e</p>";
			die();
		}
	}
	
	
}




?>
