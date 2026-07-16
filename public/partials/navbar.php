<?php
/**
 * Shared navbar shown at the top of every page.
 *
 * Before including this file, set:
 *   $navbar_admin (optional) - set to true to add "| Admin" to the title
 *   $navbar_greeting (optional) - a welcome message like "Hi, John"
 *   $navbar_links - a list of links to show, each with an 'href' and a 'text'
 */

$brand = 'Career Pathway Recommender';
if (!empty($navbar_admin)) {
    $brand = $brand . ' | Admin';
}
?>
<div class="navbar no-print">
    <div class="brand"><?= htmlspecialchars($brand) ?></div>
    <div>
        <?php if (!empty($navbar_greeting)): ?>
            <span><?= htmlspecialchars($navbar_greeting) ?></span>
        <?php endif; ?>
        <?php foreach ($navbar_links as $link): ?>
            <a href="<?= htmlspecialchars($link['href']) ?>"><?= htmlspecialchars($link['text']) ?></a>
        <?php endforeach; ?>
    </div>
</div>
