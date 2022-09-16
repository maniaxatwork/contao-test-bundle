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

namespace ManiaxAtWork\ContaoJobsBundle\Picker;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractInsertTagPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobsArchiveModel;
use ManiaxAtWork\ContaoJobsBundle\ContaoJobsModel;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobsPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    private Security $security;

    /**
     * @internal Do not inherit from this class; decorate the "contao_jobs.picker.jobs_provider" service instead
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, ?TranslatorInterface $translator, Security $security)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->security = $security;
    }

    public function getName(): string
    {
        return 'jobsPicker';
    }

    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'jobs');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(): string
    {
        return 'tl_jobs';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config), $value);
    }

    protected function getRouteParameters(PickerConfig $config = null): array
    {
        $params = ['do' => 'jobs'];

        if (null === $config || !$config->getValue() || !$this->supportsValue($config)) {
            return $params;
        }

        if (null !== ($jobsArchiveId = $this->getJobsArchiveId($this->getInsertTagValue($config)))) {
            $params['table'] = 'tl_jobs';
            $params['id'] = $jobsArchiveId;
        }

        return $params;
    }

    protected function getDefaultInsertTag(): string
    {
        return '{{jobs_url::%s}}';
    }

    /**
     * @param int|string $id
     */
    private function getJobsArchiveId($id): ?int
    {
        $jobsAdapter = $this->framework->getAdapter(JobsModel::class);

        if (!($jobsModel = $jobsAdapter->findById($id)) instanceof ContaoJobsModel) {
            return null;
        }

        if (!($jobsArchive = $jobsModel->getRelated('pid')) instanceof ContaoJobsArchiveModel) {
            return null;
        }

        return (int) $jobsArchive->id;
    }
}
