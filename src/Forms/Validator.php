<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Forms;

use Nette;
use Nette\Utils\Strings;
use Nette\Utils\Validators;


/**
 * Common validators.
 */
final class Validator
{
	use Nette\StaticClass;

	public static array $messages = [
		Controls\CsrfProtection::PROTECTION => 'Your session has expired. Please return to the home page and try again.',
		Form::EQUAL => 'Please enter %s.',
		Form::NOT_EQUAL => 'This value should not be %s.',
		Form::FILLED => 'This field is required.',
		Form::BLANK => 'This field should be blank.',
		Form::MIN_LENGTH => 'Please enter at least %d characters.',
		Form::MAX_LENGTH => 'Please enter no more than %d characters.',
		Form::LENGTH => 'Please enter a value between %d and %d characters long.',
		Form::EMAIL => 'Please enter a valid email address.',
		Form::URL => 'Please enter a valid URL.',
		Form::INTEGER => 'Please enter a valid integer.',
		Form::FLOAT => 'Please enter a valid number.',
		Form::MIN => 'Please enter a value greater than or equal to %d.',
		Form::MAX => 'Please enter a value less than or equal to %d.',
		Form::RANGE => 'Please enter a value between %d and %d.',
		Form::MAX_FILE_SIZE => 'The size of the uploaded file can be up to %d bytes.',
		Form::MAX_POST_SIZE => 'The uploaded data exceeds the limit of %d bytes.',
		Form::MIME_TYPE => 'The uploaded file is not in the expected format.',
		Form::IMAGE => 'The uploaded file must be image in format JPEG, GIF, PNG or WebP.',
		Controls\SelectBox::VALID => 'Please select a valid option.',
		Controls\UploadControl::VALID => 'An error occurred during file upload.',
	];


	/**
	 * @internal
	 */
	public static function formatMessage(Rule $rule, bool $withValue = true): string|Nette\HtmlStringable
	{
		$message = $rule->message;
		if ($message instanceof Nette\HtmlStringable) {
			return $message;

		} elseif ($message === null && is_string($rule->validator) && isset(static::$messages[$rule->validator])) {
			$message = static::$messages[$rule->validator];

		} elseif ($message == null) { // intentionally ==
			trigger_error(
				"Missing validation message for control '{$rule->control->getName()}'"
				. (is_string($rule->validator) ? " (validator '{$rule->validator}')." : '.'),
				E_USER_WARNING,
			);
		}

		if ($translator = $rule->control->getForm()->getTranslator()) {
			$message = $translator->translate($message, is_int($rule->arg) ? $rule->arg : null);
		}

		$message = preg_replace_callback('#%(name|label|value|\d+\$[ds]|[ds])#', function (array $m) use ($rule, $withValue, $translator) {
			static $i = -1;
			switch ($m[1]) {
				case 'name': return $rule->control->getName();
				case 'label':
					if ($rule->control instanceof Controls\BaseControl) {
						$caption = $rule->control->getCaption();
						$caption = $caption instanceof Nette\HtmlStringable
							? $caption->getText()
							: ($translator ? $translator->translate($caption) : $caption);
						return rtrim((string) $caption, ':');
					}

					return '';
				case 'value': return $withValue
						? $rule->control->getValue()
						: $m[0];
				default:
					$args = is_array($rule->arg) ? $rule->arg : [$rule->arg];
					$i = (int) $m[1] ? (int) $m[1] - 1 : $i + 1;
					return isset($args[$i])
						? ($args[$i] instanceof Control ? ($withValue ? $args[$i]->getValue() : "%$i") : $args[$i])
						: '';
			}
		}, $message);
		return $message;
	}


	/********************* default validators ****************d*g**/


	/**
	 * Is control's value equal with second parameter?
	 */
	public static function validateEqual(Control $control, $arg): bool
	{
		$value = $control->getValue();
		$values = is_array($value) ? $value : [$value];
		$args = is_array($arg) ? $arg : [$arg];

		foreach ($values as $val) {
			foreach ($args as $item) {
				if ($item instanceof \BackedEnum) {
					$item = $item->value;
				}

				if ((string) $val === (string) $item) {
					continue 2;
				}
			}

			return false;
		}

		return (bool) $values;
	}


	/**
	 * Is control's value not equal with second parameter?
	 */
	public static function validateNotEqual(Control $control, $arg): bool
	{
		return !static::validateEqual($control, $arg);
	}


	/**
	 * Returns argument.
	 */
	public static function validateStatic(Control $control, bool $arg): bool
	{
		return $arg;
	}


	/**
	 * Is control filled?
	 */
	public static function validateFilled(Controls\BaseControl $control): bool
	{
		return $control->isFilled();
	}


	/**
	 * Is control not filled?
	 */
	public static function validateBlank(Controls\BaseControl $control): bool
	{
		return !$control->isFilled();
	}


	/**
	 * Is control valid?
	 */
	public static function validateValid(Controls\BaseControl $control): bool
	{
		return $control->getRules()->validate();
	}


	/**
	 * Is a control's value number in specified range?
	 */
	public static function validateRange(Control $control, array $range): bool
	{
		$range = array_map(fn($v) => $v === '' ? null : $v, $range);
		return Validators::isInRange($control->getValue(), $range);
	}


	/**
	 * Is a control's value number greater than or equal to the specified minimum?
	 */
	public static function validateMin(Control $control, $minimum): bool
	{
		return Validators::isInRange($control->getValue(), [$minimum === '' ? null : $minimum, null]);
	}


	/**
	 * Is a control's value number less than or equal to the specified maximum?
	 */
	public static function validateMax(Control $control, $maximum): bool
	{
		return Validators::isInRange($control->getValue(), [null, $maximum === '' ? null : $maximum]);
	}


	/**
	 * Count/length validator. Range is array, min and max length pair.
	 */
	public static function validateLength(Control $control, array|int $range): bool
	{
		if (!is_array($range)) {
			$range = [$range, $range];
		}

		$value = $control->getValue();
		return Validators::isInRange(is_array($value) ? count($value) : Strings::length((string) $value), $range);
	}


	/**
	 * Has control's value minimal count/length?
	 */
	public static function validateMinLength(Control $control, $length): bool
	{
		return static::validateLength($control, [$length, null]);
	}


	/**
	 * Is control's value count/length in limit?
	 */
	public static function validateMaxLength(Control $control, $length): bool
	{
		return static::validateLength($control, [null, $length]);
	}


	/**
	 * Has been button pressed?
	 */
	public static function validateSubmitted(Controls\SubmitButton $control): bool
	{
		return $control->isSubmittedBy();
	}


	/**
	 * Is control's value valid email address?
	 */
	public static function validateEmail(Control $control): bool
	{
		return Validators::isEmail((string) $control->getValue());
	}


	/**
	 * Is control's value valid URL?
	 */
	public static function validateUrl(Control $control): bool
	{
		$value = (string) $control->getValue();
		if (Validators::isUrl($value)) {
			return true;
		}

		$value = "https://$value";
		if (Validators::isUrl($value)) {
			$control->setValue($value);
			return true;
		}

		return false;
	}


	/**
	 * Does the control's value match the regular expression?
	 * Case-sensitive to comply with the HTML5 <input /> pattern attribute behaviour
	 */
	public static function validatePattern(Control $control, string $pattern, bool $caseInsensitive = false): bool
	{
		$regexp = "\x01^(?:$pattern)$\x01Du" . ($caseInsensitive ? 'i' : '');
		foreach (static::toArray($control->getValue()) as $item) {
			$value = $item instanceof Nette\Http\FileUpload ? $item->getName() : $item;
			if (!Strings::match((string) $value, $regexp)) {
				return false;
			}
		}

		return true;
	}


	public static function validatePatternCaseInsensitive(Control $control, string $pattern): bool
	{
		return self::validatePattern($control, $pattern, true);
	}


	/**
	 * Is a control's value numeric?
	 */
	public static function validateNumeric(Control $control): bool
	{
		$value = $control->getValue();
		return (is_int($value) && $value >= 0)
			|| (is_string($value) && Strings::match($value, '#^\d+$#D'));
	}


	/**
	 * Is a control's value decimal number?
	 */
	public static function validateInteger(Control $control): bool
	{
		if (
			Validators::isNumericInt($value = $control->getValue())
			&& !is_float($tmp = $value * 1) // too big for int?
		) {
			$control->setValue($tmp);
			return true;
		}

		return false;
	}


	/**
	 * Is a control's value float number?
	 */
	public static function validateFloat(Control $control): bool
	{
		$value = $control->getValue();
		if (is_string($value)) {
			$value = str_replace([' ', ','], ['', '.'], $value);
		}

		if (Validators::isNumeric($value)) {
			$control->setValue((float) $value);
			return true;
		}

		return false;
	}


	/**
	 * Is file size in limit?
	 */
	public static function validateFileSize(Controls\UploadControl $control, $limit): bool
	{
		foreach (static::toArray($control->getValue()) as $file) {
			if ($file->getSize() > $limit || $file->getError() === UPLOAD_ERR_INI_SIZE) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Has file specified mime type?
	 * @param  string|string[]  $mimeType
	 */
	public static function validateMimeType(Controls\UploadControl $control, $mimeType): bool
	{
		$mimeTypes = is_array($mimeType) ? $mimeType : explode(',', $mimeType);
		foreach (static::toArray($control->getValue()) as $file) {
			$type = strtolower($file->getContentType());
			if (!in_array($type, $mimeTypes, true) && !in_array(preg_replace('#/.*#', '/*', $type), $mimeTypes, true)) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Is file image?
	 */
	public static function validateImage(Controls\UploadControl $control): bool
	{
		foreach (static::toArray($control->getValue()) as $file) {
			if (!$file->isImage()) {
				return false;
			}
		}

		return true;
	}


	private static function toArray($value): array
	{
		return is_object($value) ? [$value] : (array) $value;
	}
}
