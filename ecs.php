<?php

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer;
use PhpCsFixer\Fixer\Whitespace\LineEndingFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
	// A. full sets
	$ecsConfig->sets([SetList::PSR_12]);

	// B. standalone rule
	$ecsConfig->rule(ArrayIndentationFixer::class);
	$ecsConfig->ruleWithConfiguration(ArraySyntaxFixer::class, [
		'syntax' => 'short',
	]);
	$ecsConfig->rule(LineEndingFixer::class);
	$ecsConfig->rule(IndentationTypeFixer::class);
	$ecsConfig->rule(MethodChainingIndentationFixer::class);
};
