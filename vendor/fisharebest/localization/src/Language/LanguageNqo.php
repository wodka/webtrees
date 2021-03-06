<?php namespace Fisharebest\Localization\Language;

use Fisharebest\Localization\PluralRule\PluralRule0;
use Fisharebest\Localization\Script\ScriptNkoo;
use Fisharebest\Localization\Territory\TerritoryGn;

/**
 * Class LanguageNqo - Representation of the N’Ko language.
 *
 * @author    Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2015 Greg Roach
 * @license   GPLv3+
 */
class LanguageNqo extends AbstractLanguage implements LanguageInterface {
	/** {@inheritdoc} */
	public function code() {
		return 'nqo';
	}

	/** {@inheritdoc} */
	public function defaultScript() {
		return new ScriptNkoo;
	}

	/** {@inheritdoc} */
	public function defaultTerritory() {
		return new TerritoryGn;
	}

	/** {@inheritdoc} */
	public function pluralRule() {
		return new PluralRule0;
	}
}
