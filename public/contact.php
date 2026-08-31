<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$errors = [];
$success = '';
$prefillSubject = trim($_GET['service'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? ($prefillSubject ?: 'General Consultation'));
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $errors[] = 'Please provide your full name, email address, and event details.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $pdo = getPDO();
        $userId = !empty($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;

        $stmt = $pdo->prepare(
            'INSERT INTO inquiries (user_id, full_name, email, phone, subject, message, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $name,
            $email,
            $phone ?: null,
            $subject ?: 'Event Inquiry',
            $message,
            'new'
        ]);
        $success = 'Thank you for reaching out. Your consultation request has been received. Our event directors will contact you within 24 hours.';
    }
}
?>

<section class="section" style="padding-bottom: 2rem;">
  <div class="container">
    <div class="section-intro">
      <span class="section-kicker">Consultations & Inquiries</span>
      <h1 class="hero-title" style="font-size: clamp(2.6rem, 5vw, 4.8rem);">Let's create something beautiful.</h1>
      <p class="lead">
        We would be honored to learn about your upcoming celebration. Complete the form below to begin shaping your bespoke experience.
      </p>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3.5rem; align-items: start;">
      
      <!-- Studio Information Block -->
      <div>
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 2.5rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
          <span class="section-kicker">Atelier Details</span>
          <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;">NAAQŚĦ Studio</h2>

          <div style="display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.95rem;">
            <div>
              <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--color-muted); margin-bottom: 0.25rem;">Location</strong>
              Gulberg III, Lahore, Pakistan
            </div>

            <div>
              <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--color-muted); margin-bottom: 0.25rem;">Direct Telephone</strong>
              <a href="tel:+923001234567" style="color: var(--color-charcoal); font-weight: 600;">+92 300 1234567</a>
            </div>

            <div>
              <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--color-muted); margin-bottom: 0.25rem;">Direct Email</strong>
              <a href="mailto:info@naaqsh.pk" style="color: var(--color-charcoal); font-weight: 600;">info@naaqsh.pk</a>
            </div>

            <div>
              <strong style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--color-muted); margin-bottom: 0.25rem;">Consultation Hours</strong>
              Monday through Saturday<br>
              10:00 AM – 7:00 PM PKT
            </div>
          </div>
        </div>

        <div style="padding: 1.5rem; background: var(--color-champagne-soft); border: 1px solid var(--color-champagne-light);">
          <strong style="display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.4rem; color: var(--color-charcoal);">What to Expect</strong>
          <p style="font-size: 0.9rem; color: var(--color-body); margin: 0; line-height: 1.6;">
            Upon receiving your inquiry, an event director will review your date availability and schedule a discovery consultation via phone, video, or in-person at our Lahore studio.
          </p>
        </div>
      </div>

      <!-- Contact Form Block -->
      <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: clamp(2rem, 4vw, 3rem); box-shadow: var(--shadow-md);">
        <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Event Inquiry Form</h2>
        <p style="color: var(--color-muted); font-size: 0.92rem; margin-bottom: 2rem;">Please provide details about your dates, estimated guests, and vision.</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-error">
            <?php echo htmlspecialchars(implode(' ', $errors)); ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>

        <form method="post" action="/NAAQSH/public/contact.php" class="contact-form">
          <div class="form-grid">
            <div class="form-group full-width">
              <label for="name">Your Full Name *</label>
              <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($_POST['name'] ?? ($_SESSION['customer_name'] ?? '')); ?>" required placeholder="e.g. Sana Khan">
            </div>

            <div class="form-group">
              <label for="email">Email Address *</label>
              <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="name@example.com">
            </div>

            <div class="form-group">
              <label for="phone">Phone / WhatsApp</label>
              <input id="phone" name="phone" type="tel" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+92 300 0000000">
            </div>

            <div class="form-group full-width">
              <label for="subject">Event Subject / Service</label>
              <input id="subject" name="subject" type="text" value="<?php echo htmlspecialchars($_POST['subject'] ?? ($prefillSubject ?: '')); ?>" placeholder="e.g. 3-Day Wedding Planning in Lahore">
            </div>

            <div class="form-group full-width">
              <label for="message">Tell Us About Your Celebration *</label>
              <textarea id="message" name="message" rows="5" required placeholder="Share your estimated dates, venue, guest count, and creative ideas..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
          </div>

          <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message &rarr;</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
