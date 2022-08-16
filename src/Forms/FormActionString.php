<?php

declare(strict_types=1);

namespace Nette\Forms;

use Nette\HtmlStringable;

/**
 * Special case handling for form action strings.
 */
class FormActionString implements HtmlStringable
{
	/** @var string */
	protected $string;


	public function __construct(string $string)
	{
		$this->string = $string;
	}


	public function __toString(): string
	{
		return strtr($this->string, [
			// Decide ampersands.
			'&amp;' => '&',
		]);
	}
}
