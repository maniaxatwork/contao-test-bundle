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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Input;
use ManiaxAtWork\ContaoJobsBundle;
use Contao\System;

// Dynamically add the permission check and other callbacks
if (Input::get('do') == 'jobs')
{
	array_unshift($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'], array('tl_content_jobs', 'checkPermission'));
	$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('tl_content_jobs', 'generateFeed');
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property ContaoJobs $Jobs
 */
class tl_content_jobs extends Backend
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
	 * Check permissions to edit table tl_content
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set the root IDs
		if (empty($this->User->jobs) || !is_array($this->User->jobs))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->jobs;
		}

		// Check the current action
		switch (Input::get('act'))
		{
			case '': // empty
			case 'paste':
			case 'select':
				// Check access to the news item
				$this->checkAccessToElement(CURRENT_ID, $root, true);
				break;

			case 'create':
				// Check access to the parent element if a content element is created
				$this->checkAccessToElement(Input::get('pid'), $root, (Input::get('mode') == 2));
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				// Check access to the parent element if a content element is moved
				if (in_array(Input::get('act'), array('cutAll', 'copyAll')))
				{
					$this->checkAccessToElement(Input::get('pid'), $root, (Input::get('mode') == 2));
				}

				$objCes = $this->Database->prepare("SELECT id FROM tl_content WHERE ptable='tl_jobs' AND pid=?")
										 ->execute(CURRENT_ID);

				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
				$objSession->replace($session);
				break;

			case 'cut':
			case 'copy':
				// Check access to the parent element if a content element is moved
				$this->checkAccessToElement(Input::get('pid'), $root, (Input::get('mode') == 2));
				// no break

			default:
				// Check access to the content element
				$this->checkAccessToElement(Input::get('id'), $root);
				break;
		}
	}

	/**
	 * Check access to a particular content element
	 *
	 * @param integer $id
	 * @param array   $root
	 * @param boolean $blnIsPid
	 *
	 * @throws AccessDeniedException
	 */
	protected function checkAccessToElement($id, $root, $blnIsPid=false)
	{
		if ($blnIsPid)
		{
			$objArchive = $this->Database->prepare("SELECT a.id, n.id AS nid FROM tl_jobs n, tl_jobs_archive a WHERE n.id=? AND n.pid=a.id")
										 ->limit(1)
										 ->execute($id);
		}
		else
		{
			$objArchive = $this->Database->prepare("SELECT a.id, n.id AS nid FROM tl_content c, tl_jobs n, tl_jobs_archive a WHERE c.id=? AND c.pid=n.id AND n.pid=a.id")
										 ->limit(1)
										 ->execute($id);
		}

		// Invalid ID
		if ($objArchive->numRows < 1)
		{
			throw new AccessDeniedException('Invalid jobs content element ID ' . $id . '.');
		}

		// The jobs archive is not mounted
		if (!in_array($objArchive->id, $root))
		{
			throw new AccessDeniedException('Not enough permissions to modify article ID ' . $objArchive->nid . ' in jobs archive ID ' . $objArchive->id . '.');
		}
	}
}
