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

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use ManiaxAtWork\ContaoJobsBundle\EventListener\PreviewUrlCreateListener;
use ManiaxAtWork\ContaoJobsModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlCreateListenerTest extends ContaoTestCase
{
    public function testCreatesThePreviewUrl(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $event = new PreviewUrlCreateEvent('jobs', 1);

        $jobsModel = $this->mockClassWithProperties(ContaoJobsModel::class);
        $jobsModel->id = 1;

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByPk' => $jobsModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertSame('jobs=1', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlCreateEvent('jobs', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheJobsParameterIsNotSet(): void
    {
        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheArchiveListPage(): void
    {
        $request = new Request();
        $request->query->set('table', 'tl_jobs');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlCreateEvent('jobs', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testOverwritesTheIdIfTheArchiveSettingsAreEdited(): void
    {
        $request = new Request();
        $request->query->set('act', 'edit');
        $request->query->set('table', 'tl_jobs');
        $request->query->set('id', 2);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $jobsModel = $this->mockClassWithProperties(ContaoJobsModel::class);
        $jobsModel->id = 2;

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByPk' => $jobsModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlCreateEvent('jobs', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertSame('jobs=2', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfThereIsNoJobsItem(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlCreateEvent('jobs', 0);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }
}
