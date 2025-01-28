<?php

namespace Drupal\product_of_the_day\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ProductOfTheDayBlock' block.
 *
 * @Block(
 *   id = "product_of_the_day_block",
 *   admin_label = @Translation("Product of the Day Block"),
 *   category = @Translation("Custom Blocks")
 * )
 */
class ProductOfTheDayBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ProductOfTheDayBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->getConfiguration();

    $selected_products = [];
    for ($i = 1; $i <= 5; $i++) {
      if (!empty($config['selected_product_' . $i])) {
        $node = $this->entityTypeManager->getStorage('node')->load($config['selected_product_' . $i]);
        if ($node) {
          $selected_products[] = $node;
        }
      }
    }

    if (!empty($selected_products)) {
      $random_product = $selected_products[array_rand($selected_products)];

      $render_controller = $this->entityTypeManager->getViewBuilder($random_product->getEntityTypeId());
      $render_node = $render_controller->view($random_product, 'teaser');

      $node_rendered = $this->renderer->render($render_node);

      $nodeHtml = $node_rendered->__toString();

      $timestamp = time();
      $node_url = $random_product->toUrl();
      $node_url->setOption('query', ['event' => $timestamp]);

      $build = [
        '#type' => 'markup',
        '#markup' => $nodeHtml,
        'link' => [
          '#type' => 'link',
          '#title' => $this->t('View Product'),
          '#url' => $node_url,
          '#attributes' => ['class' => ['button', 'btn-primary']],
        ],
      ];
    } else {
      $build = [
        '#type' => 'markup',
        '#markup' => $this->t('No products selected for the block.'),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    for ($i = 1; $i <= 5; $i++) {
      $form['selected_product_' . $i] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Product @number', ['@number' => $i]),
        '#target_type' => 'node',
        '#selection_handler' => 'default',
        '#selection_settings' => [
          'target_bundles' => ['product'],
        ],
        '#default_value' => !empty($config['selected_product_' . $i])
          ? $this->entityTypeManager->getStorage('node')->load($config['selected_product_' . $i])
          : NULL,
        '#description' => $this->t('Select a product for position @number.', ['@number' => $i]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    for ($i = 1; $i <= 5; $i++) {
      $this->setConfigurationValue(
        'selected_product_' . $i,
        $form_state->getValue('selected_product_' . $i)
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}