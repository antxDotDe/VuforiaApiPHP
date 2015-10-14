VuforiaApiPHP
=============

**Important notice:** Development has been discontinued, since I switched my main project from PHP to Ruby on Rails.
I'll accept valid pull requests but I will not develop this myself anymore. Sorry for the inconvenience.

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

To retrieve informations about a target in the cloud, use
```
$api->retrieve($id);
```
Then you can use one of the four methods:
* ``retrieve_result_name`` to get the name
* ``retrieve_result_width`` to get the width
* ``retrieve_result_active_flag`` to get if the target is active
* ``retrieve_result_tracking_rating`` to get the tracking rating (a number between 0 and 5)

Check for duplicates related to an id
```
$duplicates = $api->list_dups($id);
```

It's possible to update targets with:
```
$api->update($id, "Name of the image", "Image link", Width, "Metadata", active);
```

To delete targets use:
```
$api->delete($id);
```
