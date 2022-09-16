<?php

/*
 * This file is part of contao-jobs-bundle.
 *
 * (c) Stephan Buder 2022 <stephan@maniax-at-work.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/maniaxatwork/contao-jobs-bundle
 */

namespace ManiaxAtWork\ContaoJobsBundle;

use Contao\Date;
use Contao\Input;
use Contao\System;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Environment;
use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\PageNotFoundException;

/**
 * Front end module "jobs archive".
 *
 * @property int    $jobs_startDay
 * @property bool   $jobs_showQuantity
 * @property array  $jobs_archives
 * @property string $jobs_order
 * @property string $jobs_format
 */
class ModuleContaoJobsMenu extends ModuleContaoJobs
{
	/**
	 * Current date object
	 * @var Date
	 */
	protected $Date;

	/**
	 * Current URL
	 * @var string
	 */
	protected $strUrl;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_jobsmenu';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$container = System::getContainer();
		$request = $container->get('request_stack')->getCurrentRequest();

		if ($request && $container->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['jobsmenu'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl($container->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->jobs_archives = $this->sortOutProtected(StringUtil::deserialize($this->jobs_archives));

		if (empty($this->jobs_archives) || !\is_array($this->jobs_archives))
		{
			return '';
		}

		$this->strUrl = preg_replace('/\?.*$/', '', Environment::get('request'));

		if (($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$this->strUrl = $objTarget->getFrontendUrl();
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		switch ($this->jobs_format)
		{
			case 'jobs_year':
				$this->compileYearlyMenu();
				break;

			default:
			case 'jobs_month':
				$this->compileMonthlyMenu();
				break;
		}

		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];
	}

	/**
	 * Generate the yearly menu
	 */
	protected function compileYearlyMenu()
	{
		$arrData = array();
		$time = Date::floorToMinute();

		// Get the dates
		$objDates = $this->Database->query("SELECT FROM_UNIXTIME(date, '%Y') AS year, COUNT(*) AS count FROM tl_jobs WHERE pid IN(" . implode(',', array_map('\intval', $this->jobs_archives)) . ")" . ((!BE_USER_LOGGED_IN || TL_MODE == 'BE') ? " AND published='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'$time')" : "") . " GROUP BY year ORDER BY year DESC");

		while ($objDates->next())
		{
			$arrData[$objDates->year] = $objDates->count;
		}

		// Sort the data
		($this->jobs_order == 'order_date_asc') ? ksort($arrData) : krsort($arrData);

		$arrItems = array();
		$count = 0;
		$limit = \count($arrData);

		// Prepare the navigation
		foreach ($arrData as $intYear=>$intCount)
		{
			$intDate = $intYear;
			$quantity = sprintf((($intCount < 2) ? $GLOBALS['TL_LANG']['MSC']['entry'] : $GLOBALS['TL_LANG']['MSC']['entries']), $intCount);

			$arrItems[$intYear]['date'] = $intDate;
			$arrItems[$intYear]['link'] = $intYear;
			$arrItems[$intYear]['href'] = $this->strUrl . '?year=' . $intDate;
			$arrItems[$intYear]['title'] = StringUtil::specialchars($intYear . ' (' . $quantity . ')');
			$arrItems[$intYear]['class'] = trim(((++$count == 1) ? 'first ' : '') . (($count == $limit) ? 'last' : ''));
			$arrItems[$intYear]['isActive'] = (Input::get('year') == $intDate);
			$arrItems[$intYear]['quantity'] = $quantity;
		}

		$this->Template->yearly = true;
		$this->Template->items = $arrItems;
		$this->Template->showQuantity = (bool) $this->jobs_showQuantity;
	}

	/**
	 * Generate the monthly menu
	 */
	protected function compileMonthlyMenu()
	{
		$arrData = array();
		$time = Date::floorToMinute();

		// Get the dates
		$objDates = $this->Database->query("SELECT FROM_UNIXTIME(date, '%Y') AS year, FROM_UNIXTIME(date, '%m') AS month, COUNT(*) AS count FROM tl_jobs WHERE pid IN(" . implode(',', array_map('\intval', $this->jobs_archives)) . ")" . ((!BE_USER_LOGGED_IN || TL_MODE == 'BE') ? " AND published='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'$time')" : "") . " GROUP BY year, month ORDER BY year DESC, month DESC");

		while ($objDates->next())
		{
			$arrData[$objDates->year][$objDates->month] = $objDates->count;
		}

		// Sort the data
		foreach (array_keys($arrData) as $key)
		{
			($this->jobs_order == 'order_date_asc') ? ksort($arrData[$key]) : krsort($arrData[$key]);
		}

		($this->jobs_order == 'order_date_asc') ? ksort($arrData) : krsort($arrData);

		$arrItems = array();

		// Prepare the navigation
		foreach ($arrData as $intYear=>$arrMonth)
		{
			$count = 0;
			$limit = \count($arrMonth);

			foreach ($arrMonth as $intMonth=>$intCount)
			{
				$intDate = $intYear . $intMonth;
				$intMonth = (int) $intMonth - 1;

				$quantity = sprintf((($intCount < 2) ? $GLOBALS['TL_LANG']['MSC']['entry'] : $GLOBALS['TL_LANG']['MSC']['entries']), $intCount);

				$arrItems[$intYear][$intMonth]['date'] = $intDate;
				$arrItems[$intYear][$intMonth]['link'] = $GLOBALS['TL_LANG']['MONTHS'][$intMonth] . ' ' . $intYear;
				$arrItems[$intYear][$intMonth]['href'] = $this->strUrl . '?month=' . $intDate;
				$arrItems[$intYear][$intMonth]['title'] = StringUtil::specialchars($GLOBALS['TL_LANG']['MONTHS'][$intMonth] . ' ' . $intYear . ' (' . $quantity . ')');
				$arrItems[$intYear][$intMonth]['class'] = trim(((++$count == 1) ? 'first ' : '') . (($count == $limit) ? 'last' : ''));
				$arrItems[$intYear][$intMonth]['isActive'] = (Input::get('month') == $intDate);
				$arrItems[$intYear][$intMonth]['quantity'] = $quantity;
			}
		}

		$this->Template->items = $arrItems;
		$this->Template->showQuantity = (bool) $this->jobs_showQuantity;
		$this->Template->url = $this->strUrl . '?';
		$this->Template->activeYear = Input::get('year');
	}

	/**
	 * Return the week days and labels as array
	 *
	 * @return array
	 */
	protected function compileDays()
	{
		$arrDays = array();

		for ($i=0; $i<7; $i++)
		{
			$intCurrentDay = ($i + $this->jobs_startDay) % 7;
			$arrDays[$intCurrentDay] = $GLOBALS['TL_LANG']['DAYS'][$intCurrentDay];
		}

		return array_values($arrDays);
	}

	/**
	 * Return all weeks of the current month as array
	 *
	 * @param array $arrData
	 *
	 * @return array
	 */
	protected function compileWeeks($arrData)
	{
		$intDaysInMonth = date('t', $this->Date->monthBegin);
		$intFirstDayOffset = date('w', $this->Date->monthBegin) - $this->jobs_startDay;

		if ($intFirstDayOffset < 0)
		{
			$intFirstDayOffset += 7;
		}

		$intColumnCount = -1;
		$intNumberOfRows = ceil(($intDaysInMonth + $intFirstDayOffset) / 7);
		$arrDays = array();

		// Compile days
		for ($i=1; $i<=($intNumberOfRows * 7); $i++)
		{
			$intWeek = floor(++$intColumnCount / 7);
			$intDay = $i - $intFirstDayOffset;
			$intCurrentDay = ($i + $this->jobs_startDay) % 7;

			$strWeekClass = 'week_' . $intWeek;
			$strWeekClass .= ($intWeek == 0) ? ' first' : '';
			$strWeekClass .= ($intWeek == ($intNumberOfRows - 1)) ? ' last' : '';

			$strClass = ($intCurrentDay < 2) ? ' weekend' : '';
			$strClass .= ($i == 1 || $i == 8 || $i == 15 || $i == 22 || $i == 29 || $i == 36) ? ' col_first' : '';
			$strClass .= ($i == 7 || $i == 14 || $i == 21 || $i == 28 || $i == 35 || $i == 42) ? ' col_last' : '';

			// Empty cell
			if ($intDay < 1 || $intDay > $intDaysInMonth)
			{
				$arrDays[$strWeekClass][$i]['label'] = '&nbsp;';
				$arrDays[$strWeekClass][$i]['class'] = 'days empty' . $strClass;
				$arrDays[$strWeekClass][$i]['events'] = array();

				continue;
			}

			$intKey = date('Ym', $this->Date->tstamp) . ((\strlen($intDay) < 2) ? '0' . $intDay : $intDay);
			$strClass .= ($intKey == date('Ymd')) ? ' today' : '';

			// Inactive days
			if (empty($intKey) || !isset($arrData[$intKey]))
			{
				$arrDays[$strWeekClass][$i]['label'] = $intDay;
				$arrDays[$strWeekClass][$i]['class'] = 'days' . $strClass;
				$arrDays[$strWeekClass][$i]['events'] = array();

				continue;
			}

			$arrDays[$strWeekClass][$i]['label'] = $intDay;
			$arrDays[$strWeekClass][$i]['class'] = 'days active' . $strClass;
			$arrDays[$strWeekClass][$i]['href'] = $this->strUrl . '?day=' . $intKey;
			$arrDays[$strWeekClass][$i]['title'] = sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['jobs_items']), $arrData[$intKey]);
		}

		return $arrDays;
	}
}

class_alias(ModuleContaoJobsMenu::class, 'ModuleContaoJobsMenu');
