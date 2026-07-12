</main>
<footer class="site-footer">
  <div class="site-footer-inner">
    <div class="site-footer-brand">
      <span class="site-footer-title">My Game Circle</span>
      <span class="site-footer-tagline">Self-hosted board game collection for you and your playgroup</span>
    </div>
    <?php if ($loggedIn): ?>
      <nav class="site-footer-links">
        <a href="/">My collection</a>
        <a href="/wishlist.php">My wishlist</a>
        <a href="/playgroups.php">Our playgroup</a>
        <a href="/gamenights.php">Our gamenights</a>
      </nav>
    <?php endif; ?>
  </div>
  <div class="site-footer-inner site-footer-bottom">
    <p>&copy; <?= date('Y') ?> My Game Circle</p>
    <a href="https://boardgamegeek.com" target="_blank" rel="noopener" class="bgg-badge" aria-label="Powered by BGG">
      <img src="/assets/img/powered-by-bgg.png" alt="Powered by BGG">
    </a>
  </div>
</footer>
<script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<script src="/assets/app.js"></script>
</body>
</html>
