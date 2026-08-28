<?php
// Luxury editorial homepage for NAAQŚĦ.
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

// Fetch featured/recent services from database
$servicesStmt = $pdo->query(
    'SELECT s.id, s.title, s.description, s.price, s.image, c.name AS category_name
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.is_active = 1
     ORDER BY s.id ASC
     LIMIT 3'
);
$featuredServices = $servicesStmt->fetchAll();

// Fetch featured gallery items from database
$galleryStmt = $pdo->query(
    'SELECT id, title, image_path, caption
     FROM gallery
     WHERE is_featured = 1 OR id IS NOT NULL
     ORDER BY is_featured DESC, id ASC
     LIMIT 5'
);
$galleryItems = $galleryStmt->fetchAll();

// Fetch confirmed / completed events for social proof
$eventsStmt = $pdo->query(
    'SELECT e.id, e.title, e.event_type, e.event_date, u.full_name
     FROM events e
     INNER JOIN users u ON u.id = e.user_id
     WHERE e.status IN (\'confirmed\', \'completed\')
     ORDER BY e.event_date DESC
     LIMIT 3'
);
$featuredEvents = $eventsStmt->fetchAll();

// Pakistani event types
$eventTypes = [
    'Weddings & Receptions',
    'Nikah Ceremonies',
    'Mehndi & Sangeet',
    'Barat Planning',
    'Walima Coordination',
    'Bridal Styling Suites',
    'Corporate Galas & Launches',
    'Intimate Private Celebrations'
];

// Why NAAQŚĦ core pillars
$whyPoints = [
    [
        'icon' => '✦',
        'title' => 'Thoughtful Planning',
        'text' => 'Every detail, timeline, and vendor is meticulously aligned with your personal vision and emotional priorities.'
    ],
    [
        'icon' => '✧',
        'title' => 'Refined Styling',
        'text' => 'Sensory floral concepts, ambient lighting, and bespoke stage backdrops designed with timeless elegance.'
    ],
    [
        'icon' => '✺',
        'title' => 'Personalized Experiences',
        'text' => 'From intimate family dinners to grand 500+ guest celebrations, every attendee touchpoint is crafted with intention.'
    ],
    [
        'icon' => '✹',
        'title' => 'Captured Beautifully',
        'text' => 'Cinematic and editorial visual documentation that preserves the authentic soul of your most cherished day.'
    ]
];

// Client Testimonials
$testimonials = [
    [
        'name' => 'Areeba & Hassan',
        'detail' => 'Lahore Grand Hall Wedding',
        'quote' => 'NAAQŚĦ brought calmness, sublime beauty, and immaculate precision to our 3-day wedding. Our families were able to simply enjoy every celebration.'
    ],
    [
        'name' => 'Muneeb Qureshi',
        'detail' => 'Executive Corporate Gala',
        'quote' => 'Their styling transformed our annual brand launch into an unforgettable experience. The attention to lighting and guest flow was world-class.'
    ],
    [
        'name' => 'Sana & Usman',
        'detail' => 'Intimate Nikah & Reception',
        'quote' => 'The floral direction and editorial photography exceeded every dream we had. Working with NAAQŚĦ was effortlessly luxurious.'
    ]
];

/**
 * Image resolver with luxury fallback and multi-directory resolution
 */
function naaqshImageUrl($storedPath, $fallbackUrl = '')
{
    $basePath = '/NAAQSH';
    if (!empty($storedPath)) {
        $cleanPath = ltrim(str_replace('services/', '', $storedPath), '/');
        if (file_exists(__DIR__ . '/../uploads/services/' . $cleanPath)) {
            return $basePath . '/uploads/services/' . $cleanPath;
        }
        if (file_exists(__DIR__ . '/../uploads/' . $storedPath)) {
            return $basePath . '/uploads/' . $storedPath;
        }
        if (file_exists(__DIR__ . '/../uploads/services/' . $storedPath)) {
            return $basePath . '/uploads/services/' . $storedPath;
        }
        if (file_exists(__DIR__ . '/../uploads/gallery/' . $storedPath)) {
            return $basePath . '/uploads/gallery/' . $storedPath;
        }
    }
    return $fallbackUrl ?: 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80';
}
?>

<!-- 1. Cinematic Hero -->
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="section-kicker">Luxury Event Studio · Pakistan</span>
      <h1 class="hero-title">Moments, beautifully orchestrated.</h1>
      <p class="lead">
        From intimate nikah ceremonies to breathtaking celebrations, NAAQŚĦ crafts bespoke planning, luxury styling, and editorial visual stories tailored to you.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="/NAAQSH/public/plan_event.php">Plan Your Event</a>
        <a class="btn btn-secondary" href="/NAAQSH/public/portfolio.php">View Our Work</a>
      </div>
    </div>

    <div class="hero-metrics-box">
      <h3>The Studio At A Glance</h3>
      <div class="metrics-grid">
        <div class="stat-item">
          <span class="stat-number">120+</span>
          <span class="stat-label">Events Styled</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">9 Yrs</span>
          <span class="stat-label">Experience</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">50+</span>
          <span class="stat-label">Weddings</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">100%</span>
          <span class="stat-label">Bespoke</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 2. Introduction Section -->
<section class="section section-alt">
  <div class="container">
    <div class="section-intro centered">
      <span class="section-kicker">A Studio For Significant Moments</span>
      <h2 class="section-title">Where timeless Pakistani tradition meets contemporary editorial design.</h2>
      <p class="lead">
        We believe a truly unforgettable celebration is born at the intersection of heartfelt hospitality, architectural floral aesthetics, and seamless logistical mastery.
      </p>
    </div>
  </div>
</section>

<!-- 3. Signature Services -->
<section class="section">
  <div class="container">
    <div class="section-intro">
      <span class="section-kicker">Our Offerings</span>
      <h2 class="section-title">Signature services for elevated celebrations.</h2>
      <p class="lead">Each service is tailored with dedicated coordinators, bespoke aesthetic moodboards, and trusted vendor partnerships.</p>
    </div>

    <div class="services-grid">
      <?php 
      foreach ($featuredServices as $service): 
          $imgUrl = naaqshImageUrl($service['image'] ?? '', '');
      ?>
        <article class="service-card">
          <div class="service-card-media">
            <img
              src="<?php echo htmlspecialchars($imgUrl); ?>"
              alt="<?php echo htmlspecialchars($service['title']); ?>"
              loading="lazy"
            >
          </div>
          <div class="service-card-body">
            <span class="service-category"><?php echo htmlspecialchars($service['category_name']); ?></span>
            <h3><?php echo htmlspecialchars($service['title']); ?></h3>
            <p class="service-card-desc"><?php echo htmlspecialchars($service['description']); ?></p>
            <div class="service-card-footer">
              <span class="service-price">PKR <?php echo number_format((float)$service['price'], 2); ?></span>
              <a class="btn-link" href="/NAAQSH/public/contact.php">Inquire <span class="arrow">&rarr;</span></a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 4. Selected Work / Portfolio Preview -->
<section class="section section-alt">
  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1.5rem;">
      <div>
        <span class="section-kicker">Curated Portfolio</span>
        <h2 class="section-title" style="margin-bottom: 0;">Recent stories & spaces.</h2>
      </div>
      <a class="btn btn-secondary" href="/NAAQSH/public/portfolio.php">View Full Portfolio</a>
    </div>

    <div class="portfolio-grid">
      <?php 
      $curatedPortfolioItems = [
          [
              'title' => 'Signature Wedding Setup',
              'caption' => 'A luxury wedding venue styled with floral installations and warm ambient lighting.',
              'image_path' => 'gallery/7d7b51288c8cff36.png',
              'span' => 'span-12'
          ],
          [
              'title' => 'Bride and Groom Portraits',
              'caption' => 'Editorial portraits captured in natural light for a premium wedding story.',
              'image_path' => 'gallery/fbbf5e61a7c0725d.png',
              'span' => 'span-6'
          ],
          [
              'title' => 'Corporate Launch Stage',
              'caption' => 'Modern event design for a product launch and networking reception.',
              'image_path' => 'gallery/ec41d303817e08a3.png',
              'span' => 'span-6'
          ],
          [
              'title' => 'An Evening of Timeless Celebration',
              'caption' => 'Intimate candlelit reception dining, warm ambient lighting, and bespoke floral architecture.',
              'image_path' => 'gallery/26c1c903c1f4c734.png',
              'span' => 'span-6'
          ],
          [
              'title' => 'Royal Walima Stage',
              'caption' => 'Custom floral stage designed for grand luxury reception.',
              'image_path' => 'gallery/c3aa0876ea54bd6c.png',
              'span' => 'span-6'
          ]
      ];
      foreach ($curatedPortfolioItems as $galleryItem): 
          $imgUrl = naaqshImageUrl($galleryItem['image_path'], '');
      ?>
        <article class="portfolio-card <?php echo $galleryItem['span']; ?>">
          <img
            src="<?php echo htmlspecialchars($imgUrl); ?>"
            alt="<?php echo htmlspecialchars($galleryItem['title']); ?>"
            loading="lazy"
          >
          <div class="portfolio-card-overlay">
            <h3 class="portfolio-card-title"><?php echo htmlspecialchars($galleryItem['title']); ?></h3>
            <p class="portfolio-card-caption"><?php echo htmlspecialchars($galleryItem['caption']); ?></p>
            <span class="portfolio-card-action">VIEW STORY <span class="arrow">&rarr;</span></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 5. Why NAAQŚĦ (Pillars) -->
<section class="section">
  <div class="container">
    <div class="section-intro centered">
      <span class="section-kicker">The NAAQŚĦ Difference</span>
      <h2 class="section-title">Designed with reverence. Executed with precision.</h2>
    </div>

    <div class="why-grid">
      <?php foreach ($whyPoints as $item): ?>
        <article class="why-card">
          <div class="feature-icon" aria-hidden="true"><?php echo htmlspecialchars($item['icon']); ?></div>
          <h3><?php echo htmlspecialchars($item['title']); ?></h3>
          <p><?php echo htmlspecialchars($item['text']); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 6. Event Types Cloud -->
<section class="section section-alt" style="padding-top: 4rem; padding-bottom: 4rem;">
  <div class="container">
    <div class="section-intro centered" style="margin-bottom: 1.5rem;">
      <span class="section-kicker">Celebration Spectrum</span>
      <h2 class="section-title" style="font-size: 2.2rem;">Experiences across every chapter of life.</h2>
    </div>

    <div class="event-types-wrap">
      <?php foreach ($eventTypes as $type): ?>
        <span class="event-type-pill"><?php echo htmlspecialchars($type); ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 7. Testimonials -->
<section class="section">
  <div class="container">
    <div class="section-intro">
      <span class="section-kicker">Client Stories</span>
      <h2 class="section-title">Enduring words from couples & families.</h2>
    </div>

    <div class="testimonial-grid">
      <?php foreach ($testimonials as $t): ?>
        <article class="testimonial-card">
          <p class="testimonial-quote">&ldquo;<?php echo htmlspecialchars($t['quote']); ?>&rdquo;</p>
          <div class="testimonial-author">
            <span class="testimonial-name"><?php echo htmlspecialchars($t['name']); ?></span>
            <span class="testimonial-event"><?php echo htmlspecialchars($t['detail']); ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 8. Final Dark Call-to-Action -->
<section class="section section-dark" style="text-align: center;">
  <div class="container">
    <div style="max-width: 760px; margin: 0 auto;">
      <span class="section-kicker">Begin Your Celebration</span>
      <h2 class="section-title" style="color: #ffffff; font-size: clamp(2.8rem, 4.5vw, 4.2rem);">Let's create something unforgettable.</h2>
      <p class="lead" style="color: rgba(255, 255, 255, 0.75); margin-bottom: 2.5rem;">
        Dates for our 2026 and 2027 wedding seasons are now open for consultation. Contact our directors to begin shaping your story.
      </p>
      <a class="btn btn-outline-white" href="/NAAQSH/public/plan_event.php">Plan Your Event</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
