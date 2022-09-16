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

use Contao\CoreBundle\Framework\ContaoFramework;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobs;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobsModel;
use Contao\StringUtil;

/**
 * @internal
 */
class InsertTagsListener
{
    private const SUPPORTED_TAGS = [
        'jobs',
        'jobs_open',
        'jobs_url',
        'jobs_title',
        'jobs_teaser',
    ];

    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @return string|false
     */
    public function __invoke(string $tag, bool $useCache, $cacheValue, array $flags)
    {
        $elements = explode('::', $tag);
        $key = strtolower($elements[0]);

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceJobsInsertTags($key, $elements[1], array_merge($flags, \array_slice($elements, 2)));
        }

        return false;
    }

    private function replaceJobsInsertTags(string $insertTag, string $idOrAlias, array $arguments): string
    {
        $this->framework->initialize();

        $adapter = $this->framework->getAdapter(ContaoJobsModel::class);

        if (null === ($model = $adapter->findByIdOrAlias($idOrAlias))) {
            return '';
        }

        $jobs = $this->framework->getAdapter(ContaoJobs::class);

        switch ($insertTag) {
            case 'jobs':
                return sprintf(
                    '<a href="%s" title="%s"%s>%s</a>',
                    $jobs->generateJobsUrl($model, false, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->headline),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : '',
                    $model->headline
                );

            case 'jobs_open':
                return sprintf(
                    '<a href="%s" title="%s"%s>',
                    $jobs->generateJobsUrl($model, false, \in_array('absolute', $arguments, true)) ?: './',
                    StringUtil::specialcharsAttribute($model->headline),
                    \in_array('blank', $arguments, true) ? ' target="_blank" rel="noreferrer noopener"' : ''
                );

            case 'jobs_url':
                return $jobs->generateJobsUrl($model, false, \in_array('absolute', $arguments, true)) ?: './';

            case 'jobs_title':
                return StringUtil::specialcharsAttribute($model->headline);

            case 'jobs_teaser':
                return $model->teaser;
        }

        return '';
    }
}
