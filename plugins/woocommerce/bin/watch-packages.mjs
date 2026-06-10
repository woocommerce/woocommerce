#!/usr/bin/env node
import path from 'node:path';
import { watchComposerPackages } from '@woocommerce/internal-build';

await watchComposerPackages( {
	composerJson: path.join( import.meta.dirname, '..', 'composer.json' ),
} );
