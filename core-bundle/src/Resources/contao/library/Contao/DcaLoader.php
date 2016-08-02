<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Loads a set of DCA files
 *
 * The class loads the DCA files of a certain table and stores a combined
 * version in the cache directory.
 *
 * Usage:
 *
 *     $dca = new DcaLoader('tl_user');
 *     $dca->load();
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class DcaLoader extends \Controller
{

	/**
	 * Table name
	 * @var string
	 */
	protected $strTable;


	/**
	 * Store the table name
	 *
	 * @param string $strTable The table name
	 *
	 * @throws \Exception If $strTable is empty
	 */
	public function __construct($strTable)
	{
		if ($strTable == '')
		{
			throw new \Exception('The table name must not be empty');
		}

		parent::__construct();

		$this->strTable = $strTable;
	}


	/**
	 * Load a set of DCA files
	 *
	 * @param boolean $blnNoCache If true, the cache will be bypassed
	 */
	public function load($blnNoCache=false)
	{
		// Return if the data has been loaded already
		if (isset($GLOBALS['loadDataContainer'][$this->strTable]) && !$blnNoCache)
		{
			return;
		}

		$GLOBALS['loadDataContainer'][$this->strTable] = true; // see #6145

		$strCacheDir = \System::getContainer()->getParameter('kernel.cache_dir');

		// Try to load from cache
		if (file_exists($strCacheDir . '/contao/dca/' . $this->strTable . '.php'))
		{
			include $strCacheDir . '/contao/dca/' . $this->strTable . '.php';
		}
		else
		{
			$blnBubble = false;

			try
			{
				foreach (\System::getContainer()->get('contao.resource_locator')->locate('dca/' . $this->strTable . '.php', null, false) as $file)
				{
					try
					{
						include $file;
					}
					catch (\InvalidArgumentException $e)
					{
						$blnBubble = true;
						throw $e;
					}
				}
			}
			catch (\InvalidArgumentException $e)
			{
				// Re-throw the InvalidArgumentException if it has originated from a DCA file
				if ($blnBubble)
				{
					throw $e;
				}
			}
		}

		// HOOK: allow to load custom settings
		if (isset($GLOBALS['TL_HOOKS']['loadDataContainer']) && is_array($GLOBALS['TL_HOOKS']['loadDataContainer']))
		{
			foreach ($GLOBALS['TL_HOOKS']['loadDataContainer'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($this->strTable);
			}
		}

		// Local configuration file
		if (file_exists(TL_ROOT . '/system/config/dcaconfig.php'))
		{
			include TL_ROOT . '/system/config/dcaconfig.php';
		}
	}
}
