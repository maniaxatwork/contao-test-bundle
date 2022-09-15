<?php

/*
 * This file is part of contao-test-bundle.
 * 
 * (c) Stephan Buder 2022 <stephan@maniax-at-work.de>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/maniaxatwork/contao-test-bundle
 */

use Maniaxatwork\ContaoTestBundle\Model\MxTestModel;

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['test_be_modules']['test_be_module'] = array(
    'tables' => array('tl_mx_test')
);

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_mx_test'] = MxTestModel::class;
