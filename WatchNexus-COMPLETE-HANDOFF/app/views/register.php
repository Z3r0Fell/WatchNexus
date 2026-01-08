<?php
declare(strict_types=1);

$err = (string)($_GET['err'] ?? '');
?>
<div class="card">
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

      <label>Email</label>
      <input class="input" type="email" name="email" required autocomplete="email">

      <label>Display name (optional)</label>
      <input class="input" type="text" name="display_name" maxlength="120" autocomplete="nickname">

      <label>Password (12–16 + complexity) OR Passphrase (20–64, 3+ words)</label>
      <input class="input" type="password" name="password" required autocomplete="new-password">

      <button class="btn primary" type="submit">Register</button>

      <p class="muted" style="margin-top:10px;">
        Already have an account? <a href="/?page=login">Login</a>
      </p>
    </form>
  </div>
</div>
