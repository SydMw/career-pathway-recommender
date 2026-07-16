<?php
/**
 * Shared navbar. Set $navbar_admin = true for the "| Admin" brand suffix,
 * and $navbar_links to the right-side HTML (usually built with ob_start())
 * before including this file.
 */
?>
<div class="navbar no-print">
    <div class="brand">Career Pathway Recommender<?= !empty($navbar_admin) ? ' &nbsp;|&nbsp; Admin' : '' ?></div>
    <div>
        <?= $navbar_links ?? '' ?>
    </div>
</div>
