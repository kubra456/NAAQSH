<?php
// Luxury Services Showcase for NAAQŚĦ.
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

$stmt = $pdo->query(
    'SELECT s.id, s.title, s.description, s.price, s.image, s.duration_hours, c.name AS category_name
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.is_active = 1
     ORDER BY s.created_at DESC'
);
$services = $stmt->fetchAll();

function naaqshServiceImage($storedPath, $idx)
{
    $fallbacks = [
        'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1519225421980-715cb0215aed?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1537633552985-df8429e8048b?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=900&q=80'
    ];

    if (!empty($storedPath)) {
        $fullPath = __DIR__ . '/../uploads/services/' . $storedPath;
        if (file_exists($fullPath)) {
            return '/NAAQSH/uploads/services/' . $storedPath;
        }
        $altPath = __DIR__ . '/../uploads/' . $storedPath;
        if (file_exists($altPath)) {
            return '/NAAQSH/uploads/' . $storedPath;
        }
    }

    return $fallbacks[$idx % count($fallbacks)];
}
?>

<section class="section" style="padding-bottom: 2rem;">
  <div class="container">
    <div class="section-intro">
      <span class="section-kicker">Signature Offerings</span>
      <h1 class="hero-title" style="font-size: clamp(2.6rem, 5vw, 4.5rem);">Experiences tailored to your celebration.</h1>
      <p class="lead">
        From comprehensive full-service wedding production to bespoke floral curation and documentary photography packages.
      </p>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="services-grid">
      <?php foreach ($services as $idx => $s): ?>
        <article class="service-card">
          <div class="service-card-media">
            <img
              src="<?php echo htmlspecialchars(naaqshServiceImage($s['image'] ?? '', $idx)); ?>"
              alt="<?php echo htmlspecialchars($s['title']); ?>"
              loading="lazy"
            >
          </div>
          <div class="service-card-body">
            <span class="service-category"><?php echo htmlspecialchars($s['category_name']); ?></span>
            <h3><?php echo htmlspecialchars($s['title']); ?></h3>
            <p class="service-card-desc"><?php echo nl2br(htmlspecialchars($s['description'])); ?></p>
            
            <?php if (!empty($s['duration_hours'])): ?>
              <div style="font-size: 0.8rem; color: var(--color-muted); margin-bottom: 1rem;">
                <strong>Coverage:</strong> ~<?php echo (int)$s['duration_hours']; ?> Hours
              </div>
            <?php endif; ?>

            <div class="service-card-footer">
              <span class="service-price">From PKR <?php echo number_format((float)$s['price'], 2); ?></span>
              <a class="btn-link" href="/NAAQSH/public/contact.php?service=<?php echo urlencode($s['title']); ?>">
                Inquire <span class="arrow">&rarr;</span>
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Custom Package Banner -->
<section class="section section-dark" style="text-align: center;">
  <div class="container">
    <div style="max-width: 720px; margin: 0 auto;">
      <span class="section-kicker">Custom Commissions</span>
      <h2 class="section-title" style="color: #ffffff;">Need a bespoke multi-city or destination package?</h2>
      <p class="lead" style="color: rgba(255, 255, 255, 0.75); margin-bottom: 2.2rem;">
        Our creative directors curate custom packages for destination weddings, multi-day family events, and brand experiences across Pakistan and abroad.
      </p>
      <a class="btn btn-outline-white" href="/NAAQSH/public/contact.php">Request Bespoke Proposal</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
