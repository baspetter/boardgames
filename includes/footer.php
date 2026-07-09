</main>
<footer class="site-footer">
  <div class="site-footer-inner">
    <div class="site-footer-brand">
      <span class="site-footer-title">My Game Circle</span>
      <span class="site-footer-tagline">Zelf-gehoste bordspellencollectie voor jou en je speelgroep</span>
    </div>
    <?php if ($loggedIn): ?>
      <nav class="site-footer-links">
        <a href="/">Collectie</a>
        <a href="/playgroups.php">Playgroups</a>
        <a href="/speelavond.php">Speelavond</a>
      </nav>
    <?php endif; ?>
  </div>
  <div class="site-footer-inner site-footer-bottom">
    <p>&copy; <?= date('Y') ?> My Game Circle</p>
    <p>Spelinformatie en afbeeldingen via <a href="https://boardgamegeek.com" target="_blank" rel="noopener">BoardGameGeek</a></p>
  </div>
</footer>
<script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<script src="/assets/app.js"></script>
</body>
</html>
