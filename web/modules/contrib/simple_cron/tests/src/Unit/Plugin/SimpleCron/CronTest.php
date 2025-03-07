<?php

namespace Drupal\Tests\simple_cron\Unit\Plugin\SimpleCron;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use Drupal\simple_cron\Entity\CronJobInterface;
use Drupal\simple_cron\Form\SimpleCronSettingsForm;
use Drupal\simple_cron\Plugin\SimpleCron\Cron;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the Cron simple cron plugin.
 *
 * @group simple_cron
 * @coversDefaultClass \Drupal\simple_cron\Plugin\SimpleCron\Cron
 */
class CronTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The current state of the test in memory.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The container builder.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Build the container using the resulting mock objects.
    \Drupal::setContainer(new ContainerBuilder());
    $this->container = \Drupal::getContainer();

    // Construct a state object used for testing logger assertions.
    $cache = $this->createMock(CacheBackendInterface::class);
    $lock = $this->createMock(LockBackendInterface::class);
    $this->state = new State(new KeyValueMemoryFactory(), $cache, $lock);

    // Set services mocks.
    $this->container->set('state', $this->state);
    $this->container->set('config.factory', $this->getConfigFactoryMock(TRUE));
    $this->container->set('module_handler', $this->getModuleHandlerMock());
    $this->container->set('extension.list.module', $this->getExtensionListMock());
    $this->container->set('string_translation', $this->getStringTranslationStub());
  }

  /**
   * Tests the process.
   *
   * @param string $module
   *   The module name.
   * @param string $state_name
   *   The state name.
   * @param bool $expected
   *   The expected result.
   *
   * @covers ::process
   * @dataProvider providerTestProcess
   */
  public function testProcess(string $module, string $state_name, bool $expected): void {
    $this->resetTestingState();

    $cron_job = $this->prophesize(CronJobInterface::class);
    $cron_job->getType()->willReturn($module);
    $plugin = Cron::create($this->container, [], 'cron', []);
    $plugin->setCronJob($cron_job->reveal());
    $plugin->process();
    $status = $this->state->get($state_name);

    $this->assertEquals($expected, $status, 'Process is executed.');
  }

  /**
   * Data provider for ::testProcess() method.
   *
   * @return array
   *   The data of process test.
   */
  public static function providerTestProcess(): array {
    return [
      ['simple_cron_test', 'state.simple_cron_test', TRUE],
      ['simple_cron_test_second', 'state.simple_cron_test_second', TRUE],
      ['simple_cron_test', 'state.simple_cron_test_second', FALSE],
      ['simple_cron_test_second', 'state.simple_cron_test', FALSE],
      ['simple_cron_test_not_exists', 'state.simple_cron_test', FALSE],
      ['simple_cron_test_not_exists', 'state.simple_cron_test_second', FALSE],
    ];
  }

  /**
   * Tests the label.
   *
   * @param bool $override_enabled
   *   TRUE when override enabled.
   * @param string $module
   *   The module name.
   * @param string $expected
   *   The expected result.
   *
   * @covers ::label
   * @dataProvider providerTestLabel
   */
  public function testLabel(bool $override_enabled, string $module, string $expected): void {
    $this->resetTestingState();
    $this->container->set('config.factory', $this->getConfigFactoryMock($override_enabled));

    $cron_job = $this->prophesize(CronJobInterface::class);
    $cron_job->getType()->willReturn($module);
    $plugin = Cron::create($this->container, [], 'cron', []);
    $plugin->setCronJob($cron_job->reveal());
    $label = (string) $plugin->label();

    $this->assertEquals($expected, $label, 'Label is correct.');
  }

  /**
   * Data provider for ::testLabel() method.
   *
   * @return array
   *   The data of label test.
   */
  public static function providerTestLabel(): array {
    return [
      [TRUE, 'simple_cron_test', 'The Cron test first module cron'],
      [TRUE, 'simple_cron_test_second', 'The Cron test second module cron'],
      [TRUE, 'simple_cron_test_not_exists', 'Unknown'],
      [FALSE, 'simple_cron_test', 'Unknown'],
      [FALSE, 'simple_cron_test_second', 'Unknown'],
      [FALSE, 'simple_cron_test_not_exists', 'Unknown'],
    ];
  }

  /**
   * Tests the type definitions.
   *
   * @param bool $override_enabled
   *   TRUE when override enabled.
   * @param array $expected
   *   The expected result.
   *
   * @covers ::getTypeDefinitions
   * @dataProvider providerGetTypeDefinitions
   */
  public function testGetTypeDefinitions(bool $override_enabled, array $expected): void {
    $this->resetTestingState();
    $this->container->set('config.factory', $this->getConfigFactoryMock($override_enabled));

    $cron_job = $this->prophesize(CronJobInterface::class);
    $plugin = Cron::create($this->container, [], 'cron', []);
    $plugin->setCronJob($cron_job->reveal());
    $definitions = $plugin->getTypeDefinitions();

    // Convert translatable markup to string.
    foreach ($definitions as $type => $definition) {
      $definitions[$type]['label'] = (string) $definition['label'];
    }

    $this->assertEquals($expected, $definitions, 'Type definitions is correct.');
  }

  /**
   * Data provider for ::testGetTypeDefinitions() method.
   *
   * @return array
   *   The data of type definitions test.
   */
  public static function providerGetTypeDefinitions(): array {
    return [
      [
        FALSE,
        [],
      ],
      [
        TRUE,
        [
          'simple_cron_test' => [
            'label' => 'The Cron test first module cron',
            'provider' => 'simple_cron_test',
          ],
          'simple_cron_test_second' => [
            'label' => 'The Cron test second module cron',
            'provider' => 'simple_cron_test_second',
          ],
        ],
      ],
    ];
  }

  /**
   * Resets the testing state.
   */
  protected function resetTestingState(): void {
    $this->state->set('state.simple_cron_test', FALSE);
    $this->state->set('state.simple_cron_test_second', FALSE);
  }

  /**
   * Get config factory mock.
   *
   * @param bool $override_enabled
   *   TRUE when override enabled.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory mock.
   */
  protected function getConfigFactoryMock(bool $override_enabled): ConfigFactoryInterface {
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);

    $simple_cron_config = $this->prophesize(ImmutableConfig::class);
    $simple_cron_config->get('cron.override_enabled')->willReturn($override_enabled);
    $config_factory->get(SimpleCronSettingsForm::SETTINGS_NAME)->willReturn($simple_cron_config->reveal());

    return $config_factory->reveal();
  }

  /**
   * Get extension list mock.
   *
   * @return \Drupal\Core\Extension\ModuleExtensionList
   *   The extension list mock.
   */
  protected function getExtensionListMock(): ModuleExtensionList {
    $extension_list = $this->createMock(ModuleExtensionList::class);
    $extension_list->method('getName')->willReturnMap([
      ['simple_cron_test', 'Cron test first'],
      ['simple_cron_test_second', 'Cron test second'],
    ]);
    return $extension_list;
  }

  /**
   * Get module handler mock.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler mock.
   */
  protected function getModuleHandlerMock(): ModuleHandlerInterface {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);

    // Implementations mock.
    if (method_exists($module_handler, 'invokeAllWith')) {
      $module_handler->method('invokeAllWith')
        ->with('cron')
        ->willReturnCallback(function (string $hook, callable $callback) {
          $callback(\Closure::fromCallable([$this, 'simpleCronTestCron']), 'simple_cron_test');
          $callback(\Closure::fromCallable([$this, 'simpleCronTestSecondCron']), 'simple_cron_test_second');
        });
    }

    // Name mock.
    $module_handler->method('getName')->willReturnMap([
      ['simple_cron_test', 'Cron test first'],
      ['simple_cron_test_second', 'Cron test second'],
    ]);

    $module_handler->method('invoke')->willReturnCallback(function ($module, $hook) {
      switch ($module . '_' . $hook) {
        case 'simple_cron_test_cron':
          $this->simpleCronTestCron();
          break;

        case 'simple_cron_test_second_cron':
          $this->simpleCronTestSecondCron();
          break;

        default:
          return NULL;
      }
    });

    return $module_handler;
  }

  /**
   * Simple cron test cron.
   */
  public function simpleCronTestCron(): void {
    \Drupal::state()->set('state.simple_cron_test', TRUE);
  }

  /**
   * Simple cron test second cron.
   */
  public function simpleCronTestSecondCron(): void {
    \Drupal::state()->set('state.simple_cron_test_second', TRUE);
  }

}
