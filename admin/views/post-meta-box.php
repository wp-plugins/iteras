<?php

wp_nonce_field( "post".$post->ID, 'iteras_paywall_post_nonce' );

echo __('Who should be able to access this post?', $domain)."<br>";

$i = 1;
foreach ($this->access_levels as $level => $label) {

  echo '<input id="iteras-paywall-radio'.$i.'" type="radio" name="iteras-paywall" value="'.$level.'" '. ($paywall_type == $level ? 'checked="checked"' : "").'>';
  echo '<label for="iteras-paywall-radio'.$i.'" title="'.$level_descriptions[$level].'">'.$label.'</label><br>';

  $i += 1;
}

if (!$settings['profile_name'] or !$settings['paywall_id'] or !$settings['subscribe_url'])
  echo '<br><p class="description error">'.strtr(__("The ITERAS settings haven't been filled in properly. Go to the <a href='%url%'>settings page</a> to correct them.", $domain), array('%url%' => $settings_url)).'</p>';

?>
