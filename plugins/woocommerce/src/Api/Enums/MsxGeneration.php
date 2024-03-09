<?php

namespace Automattic\WooCommerce\Api\Enums;

#[Description( 'Generation of MSX computer.' )]
class MsxGeneration {
	#[Description( 'Original MSX' )]
	public const MSX1 = 'MSX';

	public const MSX2 = 'MSX2';

	public const MSX2PLUS = 'MSX2+';

	public const MSXTR = 'MSX Turbo-R';
}
