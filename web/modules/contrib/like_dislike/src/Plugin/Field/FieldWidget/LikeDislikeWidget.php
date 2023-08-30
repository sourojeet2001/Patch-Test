<?php

namespace Drupal\like_dislike\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'like_dislike_widget' widget.
 *
 * @FieldWidget(
 *   id = "like_dislike_widget",
 *   label = @Translation("Like dislike widget"),
 *   field_types = {
 *     "like_dislike"
 *   }
 * )
 */
class LikeDislikeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];

    $element['likes'] = [
      '#type' => 'item',
      '#title' => t('Likes'),
      '#markup' => $items[$delta]->likes ?? 0,
      '#description' => $this->t("Total Likes added so far, by different authenticated users from your website on this content."),
    ];
    $element['dislikes'] = [
      '#type' => 'item',
      '#title' => t('Dislikes'),
      '#markup' => $items[$delta]->dislikes ?? 0,
      '#description' => $this->t("Total Dislikes added so far, by different authenticated users from your website on this content."),
    ];
    $element['disable'] = [
      '#title' => $this->t('Disable Like/Dislike for this content?'),
      '#type' => 'checkbox',
      '#default_value' => $items[$delta]->disable ?? 0,
      '#description' => $this->t("By default Like/Dislike will be enabled for the content,
        If you don't want to show Like/Dislike you can select this option.\n
        IMP: If you disable, you will be loosing the data related to likes & dislikes of this content."),
    ];

    return $element;
  }

}
