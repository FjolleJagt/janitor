<?php
global $model;

?>
<div class="scene setup i:scene">
	<h1>Janitor maintenance room</h1>

<? 
// initialize new project
if(SETUP_TYPE == "new"): ?>

	<h3>Welcome to Janitor</h3>
	<p>
		This guide will help you finalize your janitor installation for <em><?= PROJECT_PATH ?></em>.
	</p>
	<ul class="actions">
		<li class="check"><a href="/janitor/admin/setup/check" class="button primary">Start</a></li>
	</ul>
	<p class="note">We have pre-populated the fields in the following forms with the most likely values where possible. Don't hesitate to update as needed.</p>

<? 
// set up existing project
else: ?>
	<p>
		You are running setup for an existing project in <em><?= PROJECT_PATH ?></em>. You have two options:
	</p>

	<div class="option">
		<h3>Change configuration</h3>
		<p>
			Choose this option to change core/database/mail configuration.
		</p>
		<ul class="actions">
			<li class="check"><a href="/janitor/admin/setup/check" class="button primary">Edit configuration</a></li>
		</ul>
	</div>

	<div class="option">
		<h3>Upgrade Janitor</h3>
		<p>
			Choose this option to upgrade your project to the latest version of Janitor.
		</p>
		<ul class="actions">
			<li class="check"><a href="/janitor/admin/setup/upgrade" class="button primary">Upgrade Janitor</a></li>
		</ul>
	</div>

<? endif;?>

</div>