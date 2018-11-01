<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Localization;


/**
 * Translator adapter.
 */
interface ITranslator
{

	/**
	 * Translates the given string.
	 * @param  mixed    message
	 * @param  int      plural count
	 * @return string
	 */
	function translate($message, $count = null);
}
