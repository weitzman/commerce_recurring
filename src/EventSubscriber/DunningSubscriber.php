<?php

namespace Drupal\commerce_recurring\EventSubscriber;

use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\commerce_recurring\Event\PaymentDeclinedEvent;
use Drupal\commerce_recurring\Event\RecurringEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Commerce Dunning event subscriber.
 */
class DunningSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The order type entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * The order total summary.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * The entity view builder for profiles.
   *
   * @var \Drupal\profile\ProfileViewBuilder
   */
  protected $profileViewBuilder;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary
   *   The order total summary.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, OrderTotalSummaryInterface $order_total_summary, Renderer $renderer) {
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->orderTotalSummary = $order_total_summary;
    $this->profileViewBuilder = $entity_type_manager->getViewBuilder('profile');
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
  }

  /**
   * Sends a payment declined email.
   *
   * @param  $event
   *   The event we subscribed to.
   */
  public function sendPaymentDeclined(PaymentDeclinedEvent $event) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->orderTypeStorage->load($order->bundle());

    $retry_days = $event->getRetrydays();

    $to = $order->getEmail();
    if (!$to) {
      // The email should not be empty, unless the order is malformed.
      return;
    }

    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'from' => $order->getStore()->getEmail(),
      'subject' => $this->t('Payment declined - Order #@number.', ['@number' => $order->getOrderNumber()]),
      'order' => $order,
    ];
    if ($receipt_bcc = $order_type->getReceiptBcc()) {
      $params['headers']['Bcc'] = $receipt_bcc;
    }

    $build = [
      '#theme' => 'commerce_recurring_payment_declined',
      '#order_entity' => $order,
      '#retry_num' => $event->getJob()->getNumRetries(),
      '#retry_date' => strtotime("+$retry_days days"),
      '#payment_method_link' => Url::fromRoute('entity.commerce_payment_method.collection', ['user' => $order->getCustomerId()], ['absolute' => true])->toString(),
      '#totals' => $this->orderTotalSummary->buildTotals($order),
    ];
    if ($billing_profile = $order->getBillingProfile()) {
      $build['#billing_information'] = $this->profileViewBuilder->view($billing_profile);
    }
    $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($build) {
      return $this->renderer->render($build);
    });

    // Replicated logic from EmailAction and contact's MailHandler.
    if ($customer = $order->getCustomer()) {
      $langcode = $customer->getPreferredLangcode();
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    $this->mailManager->mail('commerce_recurring', 'payment-declined', $to, $langcode, $params);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [RecurringEvents::PAYMENT_DECLINED => ['sendPaymentDeclined', -100]];
    return $events;
  }

}
