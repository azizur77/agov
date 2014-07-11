<?php

/**
 * @file
 *
 * @license GPL v2 http://www.fsf.org/licensing/licenses/gpl.html
 * @author Chris Skene chris at previousnext dot com dot au
 * @copyright Copyright(c) 2014 Previous Next Pty Ltd
 */

namespace Drupal\agov\Profile;


class StandardProfile extends MinimalProfile {

  /**
   * Create basic roles.
   */
  public function _agov_create_basic_roles_perms() {

    $weight = 1;
    $base_permissions = array();

    // Content editor.
    $roles['Content editor'] = array(
      'name' => 'Content editor',
      'weight' => $weight++,
      'permissions' => $base_permissions,
    );

    // Content approver.
    $roles['Content approver'] = array(
      'name' => 'Content approver',
      'weight' => $weight++,
      'permissions' => $base_permissions,
    );

    // Create the roles.
    foreach ($roles as $role) {
      $role_object = new \stdClass();
      $role_object->name = $role['name'];
      $role_object->weight = $role['weight'];

      // Save the role.
      user_role_save($role_object);

      // Grant permissions.
      user_role_grant_permissions($role_object->rid, $role['permissions']);
    }

    // Update the weight of the administrator role so its last.
    $admin_role = user_role_load(AGOV_ADMIN_ROLE_ID);
    $admin_role->weight = $weight++;
    user_role_save($admin_role);
  }

  /**
   * Set default themes.
   */
  public function _agov_set_default_themes() {
    // Any themes without keys here will get numeric keys and so will be enabled,
    // but not placed into variables.
    //
    // The install_state data is discarded after the last core install task, so
    // we set install_profile specifically to ensure that drupal_get_profile()
    // returns the correct profile name and finds the profile themes.
    $enable = array(
      'theme_default' => AGOV_DEFAULT_THEME,
      'admin_theme' => AGOV_DEFAULT_ADMIN_THEME,
      'install_profile' => 'agov',
    );
    theme_enable($enable);

    foreach ($enable as $var => $theme) {
      if (!is_numeric($var)) {
        variable_set($var, $theme);
      }
    }

    // Disable the default Bartik & default admin theme.
    theme_disable(array('bartik', AGOV_DEFAULT_ADMIN_THEME));
  }

  /**
   * Sets up default vocabularies.
   */
  public function _agov_enable_vocabs() {

    // Create a default vocabulary named "Tags"
    $description = st('Use tags to group articles on similar topics into categories.');
    $help = st('Enter a comma-separated list of words to describe your content.');
    $vocabulary = (object) array(
      'name' => st('Tags'),
      'description' => $description,
      'machine_name' => 'tags',
      'help' => $help,
    );
    taxonomy_vocabulary_save($vocabulary);
  }

  /**
   * Add default text formats and create a default role for administrators.
   */
  public function _agov_default_formats_and_permissions() {

    // Add text formats.
    $filtered_html_format = array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(
        // URL filter.
        'filter_url' => array(
          'weight' => 0,
          'status' => 1,
        ),
        // HTML filter.
        'filter_html' => array(
          'weight' => 1,
          'status' => 1,
        ),
        // Line break filter.
        'filter_autop' => array(
          'weight' => 2,
          'status' => 1,
        ),
        // HTML corrector filter.
        'filter_htmlcorrector' => array(
          'weight' => 10,
          'status' => 1,
        ),
      ),
    );
    $filtered_html_format = (object) $filtered_html_format;
    filter_format_save($filtered_html_format);

    $rich_text_format = array(
      'format' => 'rich_text',
      'name' => 'Rich Text',
      'weight' => 0,
      'filters' => array(
        // URL filter.
        'filter_url' => array(
          'weight' => 0,
          'status' => 1,
        ),
        // HTML filter.
        'filter_html' => array(
          'weight' => 1,
          'status' => 1,
        ),
        // Line break filter.
        'filter_autop' => array(
          'weight' => 2,
          'status' => 1,
        ),
        // HTML corrector filter.
        'filter_htmlcorrector' => array(
          'weight' => 10,
          'status' => 1,
        ),
      ),
    );
    $rich_text_format = (object) $rich_text_format;
    filter_format_save($rich_text_format);

    $full_html_format = array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(
        // URL filter.
        'filter_url' => array(
          'weight' => 0,
          'status' => 1,
        ),
        // Line break filter.
        'filter_autop' => array(
          'weight' => 1,
          'status' => 1,
        ),
        // HTML corrector filter.
        'filter_htmlcorrector' => array(
          'weight' => 10,
          'status' => 1,
        ),
      ),
    );
    $full_html_format = (object) $full_html_format;
    filter_format_save($full_html_format);

    // Enable default permissions for system roles.
    $filtered_html_permission = filter_permission_name($filtered_html_format);
    user_role_grant_permissions(
      DRUPAL_ANONYMOUS_RID,
      array(
        'access content',
        $filtered_html_permission,
        'search page content',
        'search blog_article content',
        'search event content',
        'search media_release content',
        'search news_article content',
        'search publication content',
      )
    );
    user_role_revoke_permissions(
      DRUPAL_ANONYMOUS_RID,
      array(
        'search all content',
      )
    );
    user_role_grant_permissions(
      DRUPAL_AUTHENTICATED_RID,
      array(
        'access content',
        'access comments',
        'post comments',
        'skip comment approval',
        $filtered_html_permission,
        'search page content',
        'search blog_article content',
        'search event content',
        'search media_release content',
        'search news_article content',
        'search publication content',
      )
    );
    user_role_revoke_permissions(
      DRUPAL_AUTHENTICATED_RID,
      array(
        'search all content',
      )
    );

    // Create a default role for site administrators, with all available
    // permissions assigned.
    $admin_role = new \stdClass();
    $admin_role->name = 'administrator';
    $admin_role->weight = 2;
    user_role_save($admin_role);
    user_role_grant_permissions($admin_role->rid, array_keys(module_invoke_all('permission')));
    // Set this as the administrator role.
    variable_set('user_admin_role', $admin_role->rid);

    // Assign user 1 the "administrator" role.
    db_insert('users_roles')
      ->fields(array('uid' => 1, 'rid' => $admin_role->rid))
      ->execute();
  }

  /**
   * Sets a custom date format.
   *
   * This install function works in 3 steps:
   * 1) Install the date type - Month Year format ("F Y").
   * 2) Setup a date type of Publications.
   * 3) Using variable set we then associate the format and type.
   */
  public function _agov_date_formats() {
    $t = get_t();

    // Variables.
    $date_formats = array(
      'agov_month_year' => array(
        'format' => array(
          'type' => 'agov_month_year',
          'format' => 'F Y',
          'locked' => TRUE,
          'is_new' => TRUE,
        ),
        'type' => array(
          'type' => 'agov_month_year',
          'title' => $t('Month Year Format'),
          'locked' => TRUE,
          'is_new' => TRUE,
        ),
      ),
      'agov_month_day_year' => array(
        'format' => array(
          'type' => 'agov_month_day_year',
          'format' => 'j F Y',
          'locked' => TRUE,
          'is_new' => TRUE,
        ),
        'type' => array(
          'type' => 'agov_month_day_year',
          'title' => $t('Month Day Year Format'),
          'locked' => TRUE,
          'is_new' => TRUE,
        ),
      ),
    );

    // Save the data.
    foreach ($date_formats as $type_string => $data) {
      system_date_format_save($data['format']);
      system_date_format_type_save($data['type']);
      variable_set('date_format_' . $type_string, $data['format']['format']);
    }

    variable_set('date_format_short', "d/m/Y - g:ia");
  }

  /**
   * Setup some default blocks.
   */
  public function _agov_default_blocks() {
    // Get all the aGov supported themes.
    $themes = agov_core_theme_info();

    // Set default system block in primary theme.
    agov_core_insert_block('system', 'main', $themes, 'content', -12);
    agov_core_insert_block('system', 'help', $themes, 'content', -14);
    agov_core_insert_block('superfish', '1', $themes, 'navigation');
    agov_core_insert_block('menu', 'menu-footer-sub-menu', $themes, 'footer', 3);

    // Set aGov blocks.
    agov_core_insert_block('agov_text_resize', 'text_resize', $themes, 'header');
    agov_core_insert_block('search', 'form', $themes, 'header');
    agov_core_insert_block('workbench', 'block', $themes, 'content', -14);
    agov_core_insert_block('menu_block', 'agov_menu_block-footer', $themes, 'footer', 2);
    agov_core_insert_block('views', 'slideshow-block', $themes, 'highlighted', 0, 1, '<front>');
    agov_core_insert_block('views', 'footer_teaser-block', $themes, 'footer', 1);

    // Set aGov sidebar blocks.
    agov_core_insert_block('menu_block', 'agov_menu_block-sidebar', $themes, 'sidebar_second', -50);
    agov_core_insert_block('menu', 'menu-quick-links', $themes, 'sidebar_second', -48);
    agov_core_insert_block('agov_social_links', 'services', $themes, 'sidebar_second', -47);

    // Set some blocks in the admin theme.
    agov_core_insert_block('system', 'main', AGOV_DEFAULT_ADMIN_THEME, 'content');
    agov_core_insert_block('system', 'help', AGOV_DEFAULT_ADMIN_THEME, 'help');
    agov_core_insert_block('agov_core', 'update_notification', AGOV_DEFAULT_ADMIN_THEME, 'help', 24, BLOCK_VISIBILITY_LISTED, "admin/reports/updates*");

    // Ensure Wcag validate block only appears on correct pages.
    db_update('block')->fields(array(
      'visibility' => BLOCK_VISIBILITY_LISTED,
      'pages' => "node/*\n<front>\nnews-media\nnews-media/*\npublications\npublications/*\nblog\nblog/*\ncontact\nsitemap\ntags\ntags/*",
    ))
      ->condition('module', 'wcag_validate')
      ->condition('delta', 'wcag_validate')
      ->execute();

  }

  /**
   * Insert the twitter block.
   */
  public function _agov_twitter_block() {
    // Get all the aGov supported themes.
    $themes = agov_core_theme_info();

    $data = array(
      "theme" => "",
      "link_color" => "",
      "width" => "",
      "height" => "",
      "chrome" => array(
        "noheader" => "noheader",
        "nofooter" => "nofooter",
        "noborders" => "noborders",
        "noscrollbar" => "noscrollbar",
        "transparent" => "transparent",
      ),
      "border_color" => "",
      "language" => "",
      "tweet_limit" => "",
      "related" => "",
      "polite" => "polite",
    );

    $bid = db_insert('twitter_block')
      ->fields(array(
        'info' => 'Twitter Feed',
        'widget_id' => AGOV_TWITTER_WIDGET_ID,
        'data' => serialize($data),
      ))
      ->execute();

    agov_core_insert_block('twitter_block', $bid, $themes, 'sidebar_second', -49);

  }

  /**
   * Set default variables.
   */
  public function _agov_default_variables() {

    $variables = array(

      // Pathauto default.
      'pathauto_node_pattern' => '[node:title]',
      'pathauto_punctuation_hyphen' => 1,
      'pathauto_taxonomy_term_pattern' => '[term:vocabulary]/[term:name]',
      'pathauto_user_pattern' => 'users/[user:name]',
      'path_alias_whitelist' => array(
        'node' => TRUE,
        'taxonomy' => TRUE,
        'user' => TRUE,
      ),

      // User registration.
      'user_register' => USER_REGISTER_ADMINISTRATORS_ONLY,
      'admin_theme' => AGOV_DEFAULT_ADMIN_THEME,
      'node_admin_theme' => AGOV_DEFAULT_ADMIN_THEME,

      // Turn on honeypot protection for all forms.
      'honeypot_protect_all_forms' => 1,
      // Don't disable page caching.
      'honeypot_time_limit' => "0",


      'xmlsitemap_settings_node_page' => array(
        'status' => '1',
        'priority' => '0.5',
      ),

    );

    // Enable xmlsitemap includes for content types.
    $xmlsitemap_settings = array(
      'status' => '1',
      'priority' => '0.5',
    );

    $types = array(
      'node_page',
      'node_blog_article',
      'node_event',
      'node_footer_teaser',
      'node_media_release',
      'node_news_article',
      'node_page',
      'node_publication',
      'node_webform',
    );

    foreach ($types as $type) {
      $variables['xmlsitemap_settings_' . $type] = $xmlsitemap_settings;
    }

    foreach ($variables as $key => $value) {
      variable_set($key, $value);
    }
  }

  /**
   * Set default menu items.
   */
  public function _agov_set_menu() {

    // Create a Home link in the main menu.
    $item = array(
      'link_title' => st('Home'),
      'link_path' => '<front>',
      'menu_name' => 'main-menu',
      'weight' => -50,
    );
    menu_link_save($item);

    menu_rebuild();
  }

  /**
   * Setup beans.
   */
  public function _agov_default_beans() {
    // If functionality available. Create some blocks.
    if (function_exists('agov_core_save_bean')) {
      // Create the front page block.
      $fields = array(
        'field_bean_body' => array(
          '0' => array(
            'value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque non faucibus massa. Maecenas ac dui turpis. Vivamus eu arcu tellus. Nunc sit amet tellus nunc. Quisque ac lacus nisl. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Integer consectetur, urna vitae posuere varius, sem enim blandit augue, non condimentum dolor tortor id quam. Quisque venenatis dolor neque. Nunc suscipit sem id neque lobortis ullamcorper. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.<br/><a href="about-us">Learn more about us &raquo;</a>',
            'format' => 'rich_text',
          ),
        ),
      );
      agov_core_save_bean('basic_content', 'About Us', 'Provides "About Us" block for the from page.', 'About Us', $fields);

      // Create the footer block.
      $this_year = date('Y');
      $fields = array(
        'field_bean_body' => array(
          '0' => array(
            'value' => '&#169; ' . $this_year . ' Government | Powered by <a href="http://agov.com.au">aGov</a>',
            'format' => 'rich_text',
          ),
        ),
      );
      // Get all the aGov supported themes.
      $themes = agov_core_theme_info();
      agov_core_save_bean('basic_content', 'Footer copyright', 'The copyright message', '', $fields);
      agov_core_insert_block('bean', 'footer-copyright', $themes, 'footer', 4);
    }
    else {
      drupal_set_message('aGov Core was not available. Some of your blocks could not be created.', 'error');
    }
  }

  /**
   * Sets up news related beans.
   */
  public function _agov_default_news_beans() {
    $t = get_t();

    // Ensure the bean save function is available.
    if (!function_exists('agov_core_save_bean')) {
      error_log('Function not available');
      return;
    }

    // Add menu links to main menu.
    $menus = array(
      'current-news' => 'news-media/news',
      'archived-news' => 'news-media/news-archive',
      'media-releases' => 'news-media/media-releases',
      'events' => 'news-media/events',
      'blog' => 'news-media/blog',
    );

    // @todo - Save these menu items below the news-media item.
    // Save the images required for these block.
    $files = array();
    $images = array(
      'news-media-intro' => 'news-media.jpg',
      'current-news' => 'news-archives.jpg',
      'archived-news' => 'news-archives.jpg',
      'media-releases' => 'media-releases.jpg',
      'events' => 'events.jpg',
      'blog' => 'news-media.jpg',
    );
    foreach ($images as $delta => $image) {
      // Load the files contents.
      $handle = fopen(dirname(__FILE__) . '/images/' . $image, 'r');
      // Returns the new file object.
      $files[$delta] = file_save_data($handle, 'public://' . $image, FILE_EXISTS_RENAME);
      fclose($handle);
    }

    // Install the required blocks.
    // Beans to be installed.
    $beans = array(
      // News and Media Intro block for the News & Media page.
      'news-media-intro' => array(
        'label' => $t('News and Media Intro'),
        'title' => '',
        'description' => $t('Provides a "News and Media Intro" bean that gives overview for the News & Media page.'),
        'type' => 'image_and_text',
        'view_mode' => 'hightlight',
        'fields' => array(
          // Image field.
          'field_bean_image' => array(
            '0' => array(
              'fid' => $files['news-media-intro']->fid,
              'alt' => t('News and Media Intro'),
              'title' => t('News and Media Intro'),
            ),
          ),
          // Text field.
          'field_bean_text' => array(
            '0' => array(
              'value' => 'Optional intro block with image Est porttitor hac ultricies nec integer enim scelerisque proin sagittis porttitor, sit! Magnis sit egestas turpis parturient aliquam. Mauris duis nascetur vel porttitor scelerisque cursus nec, in dis sit sagittis, lacus lacus?',
              'format' => 'rich_text',
            ),
          ),
        ),
      ),

      // Current News block.
      'current-news' => array(
        'label' => $t('Current News'),
        'title' => $t('Current News'),
        'description' => $t('Provides a "Current News" bean for the News & Media page.'),
        'type' => 'image_and_text',
        'view_mode' => 'default',
        'fields' => array(
          // Image field.
          'field_bean_image' => array(
            '0' => array(
              'fid' => $files['current-news']->fid,
              'alt' => t('Current News'),
              'title' => t('Current News'),
            ),
          ),
          // Text field.
          'field_bean_text' => array(
            '0' => array(
              'value' => 'Vel quis etiam enim pulvinar, tincidunt porttitor dignissim cum, dignissim sociis natoque, elementum nec scelerisque pulvinar, nascetur mauris mauris, a? Rhoncus augue et nunc, adipiscing tincidunt, sagittis, amet!',
              'format' => 'rich_text',
            ),
          ),
          // Link to field.
          'field_link_to' => array(
            '0' => array(
              'url' => $menus['current-news'],
              'title' => '',
              'attributes' => '',
            ),
          ),
        ),
      ),

      // Archived News block.
      'archived-news' => array(
        'label' => $t('Archived News'),
        'title' => $t('Archived News'),
        'description' => $t('Provides a "Archived News" bean for the News & Media page.'),
        'type' => 'image_and_text',
        'view_mode' => 'default',
        'fields' => array(
          // Image field.
          'field_bean_image' => array(
            '0' => array(
              'fid' => $files['archived-news']->fid,
              'alt' => t('Archived News'),
              'title' => t('Archived News'),
            ),
          ),
          // Text field.
          'field_bean_text' => array(
            '0' => array(
              'value' => 'Vel quis etiam enim pulvinar, tincidunt porttitor dignissim cum, dignissim sociis natoque, elementum nec scelerisque pulvinar, nascetur mauris mauris, a? Rhoncus augue et nunc, adipiscing tincidunt, sagittis, amet!',
              'format' => 'rich_text',
            ),
          ),
          // Link to field.
          'field_link_to' => array(
            '0' => array(
              'url' => $menus['archived-news'],
              'title' => '',
              'attributes' => '',
            ),
          ),
        ),
      ),

      // Media releases.
      'media-releases' => array(
        'label' => $t('Media Releases'),
        'title' => $t('Media Releases'),
        'description' => $t('Provides a "Media Releases" bean for the News & Media page.'),
        'type' => 'image_and_text',
        'view_mode' => 'default',
        'fields' => array(
          // Image field.
          'field_bean_image' => array(
            '0' => array(
              'fid' => $files['media-releases']->fid,
              'alt' => t('Media Releases'),
              'title' => t('Media Releases'),
            ),
          ),
          // Text field.
          'field_bean_text' => array(
            '0' => array(
              'value' => 'Vel quis etiam enim pulvinar, tincidunt porttitor dignissim cum, dignissim sociis natoque, elementum nec scelerisque pulvinar, nascetur mauris mauris, a? Rhoncus augue et nunc, adipiscing tincidunt, sagittis, amet!',
              'format' => 'rich_text',
            ),
          ),
          // Link to field.
          'field_link_to' => array(
            '0' => array(
              'url' => $menus['media-releases'],
              'title' => '',
              'attributes' => '',
            ),
          ),
        ),
      ),

      // Events.
      'events' => array(
        'label' => $t('Events'),
        'title' => $t('Events'),
        'description' => $t('Provides a "Events" bean for the News & Media page.'),
        'type' => 'image_and_text',
        'view_mode' => 'default',
        'fields' => array(
          // Image field.
          'field_bean_image' => array(
            '0' => array(
              'fid' => $files['events']->fid,
              'alt' => t('Events'),
              'title' => t('Events'),
            ),
          ),
          // Text field.
          'field_bean_text' => array(
            '0' => array(
              'value' => 'Vel quis etiam enim pulvinar, tincidunt porttitor dignissim cum, dignissim sociis natoque, elementum nec scelerisque pulvinar, nascetur mauris mauris, a? Rhoncus augue et nunc, adipiscing tincidunt, sagittis, amet!',
              'format' => 'rich_text',
            ),
          ),
          // Link to field.
          'field_link_to' => array(
            '0' => array(
              'url' => $menus['events'],
              'title' => '',
              'attributes' => '',
            ),
          ),
        ),
      ),

      // Blog.
      'blog' => array(
        'label' => $t('Blog'),
        'title' => $t('Blog'),
        'description' => $t('Provides a "Blog" bean for the News & Media page.'),
        'type' => 'image_and_text',
        'view_mode' => 'default',
        'fields' => array(
          // Image field.
          'field_bean_image' => array(
            '0' => array(
              'fid' => $files['blog']->fid,
              'alt' => t('Blog'),
              'title' => t('Blog'),
            ),
          ),
          // Text field.
          'field_bean_text' => array(
            '0' => array(
              'value' => 'Vel quis etiam enim pulvinar, tincidunt porttitor dignissim cum, dignissim sociis natoque, elementum nec scelerisque pulvinar, nascetur mauris mauris, a? Rhoncus augue et nunc, adipiscing tincidunt, sagittis, amet!',
              'format' => 'rich_text',
            ),
          ),
          // Link to field.
          'field_link_to' => array(
            '0' => array(
              'url' => $menus['blog'],
              'title' => '',
              'attributes' => '',
            ),
          ),
        ),
      ),
    );

    // Programattically create the beans using core aGov functionality.
    foreach ($beans as $bean) {
      agov_core_save_bean($bean['type'], $bean['label'], $bean['description'], $bean['title'], $bean['fields'], $bean['view_mode']);
    }
  }

  /**
   * Reverts a list of features.
   */
  public function _agov_features_revert($modules) {
    module_load_include('inc', 'features', 'features.export');
    features_include();
    foreach ($modules as $module) {
      if (($feature = feature_load($module, TRUE)) && module_exists($module)) {
        $components = array();
        // Forcefully revert all components of a feature.
        foreach (array_keys($feature->info['features']) as $component) {
          if (features_hook($component, 'features_revert')) {
            $components[] = $component;
          }
        }
      }
      foreach ($components as $component) {
        features_revert(array($module => array($component)));
      }
    }
  }

  /**
   * Created default tags.
   */
  public function _agov_default_tags() {
    // List of terms for insert.
    $terms = array(
      t('consequat'),
      t('fuisset'),
      t('maluisset'),
      t('ponderum'),
      t('prodesset'),
      t('rationibus'),
      t('voluptatibus'),
    );

    $vocabs = taxonomy_vocabulary_get_names();
    if (empty($vocabs['tags'])) {
      return;
    }

    // Save taxonomy terms.
    $vid = $vocabs['tags']->vid;
    $tids = array();

    foreach ($terms as $delta => $name) {
      $term = new \stdClass();
      $term->name = $name;
      $term->vid = $vid;
      $term->vocabulary_machine_name = 'tags';
      taxonomy_term_save($term);
      $tids[] = $term->tid;
    }

    // Save the tids.
    variable_set('agov_tags_saved', $tids);
  }

  /**
   * Implements hook_install_tasks().
   */
  public function agov_install_tasks($install_state) {
    $task = array();

    $agov_example_content_batch = variable_get('agov_example_content_batch', FALSE);

    $task['agov_install_additional_options'] = array(
      'display_name' => st('Additional options'),
      'display' => TRUE,
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    );
    $task['agov_example_content_install'] = array(
      'display_name' => st('Example content'),
      'display' => FALSE,
      'type' => 'batch',
      'run' => $agov_example_content_batch ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    );
    $task['agov_cleanup'] = array(
      'display_name' => st('Finish'),
      'display' => FALSE,
      'type' => 'normal',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    );
    return $task;
  }

  /**
   * Implements hook_install_tasks_alter().
   */
  public function agov_install_tasks_alter(&$tasks, $install_state) {

    $tasks['agov_finished'] = $tasks['install_finished'];
    unset($tasks['install_finished']);

    $this->_agov_set_theme(AGOV_INSTALL_DEFAULT_THEME);
    drupal_add_css('profiles/agov/css/install.css');
    drupal_add_js('profiles/agov/js/install.js');
  }

  /**
   * Overrides the core install_finished.
   */
  public function agov_finished() {
    drupal_set_title(st('@drupal installation complete', array('@drupal' => drupal_install_profile_distribution_name())), PASS_THROUGH);
    $messages = drupal_set_message();
    $output = '<p>' . st('Congratulations, you installed @drupal!', array('@drupal' => drupal_install_profile_distribution_name())) . '</p>';
    $output .= '<p>' . (isset($messages['error']) ? st('Review the messages above before visiting <a href="@url">your new site</a>.', array('@url' => url(''))) : st('<a href="@url" class="button">Visit your new site</a>', array('@url' => url('')))) . '</p>';

    // Flush all caches to ensure that any full bootstraps during the installer
    // do not leave stale cached data, and that any content types or other items
    // registered by the installation profile are registered correctly.

    variable_set('features_rebuild_on_flush', FALSE);
    drupal_flush_all_caches();

    // Remember the profile which was used.
    variable_set('install_profile', drupal_get_profile());

    // Installation profiles are always loaded last.
    db_update('system')
      ->fields(array('weight' => 1000))
      ->condition('type', 'module')
      ->condition('name', drupal_get_profile())
      ->execute();

    // Cache a fully-built schema.
    drupal_get_schema(NULL, TRUE);

    // Run cron to populate update status tables (if available) so that users
    // will be warned if they've installed an out of date Drupal version.
    // Will also trigger indexing of profile-supplied content or feeds.
    drupal_cron_run();
    variable_set('features_rebuild_on_flush', TRUE);

    return $output;
  }

  /**
   * Form callback for additional options install task.
   */
  public function agov_install_additional_options() {

    set_time_limit(0);

    $form = array();
    drupal_set_title('Additional options');

    $form['default_content'] = array(
      '#type' => 'fieldset',
      '#title' => st('Example content'),
    );

    $form['default_content']['install'] = array(
      '#type' => 'checkbox',
      '#title' => st('Install example content'),
    );
    $form['register'] = array(
      '#type' => 'fieldset',
      '#title' => st('Register aGov'),
    );
    $form['register']['agov_register_confirm'] = array(
      '#type' => 'checkbox',
      '#title' => st('Register your aGov site'),
    );

    $form['register']['fields'] = array(
      '#prefix' => '<div id="agov-install-register-fields">',
      '#suffix' => '</div>',
    );

    // Default registration data.
    $default_data = array(
      'agov_register_agency' => variable_get('site_name'),
      'agov_register_email' => variable_get('site_mail'),
    );
    $form['register']['fields'][] = agov_register_form_elements($default_data, TRUE);

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => st('Continue'),
    );
    return $form;
  }

  /**
   * Validate handler.
   *
   * @ingroup forms
   */
  public function agov_install_additional_options_validate(&$form, &$form_state) {
    if ($form_state['values']['agov_register_confirm']) {
      if (!valid_email_address($form_state['values']['agov_register_email'])) {
        form_set_error('agov_register_email', 'Please enter a valid email address.');
      }
    }
  }

  /**
   * Submit handler.
   *
   * @ingroup forms
   */
  public function agov_install_additional_options_submit(&$form, &$form_state) {

    // If user selects to install default content. Set var.
    if ($form_state['values']['install']) {
      variable_set('agov_example_content_batch', TRUE);
    }

    // If the user selects to register the site, post the data.
    if ($form_state['values']['agov_register_confirm']) {
      $data = array(
        'agov_register_agency' => check_plain($form_state['values']['agov_register_agency']),
        'agov_register_contact' => check_plain($form_state['values']['agov_register_contact']),
        'agov_register_email' => check_plain($form_state['values']['agov_register_email']),
        'agov_register_notification' => check_plain($form_state['values']['agov_register_notification']),
      );
      $registration = agov_register_post($data);
      if ($registration->code == 200) {
        drupal_set_message('Thank you, your registration was successful');
      }
      else {
        drupal_set_message('Sorry, your registration failed at this time. We will try again later to register your site. Visit `Configuration > aGov Registration` after installation to check the status.', 'warning');
      }

    }
    return FALSE;
  }

  /**
   * Returns modules and functions to install default/example content.
   */
  public function agov_default_content() {

    return array(
      0 => array(
        'title' => st('Events'),
        'type' => 'module',
        'key' => 'agov_content_event',
      ),
      1 => array(
        'title' => st('Publications'),
        'type' => 'module',
        'key' => 'agov_content_publication',
      ),
      2 => array(
        'title' => st('Blog'),
        'type' => 'module',
        'key' => 'agov_content_blog',
      ),
      3 => array(
        'title' => st('Media Releases'),
        'type' => 'module',
        'key' => 'agov_content_media_release',
      ),
      4 => array(
        'title' => st('News Articles'),
        'type' => 'module',
        'key' => 'agov_content_news_article',
      ),
      5 => array(
        'title' => st('Promotions'),
        'type' => 'module',
        'key' => 'agov_content_promotion',
      ),
      6 => array(
        'title' => st('Standard pages'),
        'type' => 'module',
        'key' => 'agov_content_standard_page',
      ),
      7 => array(
        'title' => st('Default Beans'),
        'type' => 'function',
        'key' => '_agov_default_beans',
        'message' => st('Enabled Default Beans'),
      ),
      8 => array(
        'title' => st('Default News Beans'),
        'type' => 'function',
        'key' => '_agov_default_news_beans',
        'message' => st('Enabled Default News Beans'),
      ),
      9 => array(
        'title' => st('Default slides'),
        'type' => 'module',
        'key' => 'agov_content_slides',
      ),
    );

  }

  /**
   * Sets the Batch API array for batch processing.
   */
  public function agov_example_content_install() {

    $operations = array();

    $steps = $this->agov_default_content();
    foreach ($steps as $content) {
      $operations[] = array(
        'agov_example_content_install_batch',
        array(
          $content['type'],
          $content['key'],
          $content['title'],
          isset($content['message']) ? $content['message'] : '',
        ),
      );
    }

    $batch = array(
      'operations' => $operations,
      'title' => st('Processing example content'),
      'init_message' => st('Initializing'),
      'error_message' => st('Error'),
      'finished' => 'agov_example_content_install_finished',
    );
    return $batch;
  }

  /**
   * Either enable module or run function for Example content batch install.
   *
   * @param string $type
   *   Type of content either 'module' or 'function'.
   * @param string $key
   *   Name of module or function to enable/run.
   * @param string $name
   *   Title/name of the module or function.
   * @param string $message
   *   Optional message to be output with function.
   * @param array $context
   *   Batch context data.
   */
  public function agov_example_content_install_batch($type, $key, $name, $message, &$context) {
    if ($type == 'module') {
      module_enable(array($key), FALSE);
      $context['results'][] = $key;
      $context['message'] = st('Enabled %module content.', array('%module' => $name));
    }
    elseif ($type == 'function') {
      call_user_func($key);
      $context['results'][] = $key;
      if ($message) {
        $context['message'] = $message;
      }
    }
  }

  /**
   * Performs any cleanup and output on completion of example content batch.
   */
  public function agov_example_content_install_finished(&$context) {

    drupal_set_message('Example content installed.');
    drupal_set_message('Sitemap has been rebuilt.');
  }

  /**
   * Run any cleanup or other functions required after install is finished.
   */
  public function agov_cleanup() {

    $this->agov_rebuild_sitemap();

    // Rebuild default config (permissions).
    defaultconfig_rebuild_all();

    // Required by view_unpublished module.
    node_access_rebuild();

    cache_clear_all();

    // @todo Image styles not being applied to beans on install.
    features_revert(array('agov_beans' => array('field')));

  }

  /**
   * Force-set a theme at any point during the execution of the request.
   *
   * Drupal doesn't give us the option to set the theme during the installation
   * process and forces enable the maintenance theme too early in the request
   * for us to modify it in a clean way.
   *
   * This function was helpfully taken from Commerce Kickstart.
   */
  public function _agov_set_theme($target_theme) {
    if ($GLOBALS['theme'] != $target_theme) {
      unset($GLOBALS['theme']);

      drupal_static_reset();
      $GLOBALS['conf']['maintenance_theme'] = $target_theme;
      _drupal_maintenance_theme();
    }
  }

  /**
   * Rebuilds the sitemap.xml.
   */
  public function agov_rebuild_sitemap() {
    if (module_exists('xmlsitemap')) {
      module_load_include('generate.inc', 'xmlsitemap');

      // Build a list of rebuildable link types.
      $rebuild_types = xmlsitemap_get_rebuildable_link_types();

      // Run the batch process.
      xmlsitemap_run_unprogressive_batch('xmlsitemap_rebuild_batch', $rebuild_types, TRUE);
    }
  }

  /**
   * Sets up default password policy.
   *
   * We don't have an API so insert into the db directly.
   */
  public function _agov_password_policy() {

    // Define the password policy.
    $policy = array(
      "alphanumeric" => "1",
      "complexity" => "3",
      "delay" => "24",
      "history" => "8",
      "length" => "9",
      "letter" => "1",
      "alphanumeric" => "1",
    );

    // Insert the policy definition.
    $pid = db_insert('password_policy')
      ->fields(array(
        'name' => 'Australian Government DSD Policy',
        'description' => 'Password policy that conforms to Australian Government Information Security Manual guidelines 2012 September release.',
        'enabled' => 1,
        'policy' => serialize($policy),
        'expiration' => 90,
        'warning' => 7,
      ))
      ->execute();

    // Get all role ids.
    $rids = db_query("SELECT rid FROM {role}")->fetchAll(PDO::FETCH_COLUMN);

    // Insert the roles that use this policy.
    $query = db_insert('password_policy_role')->fields(array('pid', 'rid'));
    foreach ($rids as $rid) {
      // No need to add anonymous.
      if ($rid != DRUPAL_ANONYMOUS_RID) {
        $query->values(array($pid, $rid));
      }
    }
    $query->execute();

  }

  /**
   * Set defaults for contact form.
   */
  public function _agov_contact_form_defaults() {

    // Set default recipient for contact form.
    db_update('contact')->fields(
      array('recipients' => variable_get('site_mail', 'admin@example.com'))
    )
      ->condition('cid', 1)
      ->execute();
  }

  /**
   * Set up full display mode for media wysiwyg image display.
   */
  public function _agov_install_full_image_display() {
    variable_set('field_bundle_settings_file__image', array(
      'view_modes' => array(
        'teaser' => array('custom_settings' => TRUE),
        'full' => array('custom_settings' => TRUE),
        'preview' => array('custom_settings' => TRUE),
        'rss' => array('custom_settings' => FALSE),
        'search_index' => array('custom_settings' => FALSE),
        'search_result' => array('custom_settings' => FALSE),
        'token' => array('custom_settings' => FALSE),
      ),
      'extra_fields' => array(
        'form' => array(),
        'display' => array(
          'file' => array(
            'default' => array(
              'weight' => '0',
              'visible' => TRUE,
            ),
            'full' => array(
              'weight' => '0',
              'visible' => TRUE,
            ),
          ),
        ),
      ),
    ));
  }

}
