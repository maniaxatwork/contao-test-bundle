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

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use ManiaxAtWork\ContaoJobs;
use ManiaxAtWork\ContaoJobsBundle\EventListener\PreviewUrlConvertListener;
use ManiaxAtWork\ContaoJobsModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;

class PreviewUrlConverterListenerTest extends ContaoTestCase
{
    public function testConvertsThePreviewUrl(): void
    {
        $request = new Request();
        $request->query->set('jobs', 1);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $jobsModel = $this->createMock(ContaoJobsModel::class);

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByPk' => $jobsModel]),
            ContaoJobs::class => $this->mockConfiguredAdapter(['generateJobsUrl' => 'http://localhost/jobs/james-wilson-returns.html']),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertSame('http://localhost/jobs/james-wilson-returns.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlConvertEvent(new Request());

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheJobsParameterIsNotSet(): void
    {
        $request = new Request();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoJobsItem(): void
    {
        $request = new Request();
        $request->query->set('jobs', null);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertNull($event->getUrl());
    }
}
