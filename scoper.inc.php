<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$files_to_output_untouched = (static function (): array {
	$finders = [
		Finder::create()->files()->in('vendor-src')->notName('*.php'),
	];

	$files = [];
	foreach($finders as $finder) {
		foreach ( $finder as $file ) {
			$files[] = $file->getPathName();
		}
	}

	return array_unique($files);
})();

return [
	'on-existing-output-dir' => 'overwrite',
	'output-dir' => 'vendor',
    'prefix' => 'Automattic\WooCommerce\Vendor',
	'files-whitelist' => $files_to_output_untouched,
    'finders' => [
        Finder::create()->files()->in('vendor-src'),
    ],
    'whitelist' => [
	    'League\*',
    ],
	'inverse-namespaces-whitelist' => true,
    'whitelist-global-constants' => true,
    'whitelist-global-classes' => true,
    'whitelist-global-functions' => true,
];
