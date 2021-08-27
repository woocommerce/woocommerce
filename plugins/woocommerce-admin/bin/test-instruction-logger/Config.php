
<?php

class Config {

	const CONFIG_FILENAME = '.wca-test-instruction-logger.json';
	const CHANGELOG_FILENAME = 'changelog.txt';
	const REPOSITORY_PATH = 'woocommerce/woocommerce-admin';
	const OUTPUT_FILENAME = 'TESTING-INSTRUCTIONS.md';
	protected $wcRoot = __DIR__ . '/../../';


	public function getOutputFilePath() {
	    return $this->wcRoot . '/' .self::OUTPUT_FILENAME;
	}

	public function getGithubToken() {
		$config = $this->getConfig();
	    if (isset($config->token)) {
	    	return $config->token;
	    }

	    return null;
	}

	public function getGithubCredentials() {
	    $config = $this->getConfig();
	    return array(
	    	'username' => $config->username,
		    'token' => $config->token
	    );
	}

	public function getConfig() {
		$filepath = getenv('HOME') . '/' . self::CONFIG_FILENAME;
		if ( ! file_exists( $filepath ) ) {
			return new stdClass();
		}

		return json_decode( file_get_contents( $filepath ) );
	}

	public function saveGithubToken($username, $token) {
	    $config = $this->getConfig();
	    $config->token = $token;
	    $config->username = $username;
	    return file_put_contents(
	    	getenv('HOME').'/'.self::CONFIG_FILENAME,
		    json_encode($config, JSON_PRETTY_PRINT)
	    );
	}

	public function getChangelogFilepath() {
		return $this->wcRoot . '/' .self::CHANGELOG_FILENAME;
	}

	public function getRepositoryPath() {
	    return self::REPOSITORY_PATH;
	}
}
