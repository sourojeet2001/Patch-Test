# Like/Dislike
Like/Dislike module can be used to Like and Dislike actions on any content. It is powered by Drupal field concept.

#### Features covered
1. Only Authenticated Users will be able to Like or Dislike on the Content.
2. Anonymous Users will be asked to Login or Register, when they click on Like or Dislike.
3. Each User can have either one Like or one Dislike, but will not have both on a particular content

## Installation
Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration
1. The Like/Dislike field can be added to any Entity type (like Content types, Comment types etc).
2. Let's consider a case of Article content type.
3. Add the like/dislike field to the article content type from the url `admin/structure/types/manage/article/fields`,
4. Save the field with default settings.
5. Add the content of article type
6. You would be able to see Like and Dislike button on that article content.
7. One can like or dislike the content.

## Customizations
1. You can style the css class `like_dislike`, which is rendered on the content.
2. One can create own field formatter for the like dislike field and render the like and dislike as expected.
