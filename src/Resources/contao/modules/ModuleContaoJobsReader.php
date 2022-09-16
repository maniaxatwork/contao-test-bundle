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
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Environment;
use Contao\BackendTemplate;
use ManiaxAtWork\ContaoJobsBundle\ModuleContaoJobs;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;

/**
 * Front end module "jobs reader".
 *
 * @property array    $jobs_archives
 */
class ModuleContaoJobsReader extends ModuleContaoJobs
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_jobsreader';

	/**
	 * Display a wildcard in the back end
	 *
	 * @throws InternalServerErrorException
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['jobsreader'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem'))
		{
			Input::setGet('items', Input::get('auto_item'));
		}

		// Return an empty string if "items" is not set (to combine list and reader on same page)
		if (!Input::get('items'))
		{
			return '';
		}

		$this->jobs_archives = $this->sortOutProtected(StringUtil::deserialize($this->jobs_archives));

		if (empty($this->jobs_archives) || !\is_array($this->jobs_archives))
		{
			throw new InternalServerErrorException('The jobs reader ID ' . $this->id . ' has no archives specified.');
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$this->Template->articles = '';

		if ($this->overviewPage)
		{
			$this->Template->referer = PageModel::findById($this->overviewPage)->getFrontendUrl();
			$this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['jobsOverview'];
		}
		else
		{
			trigger_deprecation('contao/jobs-bundle', '4.13', 'If you do not select an overview page in the jobs reader module, the "go back" link will no longer be shown in Contao 5.0.');

			$this->Template->referer = 'javascript:history.go(-1)';
			$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
		}

		// Get the jobs item
		$objArticle = ContaoJobsModel::findPublishedByParentAndIdOrAlias(Input::get('items'), $this->jobs_archives);

		// The jobs item does not exist (see #33)
		if ($objArticle === null)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Redirect if the jobs item has a target URL (see #1498)
		switch ($objArticle->source) {
			case 'internal':
				if ($page = PageModel::findPublishedById($objArticle->jumpTo))
				{
					throw new RedirectResponseException($page->getAbsoluteUrl(), 301);
				}

				throw new InternalServerErrorException('Invalid "jumpTo" value or target page not public');
		}

		// Set the default template
		if (!$this->jobs_template)
		{
			$this->jobs_template = 'jobs_full';
		}

		$arrArticle = $this->parseArticle($objArticle);
		$this->Template->articles = $arrArticle;

		// Overwrite the page metadata (see #2853, #4955 and #87)
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if ($responseContext && $responseContext->has(HtmlHeadBag::class))
		{
			/** @var HtmlHeadBag $htmlHeadBag */
			$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
			$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

			if ($objArticle->pageTitle)
			{
				$htmlHeadBag->setTitle($objArticle->pageTitle); // Already stored decoded
			}
			elseif ($objArticle->headline)
			{
				$htmlHeadBag->setTitle($htmlDecoder->inputEncodedToPlainText($objArticle->headline));
			}

			if ($objArticle->description)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($objArticle->description));
			}
			elseif ($objArticle->teaser)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($objArticle->teaser));
			}

			if ($objArticle->robots)
			{
				$htmlHeadBag->setMetaRobots($objArticle->robots);
			}
		}
	}
}

class_alias(ModuleContaoJobsReader::class, 'ModuleContaoJobsReader');
