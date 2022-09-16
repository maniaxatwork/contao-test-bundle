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

namespace ManiaxAtWork\ContaoJobsBundle\Tests\Picker;

use Contao\CoreBundle\Picker\PickerConfig;
use ManiaxAtWork\ContaoJobsArchiveModel;
use ManiaxAtWork\ContaoJobsBundle\Picker\JobsPickerProvider;
use ManiaxAtWork\ContaoJobsModel;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobsPickerProviderTest extends ContaoTestCase
{
    public function testCreatesTheMenuItem(): void
    {
        $config = json_encode([
            'context' => 'link',
            'extras' => [],
            'current' => 'jobsPicker',
            'value' => '',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($config))) {
            $config = $encoded;
        }

        $picker = $this->getPicker();
        $item = $picker->createMenuItem(new PickerConfig('link', [], '', 'jobsPicker'));
        $uri = 'contao_backend?do=jobs&popup=1&picker='.strtr(base64_encode($config), '+/=', '-_,');

        $this->assertSame('Jobs picker', $item->getLabel());
        $this->assertSame(['class' => 'jobsPicker'], $item->getLinkAttributes());
        $this->assertTrue($item->isCurrent());
        $this->assertSame($uri, $item->getUri());
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->isCurrent(new PickerConfig('link', [], '', 'jobsPicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('jobsPicker', $picker->getName());
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $picker = $this->getPicker(true);

        $this->assertTrue($picker->supportsContext('link'));
        $this->assertFalse($picker->supportsContext('file'));
    }

    public function testChecksIfModuleAccessIsGranted(): void
    {
        $picker = $this->getPicker(false);

        $this->assertFalse($picker->supportsContext('link'));
    }

    public function testChecksIfAValueIsSupported(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->supportsValue(new PickerConfig('link', [], '{{jobs_url::5}}')));
        $this->assertFalse($picker->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('tl_jobs', $picker->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $picker = $this->getPicker();
        $extra = ['source' => 'tl_jobs.2'];

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
                'flags' => ['urlattr'],
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{jobs_url::5|urlattr}}'))
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{link_url::5}}'))
        );
    }

    public function testConvertsTheDcaValue(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('{{jobs_url::5}}', $picker->convertDcaValue(new PickerConfig('link'), 5));
    }

    public function testConvertsTheDcaValueWithACustomInsertTag(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(
            '{{jobs_title::5}}',
            $picker->convertDcaValue(new PickerConfig('link', ['insertTag' => '{{jobs_title::%s}}']), 5)
        );
    }

    public function testAddsTableAndIdIfThereIsAValue(): void
    {
        $model = $this->mockClassWithProperties(ContaoJobsArchiveModel::class);
        $model->id = 1;

        $jobs = $this->createMock(ContaoJobsModel::class);
        $jobs
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($model)
        ;

        $config = new PickerConfig('link', [], '{{jobs_url::1}}', 'jobsPicker');

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findById' => $jobs]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(JobsPickerProvider::class, 'getRouteParameters');
        $method->setAccessible(true);
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('jobs', $params['do']);
        $this->assertSame('tl_jobs', $params['table']);
        $this->assertSame(1, $params['id']);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoEventsModel(): void
    {
        $config = new PickerConfig('link', [], '{{jobs_url::1}}', 'jobsPicker');

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findById' => null]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(JobsPickerProvider::class, 'getRouteParameters');
        $method->setAccessible(true);
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('jobs', $params['do']);
        $this->assertArrayNotHasKey('tl_jobs', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoModel(): void
    {
        $jobs = $this->createMock(ContaoJobsModel::class);
        $jobs
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $config = new PickerConfig('link', [], '{{jobs_url::1}}', 'jobsPicker');

        $adapters = [
            ContaoJobsModel::class => $this->mockConfiguredAdapter(['findById' => $jobs]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(JobsPickerProvider::class, 'getRouteParameters');
        $method->setAccessible(true);
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('jobs', $params['do']);
        $this->assertArrayNotHasKey('tl_jobs', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    private function getPicker(bool $accessGranted = null): JobsPickerProvider
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects(null === $accessGranted ? $this->never() : $this->once())
            ->method('isGranted')
            ->willReturn($accessGranted ?? false)
        ;

        $menuFactory = $this->createMock(FactoryInterface::class);
        $menuFactory
            ->method('createItem')
            ->willReturnCallback(
                static function (string $name, array $data) use ($menuFactory): ItemInterface {
                    $item = new MenuItem($name, $menuFactory);
                    $item->setLabel($data['label']);
                    $item->setLinkAttributes($data['linkAttributes']);
                    $item->setCurrent($data['current']);
                    $item->setUri($data['uri']);

                    return $item;
                }
            )
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name, array $params): string => $name.'?'.http_build_query($params))
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('Jobs picker')
        ;

        return new JobsPickerProvider($menuFactory, $router, $translator, $security);
    }
}
