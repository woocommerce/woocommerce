<?php

/**
 * Stubs for PHPStan.
 */

namespace {
	if ( ! class_exists( \PHPUnit_Framework_Exception::class ) ) {
		/**
		 * Class needed by wordpress-stubs for PHPStan.
		 */
		class PHPUnit_Framework_Exception {}
	}
}

namespace WordPress\AiClient\Providers {
	if ( ! class_exists( \WordPress\AiClient\Providers\AbstractProvider::class ) ) {
		/**
		 * Class needed by wordpress-stubs for PHPStan.
		 */
		abstract class AbstractProvider {
		}
	}
}

namespace WordPress\AiClient\Providers\DTO {
	if ( ! class_exists( \WordPress\AiClient\Providers\DTO\ProviderMetadata::class ) ) {
		/**
		 * Class needed by wordpress-stubs for PHPStan.
		 */
		class ProviderMetadata {
		}
	}
}

namespace WordPress\AiClient\Providers\Models\DTO {
	if ( ! class_exists( \WordPress\AiClient\Providers\Models\DTO\ModelMetadata::class ) ) {
		/**
		 * Class needed by wordpress-stubs for PHPStan.
		 */
		class ModelMetadata {
		}
	}
}

namespace WordPress\AiClient\Providers\Contracts {
	
	if ( ! interface_exists( \WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface::class ) ) {
		/**
		 * Interface needed by wordpress-stubs for PHPStan.
		 */
		interface ProviderAvailabilityInterface {
			public function isConfigured(): bool;
		}
	}

	if ( ! interface_exists( \WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface::class ) ) {
		/**
		 * Interface needed by wordpress-stubs for PHPStan.
		 */
		interface ModelMetadataDirectoryInterface {
			public function listModelMetadata(): array;
			public function hasModelMetadata( string $modelId ): bool;
			public function getModelMetadata( string $modelId ): \WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
		}
	}
	if ( ! interface_exists( \WordPress\AiClient\Providers\Contracts\ModelInterface::class ) ) {
		/**
		 * Interface needed by wordpress-stubs for PHPStan.
		 */
		interface ModelInterface {
		}
	}
}
