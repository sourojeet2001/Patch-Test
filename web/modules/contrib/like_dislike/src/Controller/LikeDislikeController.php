<?php

namespace Drupal\like_dislike\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Like Dislike Controller.
 *
 * @package Drupal\like_dislike\Controller
 */
class LikeDislikeController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an LinkClickCountController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RequestStack $request, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, RendererInterface $renderer) {
    $this->requestStack = $request;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $account;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * Like or Dislike handler.
   *
   * @param string $clicked
   *   Status of the click link.
   * @param string $data
   *   Data passed from the formatter.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|string
   *   Response count for the like/dislike.
   */
  public function handler($clicked, $data) {
    $response = new AjaxResponse();
    $uid = $this->currentUser->id();

    // Get the users who already clicked on this particular content.
    $decode_data = json_decode(base64_decode($data));
    $entity_data = $this->entityTypeManager
      ->getStorage($decode_data->entity_type)
      ->load($decode_data->entity_id);
    $field_name = $decode_data->field_name;
    $already_clicked_users = json_decode($entity_data->$field_name->clicked_by);
    if ($already_clicked_users == NULL) {
      $entity_data->$field_name->likes = 0;
      $entity_data->$field_name->dislikes = 0;
      $already_clicked_users = new \stdClass();
      $entity_data->$field_name->clicked_by = $this->encodeAlreadyClickedUsers($already_clicked_users);
      $entity_data->save();
    }
    if ($already_clicked_users == NULL) {
      $entity_data->$field_name->likes = 0;
      $entity_data->$field_name->dislikes = 0;
      $already_clicked_users = new \stdClass();
      $entity_data->$field_name->clicked_by = $this->encodeAlreadyClickedUsers($already_clicked_users);
      $entity_data->save();
    }
    if ($clicked == 'like') {
      $entity_data->$field_name->likes++;
      $entity_data->save();
      $response->addCommand(new HtmlCommand('#like_dislike_status-' . $decode_data->entity_id, ''));
      $response->addCommand(new HtmlCommand('#like-' . $decode_data->entity_id, (string) $entity_data->$field_name->likes));
      return $response;
    }
  }

  /**
   * Cleanup already clicked users & encode data, Remove anonymous user entry.
   *
   * @param object $already_clicked_users
   *   Object of the clicked users..
   *
   * @return string
   *   Return encoded json string.
   */
  protected function encodeAlreadyClickedUsers($already_clicked_users) {
    // Remove click from the anonymous user.
    $already_clicked_users_array = (array) $already_clicked_users;
    unset($already_clicked_users_array[0]);
    $already_clicked_users_data = (object) $already_clicked_users_array;
    // Encode the already clicked users data.
    return json_encode($already_clicked_users_data);
  }

}
