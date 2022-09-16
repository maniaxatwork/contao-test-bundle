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
use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\LayoutModel;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobs;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobsArchiveModel;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobsModel;
use Contao\PageModel;
use Contao\System;

System::loadLanguageFile('tl_content');

/**
 * Table tl_jobs
 */
$GLOBALS['TL_DCA']['tl_jobs'] = array(

    // Config
    'config'      => array(
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_jobs_archive',
        'ctable'           => array('tl_content'),
		'switchToEdit'     => true,
		'enableVersioning' => true,
		'markAsCopy'       => 'headline',
        'onload_callback' => array (
			array('tl_jobs', 'checkPermission')
		),
        'oninvalidate_cache_tags_callback' => array (
			array('tl_jobs', 'addSitemapCacheInvalidationTag'),
		),
        'sql'      => array(
            'keys' => array(
                'id' => 'primary',
				'alias' => 'index',
				'pid,published,featured,start,stop' => 'index'
            )
        ),
    ),

    'list'        => array(
        'sorting'           => array(
            'mode'        => DataContainer::MODE_PARENT,
            'fields'      => array('date'),
            'headerFields'=> array('title', 'jumpTo', 'tstamp', 'protected'),
            'panelLayout' => 'filter;sort,search,limit'
        ),
        'label'             => array(
            'fields' => array('headline', 'date', 'time'),
            'format' => '%s <span style="color:#999;padding-left:3px">[%s %s]</span>',
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
                'href'  => 'act=edit',
                'icon'  => 'edit.svg'
            ),
            'editheader' => array(
				'href'  => 'act=edit',
				'icon'  => 'header.svg'
			),
            'copy'   => array(
                'href'  => 'act=paste&amp;mode=copy',
                'icon'  => 'copy.svg'
            ),
            'cut' => array
			(
				'href'  => 'act=paste&amp;mode=cut',
				'icon'  => 'cut.svg'
			),
            'delete' => array(
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
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
    'palettes'    => array(
        '__selector__' => array('source', 'addImage', 'overwriteMeta'),
        'default'      => '{title_legend},headline,alias,author;{date_legend},date;{source_legend:hide},source;{meta_legend},pageTitle,robots,description,serpPreview;{teaser_legend},subheadline,teaser;{image_legend},addImage;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
		'internal'     => '{title_legend},headline,alias,author;{date_legend},date;{source_legend},source,jumpTo;{teaser_legend},subheadline,teaser;{image_legend},addImage;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
	),
    // Subpalettes
    'subpalettes' => array(
        'addImage'     => 'singleSRC,size,floating,imagemargin,fullsize,overwriteMeta',
        'overwriteMeta'=> 'alt,imageTitle,imageUrl,caption'
    ),
    // Fields
    'fields'      => array(
        'id'             => array(
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array(
			'foreignKey'              => 'tl_jobs_archive.title',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
        'tstamp'         => array(
            'sql' => "int(10) unsigned NOT NULL default 0"
        ),
        'headline' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
        'alias' => array(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_jobs', 'generateAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'author' => array (
			'default'                 => BackendUser::getInstance()->id,
			'exclude'                 => true,
			'search'                  => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('doNotCopy'=>true, 'chosen'=>true, 'mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
        'date' => array (
			'default'                 => time(),
			'exclude'                 => true,
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'mandatory'=>true, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'load_callback' => array
			(
				array('tl_jobs', 'loadDate')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
        'pageTitle' => array (
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
        'robots' => array (
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'),
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption' => true),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
        'description' => array (
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'serpPreview' => array (
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
			'exclude'                 => true,
			'inputType'               => 'serpPreview',
			'eval'                    => array('url_callback'=>array('tl_jobs', 'getSerpUrl'), 'title_tag_callback'=>array('tl_jobs', 'getTitleTag'), 'titleFields'=>array('pageTitle', 'headline'), 'descriptionFields'=>array('description', 'teaser')),
			'sql'                     => null
		),
        'subheadline' => array (
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'teaser' => array (
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'addImage' => array (
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'overwriteMeta' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'singleSRC' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'extensions'=>'%contao.image.valid_extensions%', 'mandatory'=>true),
			'sql'                     => "binary(16) NULL"
		),
		'alt' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['alt'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imageTitle' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'size' => array (
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'options_callback' => static function ()
			{
				return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'imagemargin' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imagemargin'],
			'exclude'                 => true,
			'inputType'               => 'trbl',
			'options'                 => array('px', '%', 'em', 'rem'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'imageUrl' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'fullsize' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'caption' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['caption'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'floating' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_content']['floating'],
			'exclude'                 => true,
			'inputType'               => 'radioTable',
			'options'                 => array('above', 'left', 'right', 'below'),
			'eval'                    => array('cols'=>4, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(12) NOT NULL default 'above'"
		),
        'jumpTo' => array (
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'articleId' => array (
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_jobs', 'getArticleAlias'),
			'eval'                    => array('chosen'=>true, 'mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('table'=>'tl_article', 'type'=>'hasOne', 'load'=>'lazy'),
		),
		'url' => array (
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'target' => array (
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'cssClass' => array (
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
        'published' => array (
			'exclude'                 => true,
			'toggle'                  => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'start' => array (
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array (
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
    )
);

/**
 * Class tl_jobs
 */
class tl_jobs extends Backend
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
	 * Check permissions to edit table tl_jobs
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

		$id = strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

		// Check current action
		switch (Input::get('act'))
		{
			case 'paste':
			case 'select':
				// Check CURRENT_ID here (see #247)
				if (!in_array(CURRENT_ID, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access jobs archive ID ' . $id . '.');
				}
				break;

			case 'create':
				if (!Input::get('pid') || !in_array(Input::get('pid'), $root))
				{
					throw new AccessDeniedException('Not enough permissions to create job items in jobs archive ID ' . Input::get('pid') . '.');
				}
				break;

			case 'cut':
			case 'copy':
				if (Input::get('act') == 'cut' && Input::get('mode') == 1)
				{
					$objArchive = $this->Database->prepare("SELECT pid FROM tl_jobs WHERE id=?")
												 ->limit(1)
												 ->execute(Input::get('pid'));

					if ($objArchive->numRows < 1)
					{
						throw new AccessDeniedException('Invalid job item ID ' . Input::get('pid') . '.');
					}

					$pid = $objArchive->pid;
				}
				else
				{
					$pid = Input::get('pid');
				}

				if (!in_array($pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' job item ID ' . $id . ' to jobs archive ID ' . $pid . '.');
				}
				// no break

			case 'edit':
			case 'show':
			case 'delete':
			case 'toggle':
				$objArchive = $this->Database->prepare("SELECT pid FROM tl_jobs WHERE id=?")
											 ->limit(1)
											 ->execute($id);

				if ($objArchive->numRows < 1)
				{
					throw new AccessDeniedException('Invalid jobs item ID ' . $id . '.');
				}

				if (!in_array($objArchive->pid, $root))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' jobs item ID ' . $id . ' of jobs archive ID ' . $objArchive->pid . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access jobs archive ID ' . $id . '.');
				}

				$objArchive = $this->Database->prepare("SELECT id FROM tl_jobs WHERE pid=?")
											 ->execute($id);

				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
				}

				if (!in_array($id, $root))
				{
					throw new AccessDeniedException('Not enough permissions to access jobs archive ID ' . $id . '.');
				}
				break;
		}
	}

    /**
	 * Auto-generate the job alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = function (string $alias) use ($dc): bool
		{
			return $this->Database->prepare("SELECT id FROM tl_jobs WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
		};

		// Generate alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->headline, ContaoJobsArchiveModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
		}
		elseif (preg_match('/^[1-9]\d*$/', $varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
		}
		elseif ($aliasExists($varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}

    /**
	 * Set the timestamp to 00:00:00 (see #26)
	 *
	 * @param integer $value
	 *
	 * @return integer
	 */
	public function loadDate($value) {
		return strtotime(date('Y-m-d', $value) . ' 00:00:00');
	}

    /**
	 * Return the SERP URL
	 *
	 * @param ContaoJobsModel $model
	 *
	 * @return string
	 */
	public function getSerpUrl(ContaoJobsModel $model) {
		return ContaoJobs::generateJobsUrl($model, false, true);
	}

    /**
	 * Return the title tag from the associated page layout
	 *
	 * @param ContaoJobsModel $model
	 *
	 * @return string
	 */
	public function getTitleTag(ContaoJobsModel $model)
	{
		/** @var ContaoJobsArchiveModel $archive */
		if (!$archive = $model->getRelated('pid'))
		{
			return '';
		}

		/** @var PageModel $page */
		if (!$page = $archive->getRelated('jumpTo'))
		{
			return '';
		}

		$page->loadDetails();

		/** @var LayoutModel $layout */
		if (!$layout = $page->getRelated('layout'))
		{
			return '';
		}

		$origObjPage = $GLOBALS['objPage'] ?? null;

		// Override the global page object, so we can replace the insert tags
		$GLOBALS['objPage'] = $page;

		$title = implode(
			'%s',
			array_map(
				static function ($strVal)
				{
					return str_replace('%', '%%', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strVal));
				},
				explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2)
			)
		);

		$GLOBALS['objPage'] = $origObjPage;

		return $title;
	}

    /**
	 * Get all articles and return them as array
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getArticleAlias(DataContainer $dc)
	{
		$arrPids = array();
		$arrAlias = array();

		if (!$this->User->isAdmin)
		{
			foreach ($this->User->pagemounts as $id)
			{
				$arrPids[] = array($id);
				$arrPids[] = $this->Database->getChildRecords($id, 'tl_page');
			}

			if (!empty($arrPids))
			{
				$arrPids = array_merge(...$arrPids);
			}
			else
			{
				return $arrAlias;
			}

			$objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(" . implode(',', array_map('\intval', array_unique($arrPids))) . ") ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}
		else
		{
			$objAlias = $this->Database->prepare("SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting")
									   ->execute($dc->id);
		}

		if ($objAlias->numRows)
		{
			System::loadLanguageFile('tl_article');

			while ($objAlias->next())
			{
				$arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['COLS'][$objAlias->inColumn] ?: $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
			}
		}

		return $arrAlias;
	}

    /**
	 * Add the source options depending on the allowed fields (see #5498)
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getSourceOptions(DataContainer $dc)
	{
		if ($this->User->isAdmin)
		{
			return array('default', 'internal');
		}

		$security = System::getContainer()->get('security.helper');
		$arrOptions = array('default');

		// Add the "internal" option
		if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_jobs::jumpTo'))
		{
			$arrOptions[] = 'internal';
		}

		// Add the option currently set
		if ($dc->activeRecord && $dc->activeRecord->source)
		{
			$arrOptions[] = $dc->activeRecord->source;
			$arrOptions = array_unique($arrOptions);
		}

		return $arrOptions;
	}

    /**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$archiveModel = ContaoJobsArchiveModel::findByPk($dc->activeRecord->pid);
		$pageModel = PageModel::findWithDetails($archiveModel->jumpTo);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}
}
