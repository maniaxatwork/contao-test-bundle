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
use Contao\Config;
use Contao\System;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\Environment;
use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\PageNotFoundException;

/**
 * Front end module "jobs archive".
 *
 * @property array  $jobs_archives
 * @property string $jobs_jumpToCurrent
 * @property string $jobs_format
 * @property string $jobs_order
 * @property int    $jobs_readerModule
 */
class ModuleJobsArchive extends ModuleContaoJobs
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_jobsarchive';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['jobsarchive'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->jobs_archives = $this->sortOutProtected(StringUtil::deserialize($this->jobs_archives));

		// No jobs archives available
		if (empty($this->jobs_archives) || !\is_array($this->jobs_archives))
		{
			return '';
		}

		// Show the jobs reader if an item has been selected
		if ($this->jobs_readerModule > 0 && (isset($_GET['items']) || (Config::get('useAutoItem') && isset($_GET['auto_item']))))
		{
			return $this->getFrontendModule($this->jobs_readerModule, $this->strColumn);
		}

		// Hide the module if no period has been selected
		if ($this->jobs_jumpToCurrent == 'hide_module' && !isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['day']))
		{
			return '';
		}

		// Tag the jobs archives (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array_map(static function ($id) { return 'contao.db.tl_jobs_archive.' . $id; }, $this->jobs_archives));
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$limit = null;
		$offset = 0;
		$intBegin = 0;
		$intEnd = 0;

		$intYear = (int) Input::get('year');
		$intMonth = (int) Input::get('month');
		$intDay = (int) Input::get('day');

		// Jump to the current period
		if (!isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['day']) && $this->jobs_jumpToCurrent != 'all_items')
		{
			switch ($this->jobs_format)
			{
				case 'jobs_year':
					$intYear = date('Y');
					break;

				default:
				case 'jobs_month':
					$intMonth = date('Ym');
					break;

				case 'jobs_day':
					$intDay = date('Ymd');
					break;
			}
		}

		// Create the date object
		try
		{
			if ($intYear)
			{
				$strDate = $intYear;
				$objDate = new Date($strDate, 'Y');
				$intBegin = $objDate->yearBegin;
				$intEnd = $objDate->yearEnd;
				$this->headline .= ' ' . date('Y', $objDate->tstamp);
			}
			elseif ($intMonth)
			{
				$strDate = $intMonth;
				$objDate = new Date($strDate, 'Ym');
				$intBegin = $objDate->monthBegin;
				$intEnd = $objDate->monthEnd;
				$this->headline .= ' ' . Date::parse('F Y', $objDate->tstamp);
			}
			elseif ($intDay)
			{
				$strDate = $intDay;
				$objDate = new Date($strDate, 'Ymd');
				$intBegin = $objDate->dayBegin;
				$intEnd = $objDate->dayEnd;
				$this->headline .= ' ' . Date::parse($objPage->dateFormat, $objDate->tstamp);
			}
			elseif ($this->jobs_jumpToCurrent == 'all_items')
			{
				$intBegin = 0; // 1970-01-01 00:00:00
				$intEnd = min(4294967295, PHP_INT_MAX); // 2106-02-07 07:28:15
			}
		}
		catch (\OutOfBoundsException $e)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		$this->Template->articles = array();

		// Split the result
		if ($this->perPage > 0)
		{
			// Get the total number of items
			$intTotal = ContaoJobsModel::countPublishedFromToByPids($intBegin, $intEnd, $this->jobs_archives);

			if ($intTotal > 0)
			{
				$total = $intTotal;

				// Get the current page
				$id = 'page_a' . $this->id;
				$page = Input::get($id) ?? 1;

				// Do not index or cache the page if the page number is outside the range
				if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
				{
					throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
				}

				// Set limit and offset
				$limit = $this->perPage;
				$offset = (max($page, 1) - 1) * $this->perPage;

				// Add the pagination menu
				$objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
				$this->Template->pagination = $objPagination->generate("\n  ");
			}
		}

		// Determine sorting
		$t = ContaoJobsModel::getTable();
		$arrOptions = array();

		switch ($this->jobs_order)
		{
			case 'order_headline_asc':
				$arrOptions['order'] = "$t.headline";
				break;

			case 'order_headline_desc':
				$arrOptions['order'] = "$t.headline DESC";
				break;

			case 'order_random':
				$arrOptions['order'] = "RAND()";
				break;

			case 'order_date_asc':
				$arrOptions['order'] = "$t.date";
				break;

			default:
				$arrOptions['order'] = "$t.date DESC";
		}

		// Get the jobs items
		if (isset($limit))
		{
			$objArticles = ContaoJobsModel::findPublishedFromToByPids($intBegin, $intEnd, $this->jobs_archives, $limit, $offset, $arrOptions);
		}
		else
		{
			$objArticles = ContaoJobsModel::findPublishedFromToByPids($intBegin, $intEnd, $this->jobs_archives, 0, 0, $arrOptions);
		}

		// Add the articles
		if ($objArticles !== null)
		{
			$this->Template->articles = $this->parseArticles($objArticles);
		}

		$this->Template->headline = trim($this->headline);
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['empty'];
	}
}

class_alias(ModuleContaoJobsArchive::class, 'ModuleContaoJobsArchive');
