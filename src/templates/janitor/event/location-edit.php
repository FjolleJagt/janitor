<?php
global $action;
global $model;

$location_id = $action[1];
$location = $model->getLocations(array("id" => $location_id));

$country_options = $model->toOptions($this->countries(), "id", "name");

?>

<div class="scene i:scene defaultEdit eventLocationEdit">
	<h1>Edit location</h1>
	<h2><?= $location["location"] ?></h2>

	<?= $JML->editGlobalActions($location, array("modify" => array(
		"status" => false,
		"list" => [
			"url" => "locations"
		],
		"delete" => [
			"url" => "deleteLocation/".$location_id
		],
		"duplicate" => false
	))); ?>

	<div class="item i:defaultEdit">
		<h2>Location</h2>
		<?= $model->formStart("updateLocation/".$location_id, array("class" => "labelstyle:inject")) ?>
			<fieldset>
				<h3>Name and type</h3>
				<?= $model->input("location", array("value" => $location["location"])) ?>
				<?= $model->input("location_type", array("value" => $location["location_type"])) ?>
			</fieldset>

			<fieldset class="online">
				<h3>Online details</h3>
				<?= $model->input("location_url", array("value" => $location["location_url"])) ?>
			</fieldset>

			<fieldset class="offline">
				<h3>Physical details</h3>
				<?= $model->input("location_address1", array("value" => $location["location_address1"])) ?>
				<?= $model->input("location_address2", array("value" => $location["location_address2"])) ?>
				<?= $model->input("location_city", array("value" => $location["location_city"])) ?>
				<?= $model->input("location_postal", array("value" => $location["location_postal"])) ?>
				<?= $model->input("location_country", array("type" => "select", "value" => $location["location_country"], "options" => $country_options)) ?>
				<?= $model->input("location_googlemaps", array("value" => $location["location_googlemaps"])) ?>
			</fieldset>

			<fieldset class="comment">
				<h3>Comment</h3>
				<?= $model->input("location_comment", array("class" => "autoexpand short", "value" => $location["location_comment"])) ?>
			</fieldset>

			<?= $JML->editActions($location) ?>
		<?= $model->formEnd() ?>
	</div>

</div>