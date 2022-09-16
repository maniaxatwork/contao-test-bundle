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
use Contao\Frontend;
use Contao\PageModel;
use Contao\UserModel;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\Environment;
use Symfony\Component\HttpFoundation\Request;
use Contao\Image\Image;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Provide methods regarding jobs archives.
 */
class ContaoJobs extends Frontend
{
	/**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();

	/**
	 * Page cache array
	 * @var array
	 */
	private static $arrPageCache = array();

	/**
	 * Add jobs items to the indexer
	 *
	 * @param array   $arrPages
	 * @param integer $intRoot
	 * @param boolean $blnIsSitemap
	 *
	 * @return array
	 */
	public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false)
	{
		$arrRoot = array();

		if ($intRoot > 0)
		{
			$arrRoot = $this->Database->getChildRecords($intRoot, 'tl_page');
		}

		$arrProcessed = array();
		$time = time();

		// Get all jobs archives
		$objArchive = ContaoJobsArchiveModel::findByProtected('');

		// Walk through each archive
		if ($objArchive !== null)
		{
			while ($objArchive->next())
			{
				// Skip jobs archives without target page
				if (!$objArchive->jumpTo)
				{
					continue;
				}

				// Skip jobs archives outside the root nodes
				if (!empty($arrRoot) && !\in_array($objArchive->jumpTo, $arrRoot))
				{
					continue;
				}

				// Get the URL of the jumpTo page
				if (!isset($arrProcessed[$objArchive->jumpTo]))
				{
					$objParent = PageModel::findWithDetails($objArchive->jumpTo);

					// The target page does not exist
					if ($objParent === null)
					{
						continue;
					}

					// The target page has not been published (see #5520)
					if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time))
					{
						continue;
					}

					if ($blnIsSitemap)
					{
						// The target page is protected (see #8416)
						if ($objParent->protected)
						{
							continue;
						}

						// The target page is exempt from the sitemap (see #6418)
						if ($objParent->robots == 'noindex,nofollow')
						{
							continue;
						}
					}

					// Generate the URL
					$arrProcessed[$objArchive->jumpTo] = $objParent->getAbsoluteUrl(Config::get('useAutoItem') ? '/%s' : '/items/%s');
				}

				$strUrl = $arrProcessed[$objArchive->jumpTo];

				// Get the items
				$objArticle = ContaoJobsModel::findPublishedDefaultByPid($objArchive->id);

				if ($objArticle !== null)
				{
					while ($objArticle->next())
					{
						if ($blnIsSitemap && $objArticle->robots === 'noindex,nofollow')
						{
							continue;
						}

						$arrPages[] = $this->getLink($objArticle, $strUrl);
					}
				}
			}
		}

		return $arrPages;
	}

	/**
	 * Generate a URL and return it as string
	 *
	 * @param JobsModel $objItem
	 * @param boolean   $blnAddArchive
	 * @param boolean   $blnAbsolute
	 *
	 * @return string
	 */
	public static function generateJobsUrl($objItem, $blnAddArchive=false, $blnAbsolute=false)
	{
		$strCacheKey = 'id_' . $objItem->id . ($blnAbsolute ? '_absolute' : '');

		// Load the URL from cache
		if (isset(self::$arrUrlCache[$strCacheKey]))
		{
			return self::$arrUrlCache[$strCacheKey];
		}

		// Initialize the cache
		self::$arrUrlCache[$strCacheKey] = null;

		switch ($objItem->source)
		{
			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
				}
				break;
		}

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

			if (!$objPage instanceof PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand(Environment::get('request'));
			}
			else
			{
				$params = (Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->alias ?: $objItem->id);

				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
			}

			// Add the current archive parameter (jobs archive)
			if ($blnAddArchive && Input::get('month'))
			{
				self::$arrUrlCache[$strCacheKey] .= '?month=' . Input::get('month');
			}
		}

		return self::$arrUrlCache[$strCacheKey];
	}

	/**
	 * Return the schema.org data from a jobs article
	 *
	 * @param JobsModel $objArticle
	 *
	 * @return array
	 */
	public static function getSchemaOrgData(ContaoJobsModel $objArticle): array
	{
		$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

		$jsonLd = array(
			'@type' => 'JobPosting',
            'title' => $htmlDecoder->inputEncodedToPlainText($objArticle->headline),
            "employmentType" => $objArticle->id,
			'identifier' => '#/schema/news/' . $objArticle->id,
			'url' => self::generateJobsUrl($objArticle),
			'datePosted' => date('Y-m-d\TH:i:sP', $objArticle->date),

		);

		if ($objArticle->teaser)
		{
			$jsonLd['description'] = $htmlDecoder->htmlToPlainText($objArticle->teaser);
		}
        if ($objArticle->enddate)
		{
			$jsonLd['validThrough'] = date('Y-m-d\TH:i:sP', $objArticle->enddate);
		}

		/** @var UserModel $objAuthor */
		if (($objAuthor = $objArticle->getRelated('author')) instanceof UserModel)
		{
			$jsonLd['hiringOrganization'] = array(
				'@type' => 'Organization',
				'name' => $objArticle->organization,
                'sameAs' => $objArticle->organizationUrl
			);

            $uuid = $objArticle->logo;
            if (null !== $uuid && '' !== $uuid) {
                $image = FilesModel::findByUuid($uuid);

                $img = Image::get($image, 700, 700, 'proportional');
                $jsonLd['hiringOrganization']['logo'] = Environment::get('url').'/'.$img->path;
            }
		}



		return $jsonLd;
	}

	/**
	 * Return the link of a jobs article
	 *
	 * @param JobsModel $objItem
	 * @param string    $strUrl
	 * @param string    $strBase
	 *
	 * @return string
	 */
	protected function getLink($objItem, $strUrl, $strBase='')
	{
		switch ($objItem->source)
		{
			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					return $objTarget->getAbsoluteUrl();
				}
				break;
		}

		// Backwards compatibility (see #8329)
		if ($strBase && !preg_match('#^https?://#', $strUrl))
		{
			$strUrl = $strBase . $strUrl;
		}

		// Link to the default page
		return sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItem->alias ?: $objItem->id));
	}

	/**
	 * Return the page object with loaded details for the given page ID
	 *
	 * @param  integer        $intPageId
	 * @return PageModel|null
	 */
	private function getPageWithDetails($intPageId)
	{
		if (!isset(self::$arrPageCache[$intPageId]))
		{
			self::$arrPageCache[$intPageId] = PageModel::findWithDetails($intPageId);
		}

		return self::$arrPageCache[$intPageId];
	}

	/**
	 * Creates a sub request for the given URI.
	 */
	private function createSubRequest(string $uri, Request $request = null): Request
	{
		$cookies = null !== $request ? $request->cookies->all() : array();
		$server = null !== $request ? $request->server->all() : array();

		unset($server['HTTP_IF_MODIFIED_SINCE'], $server['HTTP_IF_NONE_MATCH']);

		$subRequest = Request::create($uri, 'get', array(), $cookies, array(), $server);

		if (null !== $request)
		{
			if ($request->get('_format'))
			{
				$subRequest->attributes->set('_format', $request->get('_format'));
			}

			if ($request->getDefaultLocale() !== $request->getLocale())
			{
				$subRequest->setLocale($request->getLocale());
			}
		}

		// Always set a session (#3856)
		$subRequest->setSession(new Session(new MockArraySessionStorage()));

		return $subRequest;
	}
}

class_alias(ContaoJobs::class, 'ContaoJobs');
