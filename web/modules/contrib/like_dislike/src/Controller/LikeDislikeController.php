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
    if ($uid == 0) {
      // If user is anonymous, ask user to Login or Register.
      user_cookie_save(['destination' => $this->requestStack->getCurrentRequest()->get('like-dislike-redirect')]);
      $response->addCommand(new OpenModalDialogCommand('Like/Dislike', $this->likeDislikeLoginRegister()));
      return $response;
    }
    else {
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
      // Update content, based on like/dislike.
      $user_already_clicked = array_key_exists($uid, (array) $already_clicked_users);
      if ($clicked == 'like') {
        if (!$user_already_clicked) {
          $entity_data->$field_name->likes++;
          $already_clicked_users->$uid = 'like';
          $entity_data->$field_name->clicked_by = $this->encodeAlreadyClickedUsers($already_clicked_users);
          $entity_data->save();
          $response->addCommand(new HtmlCommand('#like_dislike_status-' . $decode_data->entity_id, ''));
          $response->addCommand(new HtmlCommand('#like-' . $decode_data->entity_id, (string) $entity_data->$field_name->likes));
          return $response;
        }
        elseif ($already_clicked_users->$uid == 'like') {
          $response->addCommand(new HtmlCommand('#like_dislike_status-' . $decode_data->entity_id, 'You have already liked..!'));
          return $response;
        }
        elseif ($already_clicked_users->$uid == 'dislike') {
          $entity_data->$field_name->likes++;
          $entity_data->$field_name->dislikes--;
          $already_clicked_users->$uid = 'like';
          $entity_data->$field_name->clicked_by = $this->encodeAlreadyClickedUsers($already_clicked_users);
          $entity_data->save();
          $response->addCommand(new HtmlCommand('#like_dislike_status-' . $decode_data->entity_id, ''));
          $response->addCommand(new HtmlCommand('#like-' . $decode_data->entity_id, (string) $entity_data->$field_name->likes));
          $response->addCommand(new HtmlCommand('#dislike-' . $decode_data->entity_id, (string) $entity_data->$field_name->dislikes));
          return $response;
        }
      }
      elseif ($clicked == 'dislike') {
        if (!$user_already_clicked) {
          $entity_data->$field_name->dislikes++;
          $already_clicked_users->$uid = 'dislike';
          $entity_data->$field_name->clicked_by = $this->encodeAlreadyClickedUsers($already_clicked_users);
          $entity_data->save();
          $response->addCommand(new HtmlCommand('#like_dislike_status-' . $decode_data->entity_id, ''));
          $response->addCommand(new HtmlCommand('#dislike-' . $decode_data->entity_id, (string) $entity_data->$field_name->dislikes));
          return $response;
        }
        elseif ($already_clicked_users->$uid == 'like') {
          $entity_data->$field_name->likes--;
          $entity_data->$field_name->dislikes++;
          $already_clicked_users->$uid = 'dislike';
          $entity_data->$field_name->clicked_by = $this->encodeAlreadyClickedUsers($already_clicked_users);
          $entity_data->save();
          $response->addCommand(new HtmlCommand('#like_dislike_status-' . $decode_data->entity_id, ''));
          $response->addCommand(new HtmlCommand('#like-' . $decode_data->entity_id, (string) $entity_data->$field_name->likes));
          return $response->addCommand(new HtmlCommand('#dislike-' . $decode_data->entity_id, (string) $entity_data->$field_name->dislikes));
        }
        elseif ($already_clicked_users->$uid == 'dislike') {
          $response->addCommand(new HtmlCommand('#like_dislike_status-' . $decode_data->entity_id, 'You have already disliked..!'));
          return $response;
        }
      }
    }
  }

  /**
   * Get the login and Registration options for anonymous user.
   *
   * @return mixed
   *   Return rendered output.
   */
  protected function likeDislikeLoginRegister() {
    $options = [
      'attributes' => [
        'class' => [
          'use-ajax',
          'login-popup-form',
        ],
        'data-dialog-type' => 'modal',
      ],
    ];
    $user_register = Url::fromRoute('user.register')->setOptions($options);
    $user_login = Url::fromRoute('user.login')->setOptions($options);
    $register = Link::fromTextAndUrl($this->t('Register'), $user_register)->toString();
    $login = Link::fromTextAndUrl($this->t('Log in'), $user_login)->toString();
    $content = [
      'content' => [
        '#markup' => "Only logged-in users are allowed to like  or dislike. Visit " . $register . " | " . $login,
      ],
    ];
    return $this->renderer->render($content);
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
