<div class="wrap">

  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

  <form method="post" action="">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="profile"><?php _e('ITERAS profile name', $domain); ?></label></th>
        <td>
          <input class="regular-text" name="profile" placeholder="<?php _e('e.g. sportsmanden', $domain); ?>" type="text" value="<?=$settings['profile_name']; ?>">
          <p class="description"><?php _e('You can find your ITERAS profile name on the publication settings page here:', $domain); ?> <a target="_blank" href="http://app.iteras.dk/publisher/publications/">app.iteras.dk</a>.</p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="paywall"><?php _e('ITERAS paywall ID', $domain); ?></label></th>
        <td>
          <input class="regular-text" name="paywall" placeholder="<?php _e('e.g. CEru2uMu', $domain); ?>" type="text" value="<?=$settings['paywall_id']; ?>">
          <p class="description"><?php _e('You can find your ITERAS paywall ID on the publication settings page here:', $domain); ?> <a target="_blank" href="http://app.iteras.dk/publisher/publications/">app.iteras.dk</a>.</p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="subscribeurl"><?php _e('Subscribe landing page', $domain); ?></label></th>
        <td>
          <input class="regular-text" id="subscribeurl" name="subscribe_url" placeholder="<?php _e('e.g. /?page_id=1', $domain); ?>" type="text" value="<?=$settings['subscribe_url']; ?>">
          <p class="description"><?php _e('URL to the landing page for logging in or becoming a <b>paying subscriber</b>.', $domain); ?></p>
        </td>
      </tr>

      <tr style="display: none">
        <th scope="row"><label for="userurl"><?php _e('User landing page', $domain); ?></label></th>
        <td>
          <input class="regular-text" id="userurl" name="user_url" placeholder="<?php _e('e.g. /?page_id=2', $domain); ?>" type="text" value="<?=$settings['user_url']; ?>">
          <p class="description"><?php _e('URL to the landing page for logging in or registering as a <b>user</b>. The subscribe and user landing page can point to the same Wordpress page.', $domain); ?></p>
        </td>
      </tr>

      <tr>
        <th scope="row"><label for="defaultaccess"><?php _e('Default paywall access', $domain); ?></label></th>
        <td>
          <select id="defaultaccess" name="default_access">
            <?php foreach ($this->access_levels as $level => $label) { ?>
            <option value="<?=$level?>" <?php if ($settings['default_access'] == $level) echo 'selected="selected"' ?> ><?=$label?></option>
            <?php } ?>
          </select>

          <p class="description"><?php _e('Default paywall access for new posts.', $domain); ?></p>
        </td>
      </tr>
    </table>

    <?php submit_button(); ?>
  </form>

  <p><?php _e('For more information about the ITERAS API check out the <a target="_blank" href="https://app.iteras.dk/static/api/readme.txt">developer readme.txt</a>.', $domain); ?>

</div>
