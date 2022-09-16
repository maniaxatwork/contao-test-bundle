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

use Contao\Input;
use Contao\Config;
use Contao\System;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\Environment;
use Contao\BackendTemplate;
use Contao\Model\Collection;
use Contao\CoreBundle\Exception\PageNotFoundException;

/**
 * Front end module "jobs list".
 *
 * @property array  $jobs_archives
 * @property string $jobs_featured
 * @property string $jobs_order
 */
class ModuleJobsList extends ModuleContaoJobs
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_jobslist';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['jobslist'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->jobs_archives = $this->sortOutProtected(StringUtil::deserialize($this->jobs_archives));

		// Return if there are no archives
		if (empty($this->jobs_archives) || !\is_array($this->jobs_archives))
		{
			return '';
		}

		// Show the jobs reader if an item has been selected
		if ($this->jobs_readerModule > 0 && (isset($_GET['items']) || (Config::get('useAutoItem') && isset($_GET['auto_item']))))
		{
			return $this->getFrontendModule($this->jobs_readerModule, $this->strColumn);
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
		$limit = null;
		$offset = (int) $this->skipFirst;

		// Maximum number of items
		if ($this->numberOfItems > 0)
		{
			$limit = $this->numberOfItems;
		}

		// Handle featured jobs
		if ($this->jobs_featured == 'featured')
		{
			$blnFeatured = true;
		}
		elseif ($this->jobs_featured == 'unfeatured')
		{
			$blnFeatured = false;
		}
		else
		{
			$blnFeatured = null;
		}

		$this->Template->articles = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

		// Get the total number of items
		$intTotal = $this->countItems($this->jobs_archives, $blnFeatured);

		if ($intTotal < 1)
		{
			return;
		}

		$total = $intTotal - $offset;

		// Split the results
		if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage))
		{
			// Adjust the overall limit
			if (isset($limit))
			{
				$total = min($limit, $total);
			}

			// Get the current page
			$id = 'page_n' . $this->id;
			$page = Input::get($id) ?? 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}

			// Set limit and offset
			$limit = $this->perPage;
			$offset += (max($page, 1) - 1) * $this->perPage;
			$skip = (int) $this->skipFirst;

			// Overall limit
			if ($offset + $limit > $total + $skip)
			{
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$objArticles = $this->fetchItems($this->jobs_archives, $blnFeatured, ($limit ?: 0), $offset);

		// Add the articles
		if ($objArticles !== null)
		{
			$this->Template->articles = $this->parseArticles($objArticles);
		}

		$this->Template->archives = $this->jobs_archives;
	}

	/**
	 * Count the total matching items
	 *
	 * @param array   $jobsArchives
	 * @param boolean $blnFeatured
	 *
	 * @return integer
	 */
	protected function countItems($jobsArchives, $blnFeatured)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['jobsListCountItems']) && \is_array($GLOBALS['TL_HOOKS']['jobsListCountItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['jobsListCountItems'] as $callback)
			{
				if (($intResult = System::importStatic($callback[0])->{$callback[1]}($jobsArchives, $blnFeatured, $this)) === false)
				{
					continue;
				}

				if (\is_int($intResult))
				{
					return $intResult;
				}
			}
		}

		return ContaoJobsModel::countPublishedByPids($jobsArchives, $blnFeatured);
	}

	/**
	 * Fetch the matching items
	 *
	 * @param array   $jobsArchives
	 * @param boolean $blnFeatured
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return Collection|JobsModel|null
	 */
	protected function fetchItems($jobsArchives, $blnFeatured, $limit, $offset)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['jobsListFetchItems']) && \is_array($GLOBALS['TL_HOOKS']['jobsListFetchItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['jobsListFetchItems'] as $callback)
			{
				if (($objCollection = System::importStatic($callback[0])->{$callback[1]}($jobsArchives, $blnFeatured, $limit, $offset, $this)) === false)
				{
					continue;
				}

				if ($objCollection === null || $objCollection instanceof Collection)
				{
					return $objCollection;
				}
			}
		}

		// Determine sorting
		$t = ContaoJobsModel::getTable();
		$order = '';

		if ($this->jobs_featured == 'featured_first')
		{
			$order .= "$t.featured DESC, ";
		}

		switch ($this->jobs_order)
		{
			case 'order_headline_asc':
				$order .= "$t.headline";
				break;

			case 'order_headline_desc':
				$order .= "$t.headline DESC";
				break;

			case 'order_random':
				$order .= "RAND()";
				break;

			case 'order_date_asc':
				$order .= "$t.date";
				break;

			default:
				$order .= "$t.date DESC";
		}

		return ContaoJobsModel::findPublishedByPids($jobsArchives, $blnFeatured, $limit, $offset, array('order'=>$order));
	}
}

class_alias(ModuleContaoJobsList::class, 'ModuleContaoJobsList');
