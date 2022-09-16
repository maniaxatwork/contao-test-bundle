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

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobsModel;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class PreviewUrlConvertListener
{
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Adds the front end preview URL to the event.
     */
    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        if (null === ($jobs = $this->getJobsModel($event->getRequest()))) {
            return;
        }

        $event->setUrl($this->framework->getAdapter(Jobs::class)->generateJobsUrl($jobs, false, true));
    }

    private function getJobsModel(Request $request): ?ContaoJobsModel
    {
        if (!$request->query->has('jobs')) {
            return null;
        }

        return $this->framework->getAdapter(ContaoJobsModel::class)->findByPk($request->query->get('jobs'));
    }
}
