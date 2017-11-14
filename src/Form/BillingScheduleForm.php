<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\commerce\Form\CommercePluginEntityFormBase;
use Drupal\commerce_recurring\BillingScheduleManager;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BillingScheduleForm extends CommercePluginEntityFormBase {

  /**
   * The billing schedule plugin manager.
   *
   * @var \Drupal\commerce_recurring\BillingScheduleManager
   */
  protected $pluginManager;

  /**
   * Constructs a new BillingScheduleForm object.
   *
   * @param \Drupal\commerce_recurring\BillingScheduleManager $plugin_manager
   *   The billing schedule plugin manager.
   */
  public function __construct(BillingScheduleManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_billing_schedule')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = $this->entity;
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    // Use the first available plugin as the default value.
    if (!$billing_schedule->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $billing_schedule->setPluginId($plugin);
    }
    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('plugin', $billing_schedule->getPluginId());
    // Pass the plugin configuration only if the plugin hasn't been changed via #ajax.
    $plugin_configuration = $billing_schedule->getPluginId() == $plugin ? $billing_schedule->getPluginConfiguration() : [];

    $wrapper_id = Html::getUniqueId('billing-schedule-form');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $billing_schedule->label(),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $billing_schedule->id(),
      '#machine_name' => [
        'exists' => [BillingSchedule::class, 'load'],
        'source' => ['label'],
      ],
    ];
    $form['displayLabel'] = [
      '#type' => 'textfield',
      '#title' => t('Display label'),
      '#description' => t('Used to identify the billing schedule on the frontend.'),
      '#default_value' => $billing_schedule->getDisplayLabel(),
      '#required' => TRUE,
    ];
    $form['billingType'] = [
      '#type' => 'radios',
      '#title' => $this->t('Billing type'),
      '#options' => [
        BillingScheduleInterface::BILLING_TYPE_PREPAID => $this->t('Prepaid'),
        BillingScheduleInterface::BILLING_TYPE_POSTPAID => $this->t('Postpaid'),
      ],
      '#default_value' => $billing_schedule->getBillingType(),
    ];
    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#disabled' => !$billing_schedule->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'commerce_billing_schedule',
      '#plugin_id' => $plugin,
      '#default_value' => $plugin_configuration,
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#options' => [
        FALSE => $this->t('Disabled'),
        TRUE => $this->t('Enabled'),
      ],
      '#default_value' => $billing_schedule->status(),
    ];

    /**
     * Start dunning form elements.
     * @see http://cgit.drupalcode.org/examples/tree/fapi_example/src/Form/AjaxAddMore.php.
     */

    // Gather the number of emails in the form.
    $num_emails = $form_state->get('num_emails');
    // We have to ensure that there is at least one notification field.
    if ($num_emails === NULL) {
      $num_emails = count($this->entity->getDunningSchedule()) ?: 3;
      $form_state->set('num_emails', $num_emails);
    }

    $form['#tree'] = TRUE;
    $form['schedule_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Dunning'),
      '#prefix' => '<div id="schedule-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_emails; $i++) {
      $form['schedule_fieldset']['notification'][$i] = [
        '#type' => 'number',
        '#title' => $this->t('Wait duration - @nth payment decline email', ['@nth' => $this->nth($i)]),
        '#size' => 2,
        '#description' => $i == 0 ? $this->t('The number of days to wait since payment is soft declined. Hard declines are always notified immediately.') : $this->t('The number of days to wait since @nth email.', ['@nth' => $this->nth($i-1)]),
        '#default_value' => $this->getDefaultNotification($i, $form_state),
      ];
    }

    $form['schedule_fieldset']['actions'] = [
      '#type' => 'actions',
    ];
    $form['schedule_fieldset']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => t('Add one email'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'schedule-fieldset-wrapper',
      ],
    ];
    // If there is more than one name, add the remove button.
    if ($num_emails > 1) {
      $form['schedule_fieldset']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'schedule-fieldset-wrapper',
        ],
      ];
    }
    $form['schedule_fieldset']['disposition'] = [
      '#type' => 'radios',
      '#title' => $this->t('After final dunning email'),
      '#weight' => 1000,
      '#options' => [
        'suspend' => $this->t('Suspend the subscription'),
        'cancel' => $this->t('Cancel the subscription (non-reversible)'),
      ],
      '#default_value' => $billing_schedule->getDunningDisposition(),
    ];

    return $this->protectPluginIdElement($form);
  }

  /**
   *
   */
  protected function getDefaultNotification($n, FormStateInterface $form_state) {
    $billing_schedule = $this->entity;
    if ($schedule = $billing_schedule->getDunningSchedule()) {
      return $schedule[$n];
    }

    // A new billing schedule. Return same defaults.
    return $n == 0 ? 0 : 3;
  }

  /**
   * Return the counting word corresponding to an integer.
   *
   * @param $i
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function nth(int $i) {
    $map = [
      0 => 'first',
      1 => 'second',
      2 => 'third',
      3 => 'fourth',
      4 => 'fifth',
      6 => 'sixth',
      7 => 'seventh',
      8 => 'eight',
      9 => 'ninth',
    ];
    return $this->t($map[$i]);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['schedule_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_emails');
    $add_button = $name_field + 1;
    $form_state->set('num_emails', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_emails');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_emails', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = $this->entity;
    $billing_schedule->setPluginConfiguration($form_state->getValue(['configuration']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $schedule = $this->entity->schedule_fieldset;
    $this->entity->setDunningDisposition($schedule['disposition']);
    $this->entity->setDunningSchedule($schedule['notification']);
    $this->entity->save();
    drupal_set_message($this->t('Saved the @label billing schedule.', ['@label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_billing_schedule.collection');
  }

}
