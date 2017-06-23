<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for assigning recurrings to a different customer.
 */
class RecurringReassignForm extends FormBase {

  use RecurringCustomerFormTrait;

  /**
   * The current recurring.
   *
   * @var \Drupal\commerce_recurring\Entity\RecurringInterface
   */
  protected $recurring;

  /**
   * Constructs a new RecurringReassignForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->recurring = $current_route_match->getParameter('commerce_recurring');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_recurring_reassign_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->recurring->getOwnerId()) {
      $current_customer = $this->t('anonymous user with the email %email', [
        '%email' => $this->recurring->getEmail(),
      ]);
    }
    else {
      $owner = $this->recurring->getOwner();
      // If the display name has been altered to not be the email address,
      // show the email as well.
      if ($owner->getDisplayName() != $owner->getEmail()) {
        $customer_link_text = $this->t('@display (@email)', [
          '@display' => $owner->getDisplayName(),
          '@email' => $owner->getEmail(),
        ]);
      }
      else {
        $customer_link_text = $owner->getDisplayName();
      }

      $current_customer = $this->recurring->getOwner()->toLink($customer_link_text)->toString();
    }

    $form['current_customer'] = [
      '#type' => 'item',
      '#markup' => $this->t('The recurring is currently assigned to @customer.', [
        '@customer' => $current_customer,
      ]),
    ];
    $form += $this->buildCustomerForm($form, $form_state, $this->recurring);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Reassign recurring'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitCustomerForm($form, $form_state);

    $values = $form_state->getValues();
    $this->recurring->setEmail($values['mail']);
    $this->recurring->setOwnerId($values['uid']);
    $this->recurring->save();
    drupal_set_message($this->t('The recurring %label has been assigned to customer %customer.', [
      '%label' => $this->recurring->label(),
      '%customer' => $this->recurring->getOwner()->label(),
    ]));
    $form_state->setRedirectUrl($this->recurring->toUrl('collection'));
  }

}
