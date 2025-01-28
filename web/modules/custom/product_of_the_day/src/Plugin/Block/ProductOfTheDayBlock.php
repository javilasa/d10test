<?php

namespace Drupal\product_of_the_day\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Product of the Day' Block.
 *
 * @Block(
 *   id = "product_of_the_day_block",
 *   admin_label = @Translation("Product of the Day"),
 *   category = @Translation("Custom")
 * )
 */
class ProductOfTheDayBlock extends BlockBase
{


  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $build = [];
    $config = $this->getConfiguration();

    $selected_products = [];
    for ($i = 1; $i <= 5; $i++) {
      if (!empty($config['selected_product_' . $i])) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($config['selected_product_' . $i]);
        if ($node) {
          $selected_products[] = $node;
        }
      }
    }


    if (!empty($selected_products)) {
      $random_product = $selected_products[array_rand($selected_products)];

      $render_controller = \Drupal::entityTypeManager()->getViewBuilder($random_product->getEntityTypeId());
      $render_node = $render_controller->view($random_product, 'teaser');
      
      $node_rendered = \Drupal::service('renderer')->render($render_node);
      
      $nodeHtml = $node_rendered->__toString();

      $build = [
        '#type' => 'markup',
        '#markup' => $nodeHtml,
      ];
    } else {
      $build = [
        '#type' => 'markup',
        '#markup' => $this->t('No products selected for the block.'),
      ];
    }

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->setCacheMaxAge(0);
    $cache_metadata->applyTo($build);

    return $build;
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {

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
          ? \Drupal::entityTypeManager()->getStorage('node')->load($config['selected_product_' . $i])
          : NULL,
        '#description' => $this->t('Select a product for position @number.', ['@number' => $i]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    for ($i = 1; $i <= 5; $i++) {
      $this->setConfigurationValue(
        'selected_product_' . $i,
        $form_state->getValue('selected_product_' . $i)
      );
    }
  }


  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }
}
