<?php

namespace Drupal\like_dislike\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides automated tests for the like_dislike module.
 */
class LikeDislikeControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "like_dislike LikeDislikeController's controller functionality",
      'description' => 'Test Unit for module like_dislike and controller LikeDislikeController.',
      'group' => 'Other',
    ];
  }

  /**
   * Tests like_dislike functionality.
   */
  public function testLikeDislikeController() {
    // Check that the basic functions of module like_dislike.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
