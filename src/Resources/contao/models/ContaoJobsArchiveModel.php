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

use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads and writes jobs archives
 *
 * @property string|integer    $id
 * @property string|integer    $tstamp
 * @property string            $title
 * @property string|integer    $jumpTo
 * @property string|boolean    $protected
 * @property string|array|null $groups
 * @property string|boolean    $allowComments
 * @property string            $notify
 * @property string            $sortOrder
 * @property string|integer    $perPage
 * @property string|boolean    $moderate
 * @property string|boolean    $bbcode
 * @property string|boolean    $requireLogin
 * @property string|boolean    $disableCaptcha
 *
 * @method static ContaoJobsArchiveModel|null findById($id, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findByPk($id, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findByIdOrAlias($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneBy($col, $val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByTstamp($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByTitle($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByJumpTo($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByProtected($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByGroups($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByAllowComments($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByNotify($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneBySortOrder($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByPerPage($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByModerate($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByBbcode($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByRequireLogin($val, array $opt=array())
 * @method static ContaoJobsArchiveModel|null findOneByDisableCaptcha($val, array $opt=array())
 *
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByTitle($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByProtected($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByGroups($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByAllowComments($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByNotify($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findBySortOrder($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByPerPage($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByModerate($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByBbcode($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByRequireLogin($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findByDisableCaptcha($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|ContaoJobsArchiveModel[]|ContaoJobsArchiveModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByAllowComments($val, array $opt=array())
 * @method static integer countByNotify($val, array $opt=array())
 * @method static integer countBySortOrder($val, array $opt=array())
 * @method static integer countByPerPage($val, array $opt=array())
 * @method static integer countByModerate($val, array $opt=array())
 * @method static integer countByBbcode($val, array $opt=array())
 * @method static integer countByRequireLogin($val, array $opt=array())
 * @method static integer countByDisableCaptcha($val, array $opt=array())
 */
class ContaoJobsArchiveModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_jobs_archive';
}

class_alias(ContaoJobsArchiveModel::class, 'ContaoJobsArchiveModel');
