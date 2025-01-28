<?php

namespace Drupal\simple_cron\Plugin\SimpleCron;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\simple_cron\Form\SimpleCronSettingsForm;
use Drupal\simple_cron\Plugin\SimpleCronPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Module cron hook handler.
 *
 * @SimpleCron(
 *   id = "cron",
 *   label = @Translation("Cron", context = "Simple cron")
 * )
 */
class Cron extends SimpleCronPluginBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Drupal\Core\Extension\ModuleExtensionList definition.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $extensionList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->extensionList = $container->get('extension.list.module');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $definitions = $this->getTypeDefinitions();
    $definition = $definitions[$this->getType()] ?? [];

    return $definition['label'] ?? $this->t('Unknown');
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeDefinitions(): array {
    $definitions = [];

    if ($this->configFactory->get(SimpleCronSettingsForm::SETTINGS_NAME)->get('cron.override_enabled')) {
      if (method_exists($this->moduleHandler, 'invokeAllWith')) {
        $implementations = [];
        $this->moduleHandler->invokeAllWith('cron', function (callable $hook, string $module) use (&$implementations) {
          $implementations[] = $module;
        });
      }
      else {
        // Use the deprecated getImplementations() for Drupal < 9.4.
        $implementations = $this->moduleHandler->getImplementations('cron');
      }

      foreach ($implementations as $module) {
        $definitions[$module] = [
          'label' => $this->t('The @module_name module cron', [
            '@module_name' => $this->extensionList->getName($module),
          ]),
          'provider' => $module,
        ];
      }
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $this->moduleHandler->invoke($this->getType(), 'cron');
  }

}
