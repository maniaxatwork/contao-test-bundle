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

use Maniaxatwork\ContaoTestBundle\Controller\FrontendModule\TestFeModuleController;

/**
 * Backend modules
 */
$GLOBALS['TL_LANG']['MOD']['test_be_modules'] = 'Testmodule Backend';
$GLOBALS['TL_LANG']['MOD']['test_be_module'] = ['Testmodule Backend', 'Das sind Testmodule'];

/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['test_fe_modules'] = 'Testmodule Frontend';
$GLOBALS['TL_LANG']['FMD'][TestFeModuleController::TYPE] = ['Testmodule Frontend', 'Das sind Testmodule'];

