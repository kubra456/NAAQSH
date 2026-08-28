<?php
// Editorial About Page for NAAQŚĦ.
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

// Fetch active team members from database
$teamStmt = $pdo->query('SELECT full_name, role, bio, image_path FROM team_members WHERE is_active = 1 ORDER BY id ASC');
$teamMembers = $teamStmt->fetchAll();

// Team portrait fallback helper
function naaqshTeamImage($storedPath, $defaultIdx)
{
    if (!empty($storedPath)) {
        $fullPath = __DIR__ . '/../uploads/' . $storedPath;
        if (file_exists($fullPath)) {
            return '/NAAQSH/uploads/' . $storedPath;
        }
    }

    $realTeamImages = [
        'team/team_20260820_144002_2180acb3efc5.jpg',
        'team/team_20260820_144118_d2990b4bb319.jpg',
        'team/team_20260820_144318_3668491765ca.jpg',
        'team/team_20260820_144336_41949ed2eae1.jpg',
    ];
    $idx = $defaultIdx % count($realTeamImages);
    $realPath = $realTeamImages[$idx];
    if (file_exists(__DIR__ . '/../uploads/' . $realPath)) {
        return '/NAAQSH/uploads/' . $realPath;
    }

    $fallbacks = [
        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80'
    ];

    return $fallbacks[$defaultIdx % count($fallbacks)];
}
?>

<!-- About Hero -->
<section class="section" style="padding-bottom: 2rem;">
  <div class="container">
    <div class="section-intro">
      <span class="section-kicker">About Studio NAAQŚĦ</span>
      <h1 class="hero-title" style="font-size: clamp(2.6rem, 5vw, 4.8rem);">The art of meaningful celebration.</h1>
      <p class="lead">
        NAAQŚĦ is a premier Pakistani event planning, luxury styling, and photography atelier. We bridge the warmth of traditional ceremonies with the aesthetic refinement of high-end editorial design.
      </p>
    </div>
  </div>
</section>

<!-- Studio Story Split Section -->
<section class="section section-alt">
  <div class="container">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3.5rem; align-items: center;">
      <div>
        <span class="section-kicker">Our Narrative</span>
        <h2 class="section-title">Rooted in heritage. Inspired by understated elegance.</h2>
        <p style="margin-bottom: 1.25rem; font-size: 1.05rem; color: var(--color-body); line-height: 1.7;">
          Every wedding and event is an intimate chapter in your family’s history. Our multidisciplinary studio brings together planners, floral artists, lighting designers, and documentary photographers to curate experiences that feel effortlessly authentic.
        </p>
        <p style="color: var(--color-muted); font-size: 0.95rem; line-height: 1.7;">
          We take on a limited number of commissions each season to ensure uncompromising devotion, bespoke creative direction, and seamless on-the-ground execution for every client.
        </p>
      </div>

      <div style="aspect-ratio: 4/5; overflow: hidden; border: 1px solid var(--color-border); box-shadow: var(--shadow-md);">
        <img
          src="/NAAQSH/uploads/our-narrative.jpg"
          alt="NAAQŚĦ Couple Portrait - Our Narrative"
          style="width: 100%; height: 100%; object-fit: cover;"
        >
      </div>
    </div>
  </div>
</section>

<!-- Philosophy Pillars -->
<section class="section">
  <div class="container">
    <div class="section-intro centered">
      <span class="section-kicker">Our Philosophy</span>
      <h2 class="section-title">The Three Pillars of NAAQŚĦ</h2>
    </div>

    <div class="why-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
      <article class="why-card">
        <div class="feature-icon">I</div>
        <h3>Intentional Planning</h3>
        <p>Logistical rigor, calm timelines, and vendor harmony so that you remain present in every joyous moment.</p>
      </article>

      <article class="why-card">
        <div class="feature-icon">II</div>
        <h3>Sensory Styling</h3>
        <p>Bespoke floral architecture, refined tablescapes, and ambient lighting that evoke deep emotion.</p>
      </article>

      <article class="why-card">
        <div class="feature-icon">III</div>
        <h3>Editorial Storytelling</h3>
        <p>Cinematic portraits and candid photojournalism that preserve your memories as timeless heirloom art.</p>
      </article>
    </div>
  </div>
</section>

<!-- Team Section -->
<?php if (!empty($teamMembers)): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-intro centered">
      <span class="section-kicker">Leadership & Artists</span>
      <h2 class="section-title">Meet the minds behind NAAQŚĦ</h2>
      <p class="lead">Dedicated directors and artists shaping unforgettable atmospheres across Pakistan.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
      <?php foreach ($teamMembers as $idx => $member): ?>
        <article class="service-card" style="border: 1px solid var(--color-border); background: var(--color-surface);">
          <div style="height: 320px; overflow: hidden; background: var(--color-bg-alt);">
            <img
              src="<?php echo htmlspecialchars(naaqshTeamImage($member['image_path'], $idx)); ?>"
              alt="<?php echo htmlspecialchars($member['full_name']); ?>"
              style="width: 100%; height: 100%; object-fit: cover;"
            >
          </div>
          <div style="padding: 1.75rem;">
            <span class="service-category" style="color: var(--color-champagne);"><?php echo htmlspecialchars($member['role']); ?></span>
            <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($member['full_name']); ?></h3>
            <p style="font-size: 0.88rem; color: var(--color-muted); line-height: 1.6;"><?php echo htmlspecialchars($member['bio'] ?? ''); ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Call to Action Banner -->
<section class="section section-dark" style="text-align: center;">
  <div class="container">
    <div style="max-width: 720px; margin: 0 auto;">
      <span class="section-kicker">Collaborate With Us</span>
      <h2 class="section-title" style="color: #ffffff;">Let us bring your vision to life.</h2>
      <p class="lead" style="color: rgba(255, 255, 255, 0.75); margin-bottom: 2.2rem;">
        Whether you are planning an intimate ceremony or a grand multi-day celebration, we look forward to hearing your story.
      </p>
      <a class="btn btn-outline-white" href="/NAAQSH/public/contact.php">Start the Conversation</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
