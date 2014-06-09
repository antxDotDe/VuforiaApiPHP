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
	private function build_headers() {
		$sb = new SignatureBuilder();
		$date = new DateTime("now", new DateTimeZone("GMT"));
		$this->request->setHeader('Date', $date->format("D, d M Y H:i:s") . " GMT" );
		$this->request->setHeader("Content-Type", "application/json");
		$this->request->setHeader("Authorization", "VWS " . $this->access . ":" 
			. $sb->tmsSignature($this->request, $this->secret));
	}

	private function build_headers_request() {
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

	# Get last target ID
	public function result_get_target() {
		if($this->result == NULL) {
			return NULL;
		}

		$result = json_decode($this->result_body());
		$result_target = $result->target_id;
		return $result_target;
	}

	# Get the tracking rating of the retrieve result (to be used after retrieve command)
	public function retrieve_result_tracking_rating() {
		if($this->result == NULL) {
			return NULL;
		}
		return $this->result->tracking_rating;
	}

	# Get the active flag of the retrieve result (to be used after retrieve command)
	public function retrieve_result_active_flag() {
		if($this->result == NULL) {
			return NULL;
		}
		return $this->result->active_flag;
	}

	# Get the result width of the retrieve result (to be used after retrieve command)
	public function retrieve_result_width() {
		if($this->result == NULL) {
			return NULL;
		}
		return $this->result->width;
	}

	# Get the name of the retrieve result (to be used after retrieve command)
	public function retrieve_result_name() {
		if($this->result == NULL) {
			return NULL;
		}
		return $this->result->name;
	}

# -------------------------------------------------------------------------- #
# HTTP_Request2 builders                                                     #
# -------------------------------------------------------------------------- #
	public function init_get_request() {
		$this->request = new HTTP_Request2();
		$this->request->setMethod(HTTP_Request2::METHOD_GET);
		$this->request->setConfig(array('ssl_verify_peer' => false));
	}

	public function init_post_request() {
		$this->request = new HTTP_Request1();
		$this->request->setMethod(HTTP_Request2::METHOD_POST);
		$this->request->setConfig(array('ssl_verify_peer' => false));
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
		$this->init_post_request();
		$this->request->setBody($json);
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->build_headers();
		
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
		$this->init_get_request();
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->build_headers_request();
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

	# Retrieve a target from Vuforia cloud
	public function retrieve($id) {
		$requestPath = "/targets/$id";
		$this->init_get_request();
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->build_headers_request();
		try {
			$result = $this->request->send();
			$resultBody = $result->getBody();
			$json = json_decode($resultBody);
			if($json->status == "success") {
				$this->result = $json->target_record;
				return "Success";
				# After this function, you should use one of the following methods:
				# * retrieve_result_name
				# * retrieve_result_width
				# * retrieve_result_active_flag
				# * retrieve_result_tracking_rating
			} else {
				echo "<h3>Error in retrieve</h3>";
				$this->result = NULL;
				return "Failure";
			}
		} catch (HTTP_Request2_Exception $e) {
			echo "<h2>Fatal error! HTTP_Request2_Exception</h2><p>$e</p>";
		}
	}

	# List duplicates for $id
	public function list_dups($id) {
		$requestPath = "/duplicates/$id";
		$this->init_get_request();
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->build_headers_request();
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
