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

use ManiaxAtWork\ContaoJobs;
use ManiaxAtWork\ContaoJobsBundle\EventListener\InsertTagsListener;
use ManiaxAtWork\ContaoJobsFeedModel;
use ManiaxAtWork\ContaoJobsModel;
use Contao\TestCase\ContaoTestCase;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testReplacesTheJobsTags(): void
    {
        $jobsModel = $this->mockClassWithProperties(ContaoJobsModel::class);
        $jobsModel->headline = '"Foo" is not "bar"';
        $jobsModel->teaser = '<p>Foo does not equal bar.</p>';

        $jobs = $this->mockAdapter(['generateJobsUrl']);
        $jobs
            ->method('generateJobsUrl')
            ->willReturnCallback(
                static function (ContaoJobsModel $model, bool $addArchive, bool $absolute): string {
                    if ($absolute) {
                        return 'http://domain.tld/jobs/foo-is-not-bar.html';
                    }

                    return 'jobs/foo-is-not-bar.html';
                }
            )
        ;

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $jobsModel]),
            ContaoJobs::class => $jobs,
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame(
            '<a href="jobs/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener('jobs::2', false, null, [])
        );

        $this->assertSame(
            '<a href="jobs/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">"Foo" is not "bar"</a>',
            $listener('jobs::2::blank', false, null, [])
        );

        $this->assertSame(
            '<a href="jobs/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener('jobs_open::2', false, null, [])
        );

        $this->assertSame(
            '<a href="jobs/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('jobs_open::2::blank', false, null, [])
        );

        $this->assertSame(
            '<a href="http://domain.tld/jobs/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('jobs_open::2::absolute::blank', false, null, [])
        );

        $this->assertSame(
            '<a href="http://domain.tld/jobs/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;" target="_blank" rel="noreferrer noopener">',
            $listener('jobs_open::2::blank::absolute', false, null, [])
        );

        $this->assertSame(
            'jobs/foo-is-not-bar.html',
            $listener('jobs_url::2', false, null, [])
        );

        $this->assertSame(
            'http://domain.tld/jobs/foo-is-not-bar.html',
            $listener('jobs_url::2', false, null, ['absolute'])
        );

        $this->assertSame(
            'http://domain.tld/jobs/foo-is-not-bar.html',
            $listener('jobs_url::2::absolute', false, null, [])
        );

        $this->assertSame(
            'http://domain.tld/jobs/foo-is-not-bar.html',
            $listener('jobs_url::2::blank::absolute', false, null, [])
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener('jobs_title::2', false, null, [])
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener('jobs_teaser::2', false, null, [])
        );
    }

    public function testHandlesEmptyUrls(): void
    {
        $jobsModel = $this->mockClassWithProperties(ContaoJobsModel::class);
        $jobsModel->headline = '"Foo" is not "bar"';
        $jobsModel->teaser = '<p>Foo does not equal bar.</p>';

        $jobs = $this->mockAdapter(['generateJobsUrl']);
        $jobs
            ->method('generateJobsUrl')
            ->willReturn('')
        ;

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $jobsModel]),
            ContaoJobs::class => $jobs,
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame(
            '<a href="./" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener('jobs::2', false, null, [])
        );

        $this->assertSame(
            '<a href="./" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener('jobs_open::2', false, null, [])
        );

        $this->assertSame(
            './',
            $listener('jobs_url::2', false, null, [])
        );
    }

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener('link_url::2', false, null, []));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
            ContaoJobsFeedModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame('', $listener('jobs_feed::3', false, null, []));
        $this->assertSame('', $listener('jobs_url::3', false, null, []));
    }
}
