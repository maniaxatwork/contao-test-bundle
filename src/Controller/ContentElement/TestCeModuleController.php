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

namespace Maniaxatwork\ContaoTestBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TestCeModuleController
 *
 * @ContentElement(TestCeModuleController::TYPE, category="test_ce_modules", template="ce_test_ce_module")
 */
class TestCeModuleController extends AbstractContentElementController
{
    public const TYPE = 'test_ce_module';

    /**
     * Generate the content element
     */
    protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
        $template->text = $model->text;

        return $template->getResponse();
    }
}
