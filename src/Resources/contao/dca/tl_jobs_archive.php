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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use ManiaxAtWork\ContaoJobs;
use ManiaxAtWork\ContaoJobsBundle\Security\ContaoJobsPermissions;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;


/**
 * Table tl_jobs_archive
 */
$GLOBALS['TL_DCA']['tl_jobs_archive'] = array(

    // Config
    'config'      => array(
        'dataContainer'    => DC_Table::class,
        'ctable'           => array('tl_jobs'),
		'switchToEdit'     => true,
		'enableVersioning' => true,
		'markAsCopy'       => 'title',
        'onload_callback' => array (
			array('tl_jobs_archive', 'checkPermission')
		),
        'oncreate_callback' => array
		(
			array('tl_jobs_archive', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_jobs_archive', 'adjustPermissions')
		),
        'oninvalidate_cache_tags_callback' => array (
			array('tl_jobs_archive', 'addSitemapCacheInvalidationTag'),
		),
        'sql'      => array(
            'keys' => array(
                'id' => 'primary'
            )
        ),
    ),

    'list'        => array(
        'sorting'           => array(
            'mode'        => DataContainer::MODE_SORTED,
            'fields'      => array('title'),
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout' => 'filter;search,limit'
        ),
        'label'             => array(
            'fields' => array('title'),
            'format' => '%s',
        ),
        'global_operations' => array(
            'all' => array(
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations'        => array(
            'edit'   => array(
                'href'  => 'table=tl_jobs',
                'icon'  => 'edit.svg'
            ),
            'editheader' => array(
				'href'  => 'act=edit',
				'icon'  => 'header.svg',
                'button_callback' => array('tl_Jobs_archive', 'editHeader')
			),
            'copy'   => array(
                'href'  => 'act=copy',
                'icon'  => 'copy.svg',
                'button_callback'     => array('tl_jobs_archive', 'copyArchive')
            ),
            'delete' => array(
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
                'button_callback'     => array('tl_jobs_archive', 'deleteArchive')
            ),
            'toggle' => array(
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'showInHeader'        => true
			),
            'show'   => array(
                'href'       => 'act=show',
                'icon'       => 'show.svg'
            ),
        )
    ),
    // Palettes
    'palettes'    => array (
		'__selector__'                => array('protected'),
		'default'                     => '{title_legend},title,jumpTo;{protected_legend:hide},protected'
	),
    // Subpalettes
    'subpalettes' => array(
        'protected'                   => 'groups',
    ),
    // Fields
    'fields'      => array(
        'id'             => array(
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp'         => array(
            'sql' => "int(10) unsigned NOT NULL default 0"
        ),
        'title' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
        'jumpTo' => array (
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
        'protected' => array (
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array (
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		)
    )
);

/**
 * Class tl_jobs_archive
 */
class tl_jobs_archive extends Backend
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
	 * Check permissions to edit table tl_jobs_archive
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		$bundles = System::getContainer()->getParameter('kernel.bundles');

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

        $GLOBALS['TL_DCA']['tl_jobs_archive']['list']['sorting']['root'] = $root;
		$security = System::getContainer()->get('security.helper');

        // Check permissions to add archives
        if (!$security->isGranted(ContaoJobsPermissions::USER_CAN_CREATE_ARCHIVES))
        {
            $GLOBALS['TL_DCA']['tl_jobs_archive']['config']['closed'] = true;
            $GLOBALS['TL_DCA']['tl_jobs_archive']['config']['notCreatable'] = true;
            $GLOBALS['TL_DCA']['tl_jobs_archive']['config']['notCopyable'] = true;
        }

        // Check permissions to delete calendars
        if (!$security->isGranted(ContaoJobsPermissions::USER_CAN_DELETE_ARCHIVES))
        {
            $GLOBALS['TL_DCA']['tl_jobs_archive']['config']['notDeletable'] = true;
        }

        $objSession = System::getContainer()->get('session');

		// Check current action
		switch (Input::get('act'))
		{
			case 'select':
				// Allow
				break;

			case 'create':
				if (!$security->isGranted(ContaoJobsPermissions::USER_CAN_CREATE_ARCHIVES))
				{
					throw new AccessDeniedException('Not enough permissions to create jobs archives.');
				}
				break;

			case 'copy':
			case 'edit':
            case 'delete':
			case 'show':
                if (!in_array(Input::get('id'), $root) || (Input::get('act') == 'delete' && !$security->isGranted(ContaoJobsPermissions::USER_CAN_DELETE_ARCHIVES)))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' jobs archive ID ' . Input::get('id') . '.');
				}
				break;
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'copyAll':
                $session = $objSession->all();

                if (Input::get('act') == 'deleteAll' && !$security->isGranted(ContaoJobsPermissions::USER_CAN_DELETE_ARCHIVES))
                {
                    $session['CURRENT']['IDS'] = array();
                }
                else
                {
                    $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $root);
                }
                $objSession->replace($session);
                break;

            default:
                if (Input::get('act'))
                {
                    throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' jobs archives.');
                }
                break;
        }
	}

    /**
	 * Add the new archive to the permissions
	 *
	 * @param $insertId
	 */
	public function adjustPermissions($insertId)
	{
		// The oncreate_callback passes $insertId as second argument
		if (func_num_args() == 4)
		{
			$insertId = func_get_arg(1);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->jobs) || !is_array($this->User->jobs))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->jobs;
		}

		// The archive is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_jobs_archive']) && in_array($insertId, $arrNew['tl_jobs_archive']))
		{
			// Add the permissions on group level
			if ($this->User->inherit != 'custom')
			{
				$objGroup = $this->Database->execute("SELECT id, jobs, jobp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

				while ($objGroup->next())
				{
					$arrJobp = StringUtil::deserialize($objGroup->jobp);

					if (is_array($arrJobp) && in_array('create', $arrJobp))
					{
						$arrJobs = StringUtil::deserialize($objGroup->jobs, true);
						$arrJobs[] = $insertId;

						$this->Database->prepare("UPDATE tl_user_group SET jobs=? WHERE id=?")
									   ->execute(serialize($arrJobs), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($this->User->inherit != 'group')
			{
				$objUser = $this->Database->prepare("SELECT jobs, jobp FROM tl_user WHERE id=?")
										   ->limit(1)
										   ->execute($this->User->id);

				$arrNewp = StringUtil::deserialize($objUser->jobp);

				if (is_array($arrJobp) && in_array('create', $arrJobp))
				{
					$arrJobs = StringUtil::deserialize($objUser->jobs, true);
					$arrJobs[] = $insertId;

					$this->Database->prepare("UPDATE tl_user SET jobs=? WHERE id=?")
								   ->execute(serialize($arrJobs), $this->User->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$this->User->jobs = $root;
		}
	}

    /**
	 * Return the edit header button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_jobs_archive') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

    /**
	 * Return the copy archive button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function copyArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoJobsPermissions::USER_CAN_CREATE_ARCHIVES) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

    /**
	 * Return the delete archive button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoJobsPermissions::USER_CAN_DELETE_ARCHIVES) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$pageModel = PageModel::findWithDetails($dc->activeRecord->jumpTo);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}
}
