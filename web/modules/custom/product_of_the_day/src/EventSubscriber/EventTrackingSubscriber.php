<?php

namespace Drupal\product_of_the_day\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Database\Connection;

/**
 * Subscribes to the kernel request event to track events.
 */
class EventTrackingSubscriber implements EventSubscriberInterface {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs the EventTrackingSubscriber.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(RequestStack $request_stack, Connection $database) {
    $this->requestStack = $request_stack;
    $this->database = $database;
  }

  /**
   * Executes on kernel request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onKernelRequest(RequestEvent $event) {
    $request = $this->requestStack->getCurrentRequest();
    $path = $request->getPathInfo();
    $query = $request->query;

    if (preg_match('/^\/node\/(\d+)$/', $path, $matches) && $query->has('event')) {
      $nid = (int) $matches[1];
      $event_value = $query->get('event');

      $this->database->insert('product_of_day')
        ->fields([
          'nid' => $nid,
          'event_value' => $event_value,
          'timestamp' => time(),
        ])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'kernel.request' => ['onKernelRequest', 30],
    ];
  }
}
