<?php
/**
 * @file
 * Returns the HTML for a single Drupal page.
 *
 * Complete documentation for this file is available online.
 * @see https://drupal.org/node/1728148
 */

// Render the sidebars to see if there's anything in them.
$sidebar_first  = render($page['sidebar_first']);
$sidebar_second = render($page['sidebar_second']);

$layout = 'layout-full';
if (!empty($sidebar_first) && !empty($sidebar_second)) :
  $layout = 'layout-sidebars';
elseif (!empty($sidebar_first)) :
  $layout = 'layout-sidebar-first';
elseif (!empty($sidebar_second)) :
  $layout = 'layout-sidebar-second';
endif;

?>

<header class="header" id="header" role="banner">
  <div class="header__inner">

    <?php if ($secondary_menu): ?>
      <nav class="header__secondary-menu" id="secondary-menu" role="navigation">
        <?php print theme('links__system_secondary_menu', array(
          'links' => $secondary_menu,
          'attributes' => array(
            'class' => array(
              'links',
              'inlineLinks--bordered--double',
              'clearfix',
            ),
          ),
          'heading' => array(
            'text' => isset($secondary_menu_heading) ? $secondary_menu_heading : '',
            'level' => 'h2',
            'class' => array('element-invisible'),
          ),
        )); ?>
      </nav>
    <?php endif; ?>

    <?php if ($logo): ?>
      <a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" rel="home" class="header__logo" id="logo"><img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" class="header__logo-image" /></a>
    <?php endif; ?>

    <?php print render($page['header']); ?>
  </div>
</header>

<?php print render($page['navigation']); ?>

<div id="page">
  <?php print render($page['highlighted']); ?>

  <?php print $messages; ?>
  <?php print render($tabs); ?>
  <?php if ($action_links): ?>
    <ul class="action-links"><?php print render($action_links); ?></ul>
  <?php endif; ?>

  <?php print render($page['help']); ?>

  <?php print $breadcrumb; ?>

  <div id="main" class="<?php print $layout; ?>">

    <div id="content" class="<?php print $layout; ?>__main main-content" role="main">

      <a href="#skip-link" class="element-focusable" id="skip-content">Back to top</a>

      <a id="main-content"></a>
      <?php print render($title_prefix); ?>
      <?php if ($title): ?>
        <h1 class="page__title title" id="page-title"><?php print $title; ?></h1>
      <?php endif; ?>
      <?php print render($title_suffix); ?>
      <?php print render($page['content']); ?>
      <?php print $feed_icons; ?>
    </div>

    <?php if ($sidebar_first || $sidebar_second): ?>
      <aside class="sidebars" role="complementary">
        <?php if ($sidebar_first) : ?>
          <div class="<?php print $layout; ?>__sidebar-first" >
            <?php print $sidebar_first; ?>
          </div>
        <?php endif; ?>

        <?php if ($sidebar_second) : ?>
          <div class="<?php print $layout; ?>__sidebar-second" >
            <?php print $sidebar_second; ?>
          </div>
        <?php endif; ?>      </aside>
    <?php endif; ?>

  </div>

  <?php print render($page['footer']); ?>
</div>

<?php print render($page['bottom']); ?>
