<?php namespace Fisharebest\Localization\Language;

use Fisharebest\Localization\PluralRule\PluralRule0;
use Fisharebest\Localization\Territory\TerritorySn;

/**
 * Class LanguageXh - Representation of the Wolof language.
 *
 * @author    Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2015 Greg Roach
 * @license   GPLv3+
 */
class LanguageWo extends AbstractLanguage implements LanguageInterface {
	/** {@inheritdoc} */
	public function code() {
		return 'wo';
	}

	/** {@inheritdoc} */
	public function defaultTerritory() {
		return new TerritorySn;
	}

	/** {@inheritdoc} */
	public function pluralRule() {
		return new PluralRule0;
	}
}
