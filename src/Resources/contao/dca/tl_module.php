<?php

declare(strict_types=1);

/*
 * This file is part of contao-jobs-bundle.
 *
 * (c) Stephan Buder 2022 <stephan@maniax-at-work.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/maniaxatwork/contao-jobs-bundle
 */

use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\DataContainer;
use ManiaxAtWork\ContaoJobsBundle\Security\ContaoJobsPermissions;
use Contao\System;

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'jobs_format';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobslist']         = '{title_legend},name,headline,type;{config_legend},jobs_archives,jobs_readerModule,numberOfItems,jobs_order,skipFirst,perPage;{template_legend:hide},jobs_metaFields,jobs_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobsreader']       = '{title_legend},name,headline,type;{config_legend},jobs_archives,overviewPage,customLabel;{template_legend:hide},jobs_metaFields,jobs_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobsarchive']      = '{title_legend},name,headline,type;{config_legend},jobs_archives,jobs_readerModule,jobs_format,jobs_order,jobs_jumpToCurrent,perPage;{template_legend:hide},jobs_metaFields,jobs_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobsmenu']         = '{title_legend},name,headline,type;{config_legend},jobs_archives,jobs_showQuantity,jobs_format,jobs_order;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['jobsmenujobs_day'] = '{title_legend},name,headline,type;{config_legend},jobs_archives,jobs_showQuantity,jobs_format,jobs_startDay;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_archives'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_jobs', 'getJobsArchives'),
	'eval'                    => array('multiple'=>true, 'mandatory'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_jumpToCurrent'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('hide_module', 'show_current', 'all_items'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_readerModule'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_jobs', 'getReaderModules'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_metaFields'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('date', 'author'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "varchar(255) COLLATE ascii_bin NOT NULL default 'a:2:{i:0;s:4:\"date\";i:1;s:6:\"author\";}'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_template'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Controller::getTemplateGroup('jobs_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_format'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('jobs_day', 'jobs_month', 'jobs_year'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50 clr', 'submitOnChange'=>true),
	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'jobs_month'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_startDay'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array(0, 1, 2, 3, 4, 5, 6),
	'reference'               => &$GLOBALS['TL_LANG']['DAYS'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "smallint(5) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_order'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_jobs', 'getSortingOptions'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'order_date_desc'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['jobs_showQuantity'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
);

$bundles = System::getContainer()->getParameter('kernel.bundles');

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_module_jobs extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	/**
	 * Get all news archives and return them as array
	 *
	 * @return array
	 */
	public function getJobsArchives()
	{
		if (!$this->User->isAdmin && !is_array($this->User->jobs))
		{
			return array();
		}

		$arrArchives = array();
		$objArchives = $this->Database->execute("SELECT id, title FROM tl_jobs_archive ORDER BY title");
		$security = System::getContainer()->get('security.helper');

		while ($objArchives->next())
		{
			if ($security->isGranted(ContaoJobsPermissions::USER_CAN_EDIT_ARCHIVE, $objArchives->id))
			{
				$arrArchives[$objArchives->id] = $objArchives->title;
			}
		}

		return $arrArchives;
	}

	/**
	 * Get all jobs reader modules and return them as array
	 *
	 * @return array
	 */
	public function getReaderModules()
	{
		$arrModules = array();
		$objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='jobsreader' ORDER BY t.name, m.name");

		while ($objModules->next())
		{
			$arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
		}

		return $arrModules;
	}

	/**
	 * Return the sorting options
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getSortingOptions(DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'jobsmenu')
		{
			return array('order_date_asc', 'order_date_desc');
		}

		return array('order_date_asc', 'order_date_desc', 'order_headline_asc', 'order_headline_desc', 'order_random');
	}
}
