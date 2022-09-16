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

namespace ManiaxAtWork\ContaoJobsBundle\EventListener;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
class PreviewUrlCreateListener
{
    private RequestStack $requestStack;
    private ContaoFramework $framework;

    public function __construct(RequestStack $requestStack, ContaoFramework $framework)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    /**
     * Adds the jobs ID to the front end preview URL.
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || 'jobs' !== $event->getKey()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        // Return on the jobs archive list page
        if ('tl_jobs' === $request->query->get('table') && !$request->query->has('act')) {
            return;
        }

        if ((!$id = $this->getId($event, $request)) || (!$jobsModel = $this->getJobsModel($id))) {
            return;
        }

        $event->setQuery('jobs='.$jobsModel->id);
    }

    /**
     * @return int|string
     */
    private function getId(PreviewUrlCreateEvent $event, Request $request)
    {
        // Overwrite the ID if the jobs settings are edited
        if ('tl_jobs' === $request->query->get('table') && 'edit' === $request->query->get('act')) {
            return $request->query->get('id');
        }

        return $event->getId();
    }

    /**
     * @param int|string $id
     */
    private function getJobsModel($id): ?ContaoJobsModel
    {
        return $this->framework->getAdapter(ContaoJobsModel::class)->findByPk($id);
    }
}
