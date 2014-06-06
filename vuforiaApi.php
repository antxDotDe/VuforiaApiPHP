<?php

require_once 'SignatureBuilder.php';
require_once 'HTTP/Request2.php';

class VuforiaCloud {
	
	# Cloud server secret and access codes
	private $secret;
	private $access;
	
	# Request object to be used across methods
	private $request;

	# Constants
	const URL = "https://vws.vuforia.com";
	
	public function __construct( $access, $secret ) {
		$this->secret = $secret;
		$this->access = $access;
	}
	
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
	
	# Add a target to Vuforia Cloud
	public function send($name, $image_path, $width, $metadata=NULL, $active=true) {
		$requestPath = "/targets";
		$image = $this->getImageAsBase64($image_path);
		$metadata = base64_encode($metadata);
		if($metadata == NULL) {
			$json = json_encode( array ( 'width' => $width, 
						'name' => $name, 
						'image' => $image,
						'active' => $active ));
		} else {
			$json = json_encode( array ( 'width' => $width,
						'name' => $name,
						'image' => $image,
						'active' => $active,
						'application_metadata' => $metadata));
		}
		$this->request = new HTTP_Request2();
		$this->request->setMethod(HTTP_Request2::METHOD_POST);
		$this->request->setBody($json);
		$this->request->setConfig(array('ssl_verify_peer' => false));
		
		$this->request->setURL(VuforiaCloud::URL . $requestPath);
		$this->buildHeaders();
		
		try {
			$response = $this->request->send();
			return $response;
		} catch( HTTP_Request2_Exception $e) {
			echo "<h2>Fatal error! HTTP_Request2_Exception</h2><p>$e</p>";
			die();
		}
	}
	
	
	

}




?>
