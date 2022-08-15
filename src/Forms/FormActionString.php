<?php

namespace Nette\Forms;

use Nette\HtmlStringable;

/**
 * Special case handling for form action strings.
 */
class FormActionString implements HtmlStringable {

  /**
   * @var string
   */
  protected $string;

  /**
   * @param string $string
   */
  public function __construct(string $string) {
    $this->string = $string;
  }

  /**
   * @inheritDoc
   */
  public function __toString(): string {
    return strtr($this->string, [
      // Decide ampersands.
      '&amp;' => '&',
    ]);
  }

}