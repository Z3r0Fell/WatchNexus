<?php
declare(strict_types=1);

$err = (string)($_GET['err'] ?? '');
?>
<div class="card" style="max-width:620px;margin:0 auto;">
  <div class="hd"><h2>Create account</h2></div>
  <div class="bd">
    <?php if ($err !== ''): ?>
      <div class="banner warn">
        <div class="badge">Oops</div>
        <div><p><?= htmlspecialchars($err) ?></p></div>
      </div>
    <?php endif; ?>

    <form method="post" action="/api/auth.php">
      <input type="hidden" name="action" value="register">

      <label>Username</label>
      <input class="input" type="text" name="display_name" required maxlength="80" autocomplete="username" placeholder="e.g. auz_the_inspector">
      <p class="small muted" style="margin-top:6px;">You can log in with either this username or your email.</p>

      <label>Email</label>
      <input class="input" type="email" name="email" required autocomplete="email" placeholder="you@example.com">

      <label>Password (12–16 + complexity) OR Passphrase (20–64, 4+ words)</label>
      <input class="input" type="password" name="password" required autocomplete="new-password">

      <button class="btn primary" type="submit">Register</button>

      <p class="muted" style="margin-top:10px;">
        Already have an account? <a href="/?page=login" data-nav>Login</a>
      </p>
    </form>
  </div>
</div>
