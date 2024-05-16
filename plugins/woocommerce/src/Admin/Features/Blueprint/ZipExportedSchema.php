<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class ZipExportedSchema {
	// exported schema from ExportSchema class
	protected array $schema;

	// Full path for the zip file
	protected string $destination;

	// base path
	protected string $dir;

	// a unique dir name for a single session
	protected string $working_dir;

	protected array $files;

	public function __construct($schema, $destination = null) {
		$this->schema = $schema;

		if ($destination === null) {
			$dir = $this->get_default_destination_dir();
			$this->dir = $dir;
			$this->destination = $dir.'/woo-blueprint.zip';
		}

		$this->working_dir = $this->dir.'/'.date('Ymd').'_'.time();

		if (!class_exists('PclZip')) {
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
		}
	}

	protected function get_working_dir_path($filename) {
		return $this->working_dir . '/' .$filename;
	}

	protected function maybe_create_dir($dir) {
		if (!is_dir($dir)) {
			mkdir($dir);
		}
	}

	public function zip() {

		$this->maybe_create_dir($this->dir);
		$this->maybe_create_dir($this->working_dir);

		// create .json file
		$this->files[] = $this->create_json_schema_file();
		$this->files = array_merge($this->files, $this->add_resource('installPlugins', 'plugins'));
		$this->files = array_merge($this->files, $this->add_resource('installThemes', 'themes'));

		$archive = new \PclZip($this->destination);
		if ($archive->create($this->files, PCLZIP_OPT_REMOVE_PATH, $this->working_dir) == 0) {
			var_dump($this->files);
			die("Error : ".$archive->errorInfo(true));
		}

		$this->clean();

		return $this->destination;
	}

	protected function get_default_destination_dir() {
		return WP_CONTENT_DIR.'/uploads/blueprint';
	}

	protected function find_step($step_name) {
		$step = array_filter($this->schema['steps'], function($step) use ($step_name) {
			return $step['step'] === $step_name;
		});
		if (count($step)) {
			return current($step);
		}
		return null;
	}

	protected function add_resource($step, $type) {

		// check if we have any plugins with resource = self/plugins
		// if not, we should just skip
		$step = $this->find_step($step);
		if ($step === null) {
			return array();
		}

		$resources = array_filter($step[$type], function($plugin) use ($type){
			return $plugin['resource'] === 'self/'.$type;
		});

		if (count($resources) === 0) {
			return array();
		}

		// create 'plugins' dir
		$this->maybe_create_dir($this->working_dir.'/'.$type);

		$files = array();


		foreach ($resources as $resource) {
			$destination = $this->working_dir.'/'.$type.'/'.$resource['slug'].'.zip';
			$plugin_dir = WP_CONTENT_DIR . '/'.$type.'/' . $resource['slug'];
			if (!is_dir($plugin_dir)) {
				// if dir does not exist, see if it's a single file plugin
				$plugin_dir = $plugin_dir.'.php';
				if (!file_exists($plugin_dir)) {
					// @todo handle error
					continue;
				}
			}
			$archive = new \PclZip($destination);
			$result = $archive->create($plugin_dir, PCLZIP_OPT_REMOVE_PATH, WP_CONTENT_DIR.'/'.$type);
			if ($result === 0) {
				var_dump($plugin_dir);

				die($archive->errorInfo(true));
			}
			$files[] = $destination;
		}

		return $files;
	}

	private function create_json_schema_file() {
		$schema_file = $this->get_working_dir_path('woo-blueprint.json');
		file_put_contents($schema_file, json_encode($this->schema, JSON_PRETTY_PRINT));
		return $schema_file;
	}

	private function clean() {
		Util::delete_dir($this->working_dir);
	}
}
