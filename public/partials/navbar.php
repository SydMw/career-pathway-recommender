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

<?php if (!empty($_SESSION['user_id']) && defined('SESSION_TIMEOUT_SECONDS')): ?>
<div class="session-warning no-print" id="session-warning">
    <span id="session-warning-text"></span>
    <button type="button" id="session-stay-btn">Stay signed in</button>
</div>
<script>
(function () {
    var totalSeconds = <?= (int) SESSION_TIMEOUT_SECONDS ?>;
    var warnWithSecondsLeft = 60;
    var remaining = totalSeconds;
    var banner = document.getElementById('session-warning');
    var text = document.getElementById('session-warning-text');

    function render() {
        var m = Math.floor(remaining / 60);
        var s = remaining % 60;
        text.textContent = "You'll be logged out in " + m + ":" + (s < 10 ? '0' : '') + s + " due to inactivity.";
    }

    var timer = setInterval(function () {
        remaining -= 1;
        if (remaining <= 0) {
            window.location.href = 'logout.php';
            return;
        }
        if (remaining <= warnWithSecondsLeft) {
            render();
            banner.classList.add('visible');
        }
    }, 1000);

    document.getElementById('session-stay-btn').addEventListener('click', function () {
        fetch('keep_alive.php', { credentials: 'same-origin' });
        remaining = totalSeconds;
        banner.classList.remove('visible');
    });
})();
</script>
<?php endif; ?>
