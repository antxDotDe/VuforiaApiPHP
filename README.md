VuforiaApiPHP
=============

Simple Vuforia Cloud API written in PHP.


Usage
-----

Just require vuforiaApi, then create a new VuforiaCloud object with your server access and secret codes
```
require_once 'vuforiaApi.php'

$access = "your server access key";
$secret = "your secret server key";
$api = new VuforiaCloud($access, $secret);
```
then you can push images to the cloud via

```
$api->send("Name of the image", "Image link", Width, "Metadata", active);
```

Metadata and active are optional: ``metadata`` is null by default, ``active`` is true by default.
More methods coming soon.

You can request current targets with
```
$array = $api->list_targets();
```

and check for duplicates related to an id
```
$duplicates = $api->list_dups($id);
```
