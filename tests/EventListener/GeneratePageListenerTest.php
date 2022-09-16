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

namespace ManiaxAtWork\ContaoJobsBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Environment;
use Contao\LayoutModel;
use Contao\Model\Collection;
use ManiaxAtWork\ContaoJobsBundle\EventListener\GeneratePageListener;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;

class GeneratePageListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG'], $GLOBALS['TL_HEAD']);

        parent::tearDown();
    }

    public function testAddsTheJobsFeedLink(): void
    {

        $adapters = [
            Environment::class => $this->mockAdapter(['get']),
            Template::class => new Adapter(Template::class),
        ];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener($this->createMock(PageModel::class), $layoutModel);
    }
}