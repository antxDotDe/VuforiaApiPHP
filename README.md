VuforiaApiPHP
=============

Simple Vuforia Cloud API written in PHP.


Usage
=============

Just require 'vuforiaApi.php', then create a new VuforiaCloud object with your server access and secret codes

$access = "your server access key";
$secret = "your secret server key";
$api = new VuforiaCloud($access, $secret);

then you can push images to the cloud via

$api->send("Name of the image", "Image link", Width, "Metadata", active);

Metadata and active are optional: metadata is null by default, active is true by default.
More methods coming soon.
