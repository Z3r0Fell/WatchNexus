<?php
declare(strict_types=1);

$err = (string)($_GET['err'] ?? '');
?>
<div class="card" style="max-width:540px;margin:0 auto;">
  <div class="hd">
    <h2>Login</h2>
    <div class="spacer"></div>
    <span class="small">Optional</span>
  </div>

  <div class="bd">
    <p class="small">Calendar is public. Sign in only if you want tracking, integrations, and themes.</p>

    <?php if ($err !== ''): ?>
      <div class="banner warn">
        <div class="badge">Error</div>
        <div><p><?= htmlspecialchars($err) ?></p></div>
      </div>
    <?php endif; ?>

    <form method="post" action="/api/auth.php">
      <input type="hidden" name="action" value="login">

      <label>Email or username</label>
      <input class="input" type="text" name="identifier" required autocomplete="username" placeholder="you@example.com  /  yourname">

      <label>Password</label>
      <input class="input" type="password" name="password" required autocomplete="current-password">

      <div class="row mt16">
        <button class="btn primary" type="submit">Login</button>
        <a class="btn" href="/?page=calendar" data-nav>Back to calendar</a>
      </div>

      <p class="small mt16">
        No account? <a href="/?page=register" data-nav>Register</a>
      </p>
    </form>
  </div>
</div>
