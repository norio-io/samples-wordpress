<?php
/**
 * Title: アクセスの略図
 * Slug: mizuki-dental/access-map
 * Categories: mizuki-dental
 * Description: みずき台駅北口から医院までの略図と、経路の説明。
 *
 * @package mizuki-dental
 */

?>
<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"map"} -->
<figure class="wp-block-image size-full map"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/access-map.svg' ) ); ?>" alt="<?php echo esc_attr__( 'みずき台駅北口から当院までの略図。北口を出て駅前通りを北へ進み、みずき台郵便局の角を右に曲がった先の2つ目の建物です。', 'mizuki-dental' ); ?>" width="520" height="340"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"is-style-note"} -->
<p class="is-style-note"><?php echo esc_html__( 'みずき台駅 北口を出て駅前通りを北へ進み、みずき台郵便局の角を右に曲がった先、2つ目の建物です。', 'mizuki-dental' ); ?></p>
<!-- /wp:paragraph -->
