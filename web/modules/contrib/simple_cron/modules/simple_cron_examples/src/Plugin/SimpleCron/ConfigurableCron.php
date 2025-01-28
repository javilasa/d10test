<?php

namespace Drupal\simple_cron_examples\Plugin\SimpleCron;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\simple_cron\Plugin\SimpleCronPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configurable cron example implementation.
 *
 * @SimpleCron(
 *   id = "example_simple_cron_configurable",
 *   label = @Translation("Example: Configurable", context = "Simple cron")
 * )
 */
class ConfigurableCron extends SimpleCronPluginBase {

  use LoggerChannelTrait;

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
  public function process(): void {
    $this->getLogger('simple_cron_examples')->info('The module @module is selected.', [
      '@module' => $this->getConfigValue('module_name'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'module_name' => 'simple_cron_examples',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['module_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Module'),
      '#options' => $this->getModuleOptions(),
      '#default_value' => $this->getConfigValue('module_name'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Get module options list.
   *
   * @return array
   *   Module list.
   */
  protected function getModuleOptions(): array {
    $options = [];

    foreach ($this->moduleHandler->getModuleList() as $extension) {
      $machine_name = $extension->getName();
      $options[$machine_name] = $this->extensionList->getName($machine_name);
    }

    return $options;
  }

}
