<?php
// Curated Editorial Portfolio for NAAQŚĦ.
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();
$stmt = $pdo->query('SELECT id, title, image_path, caption, is_featured FROM gallery ORDER BY created_at DESC');
$items = $stmt->fetchAll();

function naaqshGalleryImage($storedPath, $idx)
{
    $fallbacks = [
        'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1606800052052-a08af7148866?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1519225421980-715cb0215aed?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=1200&q=80',
        'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?auto=format&fit=crop&w=1200&q=80'
    ];

    if (!empty($storedPath)) {
        $fullPath = __DIR__ . '/../uploads/' . $storedPath;
        if (file_exists($fullPath)) {
            return '/NAAQSH/uploads/' . $storedPath;
        }
    }
    return $fallbacks[$idx % count($fallbacks)];
}
?>

<section class="section" style="padding-bottom: 2.5rem;">
  <div class="container">
    <div class="section-intro">
      <span class="section-kicker">Curated Portfolio</span>
      <h1 class="hero-title" style="font-size: clamp(2.6rem, 5vw, 4.8rem);">Selected stories & spaces.</h1>
      <p class="lead">
        A visual collection of bespoke wedding environments, documentary portraits, and luxury celebrations shaped by NAAQŚĦ.
      </p>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <?php if (empty($items)): ?>
      <div style="text-align: center; padding: 4rem 1rem;">
        <p class="lead">No portfolio entries currently available.</p>
      </div>
    <?php else: ?>
      <div class="portfolio-grid">
        <?php foreach ($items as $idx => $it): ?>
          <?php
            // Assign varying spans for an editorial visual rhythm
            $spanClass = 'span-6';
            if ($idx === 0) {
                $spanClass = 'span-12';
            } elseif ($idx % 5 === 1 || $idx % 5 === 2) {
                $spanClass = 'span-6';
            }
          ?>
          <article class="portfolio-card <?php echo $spanClass; ?>">
            <img
              src="<?php echo htmlspecialchars(naaqshGalleryImage($it['image_path'], $idx)); ?>"
              alt="<?php echo htmlspecialchars($it['title']); ?>"
              loading="lazy"
            >
            <div class="portfolio-card-overlay">
              <?php if (!empty($it['is_featured'])): ?>
                <span style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2em; color: var(--color-champagne-light); margin-bottom: 0.3rem;">Featured Story</span>
              <?php endif; ?>
              <h3 class="portfolio-card-title"><?php echo htmlspecialchars($it['title']); ?></h3>
              <?php if (!empty($it['caption'])): ?>
                <p class="portfolio-card-caption"><?php echo htmlspecialchars($it['caption']); ?></p>
              <?php endif; ?>
              <span class="portfolio-card-action">View Story <span class="arrow">&rarr;</span></span>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Portfolio Footer CTA -->
<section class="section section-dark" style="text-align: center;">
  <div class="container">
    <div style="max-width: 720px; margin: 0 auto;">
      <span class="section-kicker">Your Story Awaits</span>
      <h2 class="section-title" style="color: #ffffff;">Ready to create your own NAAQŚĦ?</h2>
      <p class="lead" style="color: rgba(255, 255, 255, 0.75); margin-bottom: 2.2rem;">
        Let our creative team design, style, and capture your upcoming celebration.
      </p>
      <a class="btn btn-outline-white" href="/NAAQSH/public/plan_event.php">Plan Your Event</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
