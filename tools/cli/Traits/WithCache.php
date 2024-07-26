<?php

namespace WooCommerce\Dev\CLI\Traits;

/**
 * Implements methods to handle the project-level caching.
 *
 * @package WooCommerce\Dev\CLI\Traits
 */

/**
 * Trait WithCache
 *
 * @package WooCommerce\Dev\CLI\Traits
 */
trait WithCache {
	protected function getCacheDirPath( string $relativeDirPath, bool $create = true ): string {
		$projectRoot   = property_exists( $this, 'rootPath' ) ?
			$this->rootPath
			: dirname( __DIR__, 3 );
		$cacheRootPath = rtrim( $projectRoot, '\\/' ) . '/.cache';
		$cachePath     = $cacheRootPath . '/' . ltrim( $relativeDirPath, '\\/' );

		if ( $create && ! is_dir( $cachePath ) && mkdir( $cachePath, 0777, true ) && ! is_dir( $cachePath ) ) {
			throw new \RuntimeException( "Could not create directory {$cachePath}" );
		}

		return rtrim( $cachePath, '\\/' );
	}
}
