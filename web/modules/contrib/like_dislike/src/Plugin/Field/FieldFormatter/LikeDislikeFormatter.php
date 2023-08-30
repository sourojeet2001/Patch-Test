<?php

namespace Drupal\like_dislike\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'like_dislike_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "like_dislike_formatter",
 *   label = @Translation("Like Dislike"),
 *   field_types = {
 *     "like_dislike"
 *   }
 * )
 */
class LikeDislikeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, RequestStack $request) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->requestStack = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
      'likes_label' => t('Like'),
      'dislikes_label' => t('Dislike'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['likes_label'] = [
      '#title' => $this->t('Like label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('likes_label'),
    ];
    $form['dislikes_label'] = [
      '#title' => $this->t('Dislike label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('dislikes_label'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $summary[] = t('Like / Dislike label can be updated.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = 'en') {
    $elements = [];
    // Get data to be passed in the URL.
    $data = $this->getDataToPassInUrl($items);
    // Get query parameters to be passed in the URL.
    $query_parameters = $this->getQueryParametersToPassInUrl();
    // @todo remove below code once this is fixed - https://www.drupal.org/project/drupal/issues/2730351
    $route = 'like_dislike.manager';
    if (!$this->currentUser->isAuthenticated()) {
      $route = 'like_dislike.loggedout_manager';
    }
    $settings = $this->getSettings();
    $elements[] = [
      '#theme' => 'like_dislike',
      '#likes' => $this->getContentLikesOrDislikes($items, 'like'),
      '#dislikes' => $this->getContentLikesOrDislikes($items, 'dislike'),
      '#disable' => $this->getContentLikesOrDislikes($items, 'disable'),
      '#like_url' => Url::fromRoute(
        $route, ['clicked' => 'like', 'data' => $data], ['query' => $query_parameters]
      )->toString(),
      '#dislike_url' => Url::fromRoute(
        $route, ['clicked' => 'dislike', 'data' => $data], ['query' => $query_parameters]
      )->toString(),
      '#entity_id' => $items->getEntity()->id(),
      '#likes_label' => $settings['likes_label'] ?: $this->t('Like'),
      '#dislikes_label' => $settings['dislikes_label'] ?: $this->t('Dislike'),
    ];
    // Attach the dependent libraries.
    $elements['#attached']['library'][] = 'like_dislike/like_dislike';
    // Set the cache for the element.
    $elements['#cache']['max-age'] = 0;
    return $elements;
  }

  /**
   * Get data to be passed in the URL.
   *
   * @param Drupal\Core\Field\FieldItemListInterface $items
   *   Field items.
   *
   * @return string
   *   Encoded json string.
   */
  protected function getDataToPassInUrl(FieldItemListInterface $items) {
    $initial_data = [];
    foreach ($items as $delta => $item) {
      $initial_data['likes'] = $items[$delta]->likes;
      $initial_data['dislikes'] = $items[$delta]->dislikes;
    }
    $initial_data['entity_type'] = $items->getEntity()->getEntityTypeId();
    $initial_data['entity_id'] = $items->getEntity()->id();
    $initial_data['field_name'] = $items->getFieldDefinition()->getName();
    return base64_encode(json_encode($initial_data));
  }

  /**
   * Get query parameters to be passed in the URL.
   *
   * @return array
   *   Array of query parameters.
   */
  protected function getQueryParametersToPassInUrl() {
    $query_parameters = [];
    // If user is anonymous, the append the destination url to query parameters.
    if ($this->currentUser->id() == 0) {
      $query_parameters['like-dislike-redirect'] = $this->requestStack->getCurrentRequest()->getUri();
    }
    return $query_parameters;
  }

  /**
   * Get content like & dislikes.
   *
   * @param Drupal\Core\Field\FieldItemListInterface $items
   *   Field items.
   * @param string $likeOrDislike
   *   Like or Dislike string.
   *
   * @return string|null
   *   Number of likes or dislikes of the content OR NULL.
   */
  protected function getContentLikesOrDislikes(FieldItemListInterface $items, $likeOrDislike) {
    $data = [];
    foreach ($items as $delta => $item) {
      $data['likes'] = $items[$delta]->likes;
      $data['dislikes'] = $items[$delta]->dislikes;
      $data['disable'] = $items[$delta]->disable;
    }
    if ($likeOrDislike == 'like') {
      return $data['likes'] ?? NULL;
    }
    elseif ($likeOrDislike == 'dislike') {
      return $data['dislikes'] ?? NULL;
    }
    elseif ($likeOrDislike == 'disable') {
      return $data['disable'] ?? FALSE;
    }
  }

}
