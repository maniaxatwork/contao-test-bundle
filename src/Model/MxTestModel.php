<?php

declare(strict_types=1);

/*
 * This file is part of contao-test-bundle.
 * 
 * (c) Stephan Buder 2022 <stephan@maniax-at-work.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/maniaxatwork/contao-test-bundle
 */

namespace Maniaxatwork\ContaoTestBundle\Model;

use Contao\Model;

/**
 * Class MxTestModel
 *
 * @package Maniaxatwork\ContaoTestBundle\Model
 */
class MxTestModel extends Model
{
    protected static $strTable = 'tl_mx_test';

}

