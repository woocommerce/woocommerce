<?php

namespace Automattic\WooCommerce\Api\Enums;

#[Description( 'Just some ordinal numbers.' )]
class OrdinalNumber {
	#[Description( 'The first ordinal value.' )]
	public const FIRST = 1;

	#[Description( 'The second ordinal value.' )]
	public const SECOND = 2;

	public const THIRD = 3;
}
