<?php

add_action(
	'wp_loaded',
	function() {
		require 'api/api.php';
	}
);


