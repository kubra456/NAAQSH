<?php
// Customer-facing Event Planning Form for NAAQŚĦ
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

$isLoggedIn = !empty($_SESSION['customer_id']);
$loggedInUser = null;

if ($isLoggedIn) {
    $userStmt = $pdo->prepare('SELECT id, full_name, email, phone FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([(int)$_SESSION['customer_id']]);
    $loggedInUser = $userStmt->fetch();
}

// Fetch active services for selection
$servicesStmt = $pdo->query('
    SELECT s.id, s.title, s.price, s.description, c.name AS category_name
    FROM services s
    INNER JOIN categories c ON c.id = s.category_id
    WHERE s.is_active = 1
    ORDER BY c.name ASC, s.price ASC
');
$availableServices = $servicesStmt->fetchAll();

$errors = [];
$submittedEvent = null;

// Event type options
$eventTypeOptions = [
    'Wedding (Multi-Day)',
    'Nikah Ceremony',
    'Barat Planning & Production',
    'Walima Reception',
    'Mehndi & Sangeet',
    'Engagement & Bridal Suite',
    'Corporate Gala & Launch',
    'Private Dinner & Anniversary',
    'Other Bespoke Gathering'
];

// Theme & aesthetic presets
$themeOptions = [
    'Royal Mughal & Traditional Opulence',
    'Contemporary Minimalist & White Florals',
    'Pastel Garden & Sensory Botanicals',
    'Candlelit Romantic Evening & Warm Ambience',
    'Modern Architectural & High-Contrast Luxury',
    'Custom Bespoke Aesthetic'
];

// Inspiration categories mapping (Form input key => Database category)
$inspirationCategories = [
    'inspiration_venue' => ['label' => 'Venue Inspiration', 'db' => 'Venue'],
    'inspiration_decoration' => ['label' => 'Decoration Inspiration', 'db' => 'Decoration'],
    'inspiration_food' => ['label' => 'Food & Catering Inspiration', 'db' => 'Food & Catering'],
    'inspiration_dress' => ['label' => 'Dress & Bridal Inspiration', 'db' => 'Dress & Bridal'],
    'inspiration_photography' => ['label' => 'Photography Inspiration', 'db' => 'Photography'],
    'inspiration_makeup' => ['label' => 'Makeup & Styling Inspiration', 'db' => 'Makeup & Styling'],
    'inspiration_theme' => ['label' => 'Theme & Colors Inspiration', 'db' => 'Theme & Colors'],
    'inspiration_other' => ['label' => 'Other Inspiration', 'db' => 'Other']
];

if (!function_exists('processInspirationUploads')) {
    function processInspirationUploads(PDO $pdo, int $eventId, array $filesArray, array $categories, array &$errors): int {
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    $maxFileSize = 5 * 1024 * 1024; // 5MB limit
    $uploadDir = __DIR__ . '/../uploads/inspirations/';

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $uploadedCount = 0;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $insertStmt = $pdo->prepare('
        INSERT INTO event_inspirations (event_id, category, image_path)
        VALUES (?, ?, ?)
    ');

    foreach ($categories as $inputKey => $meta) {
        $dbCategory = $meta['db'];
        if (!isset($filesArray[$inputKey]) || !is_array($filesArray[$inputKey]['name'])) {
            continue;
        }

        $names = $filesArray[$inputKey]['name'];
        $tmpNames = $filesArray[$inputKey]['tmp_name'];
        $sizes = $filesArray[$inputKey]['size'];
        $errCodes = $filesArray[$inputKey]['error'];

        foreach ($names as $idx => $origName) {
            $errCode = $errCodes[$idx] ?? UPLOAD_ERR_NO_FILE;
            if ($errCode === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($errCode !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading '{$origName}' under {$meta['label']}.";
                continue;
            }

            $tmpPath = $tmpNames[$idx];
            $size = $sizes[$idx];

            if ($size > $maxFileSize) {
                $errors[] = "File '{$origName}' under {$meta['label']} exceeds the 5MB size limit.";
                continue;
            }

            $mime = finfo_file($finfo, $tmpPath);
            if (!isset($allowedMimes[$mime])) {
                $errors[] = "File '{$origName}' under {$meta['label']} is invalid. Only JPG, PNG, and WEBP image files are accepted.";
                continue;
            }

            $ext = $allowedMimes[$mime];
            $randomFilename = 'insp_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $destination = $uploadDir . $randomFilename;
            $dbImagePath = 'uploads/inspirations/' . $randomFilename;

            $isMoved = is_uploaded_file($tmpPath) ? move_uploaded_file($tmpPath, $destination) : copy($tmpPath, $destination);
            if ($isMoved) {
                $insertStmt->execute([$eventId, $dbCategory, $dbImagePath]);
                $uploadedCount++;
            } else {
                $errors[] = "Could not save uploaded image '{$origName}'.";
            }
        }
    }

    finfo_close($finfo);
    return $uploadedCount;
}
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // 1. Customer Information
    $customerName = '';
    $customerEmail = '';
    $customerPhone = '';
    $userId = 0;

    if ($isLoggedIn && $loggedInUser) {
        $userId = (int)$loggedInUser['id'];
        $customerName = $loggedInUser['full_name'];
        $customerEmail = $loggedInUser['email'];
        $customerPhone = $loggedInUser['phone'] ?? '';
    } else {
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');

        if ($customerName === '') {
            $errors[] = 'Please enter your full name.';
        }
        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
    }

    // 2. Event Information
    $title = trim($_POST['title'] ?? '');
    $eventType = trim($_POST['event_type'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $guestCountRaw = $_POST['guest_count'] ?? '';
    $budgetRaw = $_POST['budget'] ?? '';
    $themeStyle = trim($_POST['theme_style'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $selectedServices = isset($_POST['services']) && is_array($_POST['services']) ? array_map('intval', $_POST['services']) : [];

    if ($title === '') {
        $errors[] = 'Please provide an event or celebration name.';
    }
    if ($eventType === '') {
        $errors[] = 'Please select an event type.';
    }

    if ($eventDate === '') {
        $errors[] = 'Please select the date of your event.';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $eventDate);
        if (!$dt || $dt->format('Y-m-d') !== $eventDate) {
            $errors[] = 'Please enter a valid event date format (YYYY-MM-DD).';
        }
    }

    $guestCount = 0;
    if ($guestCountRaw !== '') {
        if (!is_numeric($guestCountRaw) || (int)$guestCountRaw < 0) {
            $errors[] = 'Guest count must be a non-negative number.';
        } else {
            $guestCount = (int)$guestCountRaw;
        }
    }

    $budget = 0.00;
    if ($budgetRaw !== '') {
        if (!is_numeric($budgetRaw) || (float)$budgetRaw < 0) {
            $errors[] = 'Budget must be a non-negative number.';
        } else {
            $budget = (float)$budgetRaw;
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Resolve customer account if guest submission
            if (!$userId) {
                $findUser = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ? LIMIT 1');
                $findUser->execute([$customerEmail]);
                $existing = $findUser->fetch();

                if ($existing) {
                    $userId = (int)$existing['id'];
                } else {
                    $tempHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                    $createUser = $pdo->prepare('
                        INSERT INTO users (full_name, email, phone, password_hash, status)
                        VALUES (?, ?, ?, ?, "active")
                    ');
                    $createUser->execute([$customerName, $customerEmail, $customerPhone ?: null, $tempHash]);
                    $userId = (int)$pdo->lastInsertId();
                }

                // Auto-login session for guest user
                $_SESSION['customer_id'] = $userId;
                $_SESSION['customer_name'] = $customerName;
            }

            // Consolidate theme/notes if provided
            $noteParts = [];
            if ($themeStyle !== '') {
                $noteParts[] = "Theme / Aesthetic: " . $themeStyle;
            }
            if ($notes !== '') {
                $noteParts[] = "Planning Notes & Vision:\n" . $notes;
            }
            $finalNotes = !empty($noteParts) ? implode("\n\n", $noteParts) : null;

            // Insert into `events` table
            $insertEvent = $pdo->prepare('
                INSERT INTO events (user_id, title, event_type, event_date, venue, guest_count, budget, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, "draft", ?)
            ');
            $insertEvent->execute([
                $userId,
                $title,
                $eventType,
                $eventDate,
                $venue !== '' ? $venue : null,
                $guestCount,
                $budget,
                $finalNotes
            ]);
            $eventId = (int)$pdo->lastInsertId();

            // Insert selected services as pending bookings if any
            $bookedServicesList = [];
            if (!empty($selectedServices)) {
                $placeholders = implode(',', array_fill(0, count($selectedServices), '?'));
                $svcQuery = $pdo->prepare("SELECT id, title, price FROM services WHERE id IN ($placeholders) AND is_active = 1");
                $svcQuery->execute($selectedServices);
                $validServices = $svcQuery->fetchAll();

                if (!empty($validServices)) {
                    $insertBooking = $pdo->prepare('
                        INSERT INTO bookings (user_id, event_id, service_id, quantity, total_price, status, notes)
                        VALUES (?, ?, ?, 1, ?, "pending", ?)
                    ');
                    foreach ($validServices as $vs) {
                        $insertBooking->execute([
                            $userId,
                            $eventId,
                            (int)$vs['id'],
                            (float)$vs['price'],
                            'Selected in event planning consultation form'
                        ]);
                        $bookedServicesList[] = $vs['title'];
                    }
                }
            }

            // Process inspiration uploads safely
            $uploadErrors = [];
            $uploadedImageCount = processInspirationUploads($pdo, $eventId, $_FILES, $inspirationCategories, $uploadErrors);

            if (!empty($uploadErrors)) {
                throw new Exception(implode(' ', $uploadErrors));
            }

            $pdo->commit();

            $submittedEvent = [
                'id' => $eventId,
                'title' => $title,
                'event_type' => $eventType,
                'event_date' => $eventDate,
                'venue' => $venue,
                'guest_count' => $guestCount,
                'budget' => $budget,
                'booked_services' => $bookedServicesList,
                'inspiration_count' => $uploadedImageCount
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'An error occurred while saving your event plan: ' . $e->getMessage();
        }
    }
}
?>

<section class="section" style="padding-bottom: 2rem;">
  <div class="container">
    <div class="section-intro centered">
      <span class="section-kicker">Event Consultation & Planning</span>
      <h1 class="hero-title" style="font-size: clamp(2.4rem, 4.5vw, 4.4rem);">Plan Your Celebration</h1>
      <p class="lead">
        Share your vision, target date, scale, and desired services. Our event directors will review your event details and coordinate with you.
      </p>
    </div>
  </div>
</section>

<section class="section section-alt" style="padding-top: 2rem; padding-bottom: 5rem;">
  <div class="container" style="max-width: 900px;">

    <?php if ($submittedEvent): ?>
      <!-- Success Confirmation Banner -->
      <div style="background: var(--color-surface); border: 1px solid var(--color-champagne); padding: 3rem 2.5rem; box-shadow: var(--shadow-md); text-align: center;">
        <div style="display: inline-flex; width: 64px; height: 64px; border-radius: 50%; background: var(--color-champagne-soft); color: var(--color-charcoal); align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.5rem;">
          &#10003;
        </div>
        
        <span class="section-kicker" style="color: var(--color-status-confirmed);">Event Registered</span>
        <h2 style="font-size: clamp(2rem, 3vw, 2.8rem); margin-bottom: 0.75rem;">
          <?php echo htmlspecialchars($submittedEvent['title']); ?>
        </h2>
        <p class="lead" style="font-size: 1.05rem; margin-bottom: 2rem; color: var(--color-body);">
          Your celebration plan #<?php echo (int)$submittedEvent['id']; ?> has been saved successfully in your account
          <?php echo (!empty($submittedEvent['inspiration_count'])) ? ' along with ' . (int)$submittedEvent['inspiration_count'] . ' inspiration reference images' : ''; ?>.
        </p>

        <!-- Summary Details Box -->
        <div style="text-align: left; background: var(--color-bg-alt); border: 1px solid var(--color-border); padding: 1.75rem 2rem; margin-bottom: 2.5rem;">
          <h3 style="font-size: 1.3rem; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem;">Event Summary</h3>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.92rem;">
            <div>
              <strong style="color: var(--color-muted); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">Event Type</strong>
              <span><?php echo htmlspecialchars($submittedEvent['event_type']); ?></span>
            </div>
            <div>
              <strong style="color: var(--color-muted); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">Date</strong>
              <span><?php echo htmlspecialchars(date('d F Y', strtotime($submittedEvent['event_date']))); ?></span>
            </div>
            <div>
              <strong style="color: var(--color-muted); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">Venue</strong>
              <span><?php echo htmlspecialchars($submittedEvent['venue'] ?: 'To Be Confirmed'); ?></span>
            </div>
            <div>
              <strong style="color: var(--color-muted); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">Guest Count</strong>
              <span><?php echo number_format((int)$submittedEvent['guest_count']); ?> Guests</span>
            </div>
            <?php if ($submittedEvent['budget'] > 0): ?>
              <div>
                <strong style="color: var(--color-muted); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">Estimated Budget</strong>
                <span>PKR <?php echo number_format((float)$submittedEvent['budget'], 2); ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($submittedEvent['inspiration_count'])): ?>
              <div>
                <strong style="color: var(--color-muted); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">Inspiration Images</strong>
                <span><?php echo (int)$submittedEvent['inspiration_count']; ?> Uploaded</span>
              </div>
            <?php endif; ?>
            <?php if (!empty($submittedEvent['booked_services'])): ?>
              <div style="grid-column: 1 / -1;">
                <strong style="color: var(--color-muted); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.35rem;">Selected Offerings</strong>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                  <?php foreach ($submittedEvent['booked_services'] as $bs): ?>
                    <span class="event-type-pill" style="margin: 0; font-size: 0.8rem;"><?php echo htmlspecialchars($bs); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
          <a href="/NAAQSH/customer/dashboard.php" class="btn btn-primary">Go to Client Portal &rarr;</a>
          <a href="/NAAQSH/public/plan_event.php" class="btn btn-secondary">Plan Another Event</a>
        </div>
      </div>

    <?php else: ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
          <ul style="margin: 0; padding-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
              <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: clamp(2rem, 4vw, 3rem); box-shadow: var(--shadow-md);">
        
        <form method="post" action="/NAAQSH/public/plan_event.php" enctype="multipart/form-data" novalidate>
          
          <!-- 1. Client Details Section -->
          <div style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 2rem;">
            <span class="section-kicker">Section 1</span>
            <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Client & Contact Details</h2>
            
            <?php if ($isLoggedIn && $loggedInUser): ?>
              <div style="display: flex; align-items: center; justify-content: space-between; background: var(--color-champagne-soft); border: 1px solid var(--color-champagne-light); padding: 1rem 1.25rem; margin-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                  <strong style="color: var(--color-charcoal); display: block;"><?php echo htmlspecialchars($loggedInUser['full_name']); ?></strong>
                  <span style="font-size: 0.88rem; color: var(--color-muted);"><?php echo htmlspecialchars($loggedInUser['email']); ?> <?php echo !empty($loggedInUser['phone']) ? ' · ' . htmlspecialchars($loggedInUser['phone']) : ''; ?></span>
                </div>
                <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.12em; font-weight: 700; color: var(--color-status-confirmed);">Authenticated Client</span>
              </div>
            <?php else: ?>
              <p style="font-size: 0.9rem; color: var(--color-muted); margin-bottom: 1.25rem;">
                Already have an account? <a href="/NAAQSH/customer/login.php" style="text-decoration: underline; font-weight: 600; color: var(--color-charcoal);">Sign in</a> to automatically link this celebration to your client portal.
              </p>
              <div class="form-grid">
                <div class="form-group">
                  <label for="customer_name">Your Full Name *</label>
                  <input id="customer_name" name="customer_name" type="text" value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>" required placeholder="e.g. Sana Khan">
                </div>

                <div class="form-group">
                  <label for="customer_email">Email Address *</label>
                  <input id="customer_email" name="customer_email" type="email" value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>" required placeholder="name@example.com">
                </div>

                <div class="form-group full-width">
                  <label for="customer_phone">Phone / WhatsApp Number</label>
                  <input id="customer_phone" name="customer_phone" type="tel" value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>" placeholder="+92 300 1234567">
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- 2. Event Specification Section -->
          <div style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 2rem;">
            <span class="section-kicker">Section 2</span>
            <h2 style="font-size: 1.8rem; margin-bottom: 1rem;">Celebration Details</h2>

            <div class="form-grid">
              <div class="form-group full-width">
                <label for="title">Event Name *</label>
                <input id="title" name="title" type="text" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required placeholder="e.g. Sana & Areeb's Wedding Celebration">
              </div>

              <div class="form-group">
                <label for="event_type">Event Type *</label>
                <select id="event_type" name="event_type" required>
                  <option value="">Select Event Classification</option>
                  <?php foreach ($eventTypeOptions as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo (($_POST['event_type'] ?? '') === $opt) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($opt); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="event_date">Event Date *</label>
                <input id="event_date" name="event_date" type="date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>" required>
              </div>

              <div class="form-group full-width">
                <label for="venue">Preferred Venue / City</label>
                <input id="venue" name="venue" type="text" value="<?php echo htmlspecialchars($_POST['venue'] ?? ''); ?>" placeholder="e.g. Lahore Grand Hall / Islamabad Farmhouse">
              </div>

              <div class="form-group">
                <label for="guest_count">Estimated Number of Guests</label>
                <input id="guest_count" name="guest_count" type="number" min="0" step="10" value="<?php echo htmlspecialchars($_POST['guest_count'] ?? '250'); ?>" placeholder="e.g. 350">
              </div>

              <div class="form-group">
                <label for="budget">Allocated Budget (PKR)</label>
                <input id="budget" name="budget" type="number" min="0" step="1000" value="<?php echo htmlspecialchars($_POST['budget'] ?? '150000'); ?>" placeholder="e.g. 250000">
              </div>

              <div class="form-group full-width">
                <label for="theme_style">Desired Theme & Aesthetic Direction</label>
                <select id="theme_style" name="theme_style">
                  <option value="">Select an Aesthetic Concept (Optional)</option>
                  <?php foreach ($themeOptions as $thm): ?>
                    <option value="<?php echo htmlspecialchars($thm); ?>" <?php echo (($_POST['theme_style'] ?? '') === $thm) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($thm); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group full-width">
                <label for="notes">Notes / Additional Requirements</label>
                <textarea id="notes" name="notes" rows="4" placeholder="Tell us about special floral preferences, stage requirements, bridal timelines, or specific vendor needs..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
              </div>
            </div>
          </div>

          <!-- 3. Inspiration Board Upload Section -->
          <div style="margin-bottom: 2.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 2rem;">
            <span class="section-kicker">Section 3 (Optional)</span>
            <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Your Inspiration Board</h2>
            <p style="font-size: 0.92rem; color: var(--color-muted); margin-bottom: 1.5rem;">
              Share reference images that help us understand your vision for the event. Select multiple images together or add more images per category.
            </p>

            <div class="form-grid">
              <?php foreach ($inspirationCategories as $inputKey => $meta): ?>
                <div class="form-group inspiration-upload-card" style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 1.25rem; border-radius: 4px; display: flex; flex-direction: column;">
                  <label for="<?php echo $inputKey; ?>" style="font-size: 0.92rem; font-weight: 700; color: var(--color-charcoal); display: block; margin-bottom: 0.35rem;">
                    <?php echo htmlspecialchars($meta['label']); ?>
                  </label>
                  
                  <input id="<?php echo $inputKey; ?>" 
                         name="<?php echo $inputKey; ?>[]" 
                         type="file" 
                         multiple="multiple" 
                         accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" 
                         class="inspiration-file-input" 
                         data-input-key="<?php echo $inputKey; ?>"
                         style="font-size: 0.82rem; width: 100%;">

                  <span style="display: block; font-size: 0.74rem; color: var(--color-muted); margin-top: 0.35rem; margin-bottom: 0.5rem;">
                    Select multiple images (JPG, PNG, or WEBP &bull; Max 5MB each)
                  </span>

                  <!-- Cumulative Image Thumbnail Preview Grid -->
                  <div id="preview_<?php echo $inputKey; ?>" class="inspiration-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(75px, 1fr)); gap: 0.5rem; margin-top: 0.5rem;"></div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <style>
              .preview-thumb-card {
                position: relative;
                width: 100%;
                height: 75px;
                border-radius: 4px;
                overflow: hidden;
                border: 1px solid var(--color-border);
                background: var(--color-surface);
                box-shadow: var(--shadow-sm);
              }
              .preview-thumb-card img {
                width: 100%;
                height: 100%;
                object-fit: cover;
              }
              .preview-thumb-remove {
                position: absolute;
                top: 2px;
                right: 2px;
                background: rgba(220, 38, 38, 0.9);
                color: #ffffff;
                border: none;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 11px;
                font-weight: bold;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                transition: background 0.2s ease;
              }
              .preview-thumb-remove:hover {
                background: #b91c1c;
              }
            </style>

            <script>
              document.addEventListener('DOMContentLoaded', function() {
                const categoryStore = {};

                document.querySelectorAll('.inspiration-file-input').forEach(function(input) {
                  const key = input.dataset.inputKey || input.id;
                  categoryStore[key] = new DataTransfer();

                  input.addEventListener('change', function() {
                    const dt = categoryStore[key];
                    const newFiles = Array.from(input.files);

                    if (newFiles.length === 0) return;

                    newFiles.forEach(function(file) {
                      let isDuplicate = false;
                      for (let i = 0; i < dt.files.length; i++) {
                        if (dt.files[i].name === file.name && dt.files[i].size === file.size) {
                          isDuplicate = true;
                          break;
                        }
                      }
                      if (!isDuplicate) {
                        dt.items.add(file);
                      }
                    });

                    input.files = dt.files;
                    renderPreviews(key, input, dt);
                  });
                });

                function renderPreviews(key, input, dt) {
                  const container = document.getElementById('preview_' + key);
                  if (!container) return;

                  container.innerHTML = '';

                  if (dt.files.length === 0) return;

                  Array.from(dt.files).forEach(function(file, index) {
                    const card = document.createElement('div');
                    card.className = 'preview-thumb-card';

                    const img = document.createElement('img');
                    img.alt = file.name;

                    if (file.type.startsWith('image/')) {
                      const reader = new FileReader();
                      reader.onload = function(e) {
                        img.src = e.target.result;
                      };
                      reader.readAsDataURL(file);
                    } else {
                      img.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="%23888" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
                    }

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'preview-thumb-remove';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.title = 'Remove ' + file.name;
                    removeBtn.addEventListener('click', function(e) {
                      e.preventDefault();
                      e.stopPropagation();

                      const newDt = new DataTransfer();
                      Array.from(dt.files).forEach(function(f, i) {
                        if (i !== index) {
                          newDt.items.add(f);
                        }
                      });

                      categoryStore[key] = newDt;
                      input.files = newDt.files;
                      renderPreviews(key, input, newDt);
                    });

                    card.appendChild(img);
                    card.appendChild(removeBtn);
                    container.appendChild(card);
                  });
                }
              });
            </script>
          </div>

          <!-- 4. Signature Services Selection -->
          <?php if (!empty($availableServices)): ?>
            <div style="margin-bottom: 2.5rem;">
              <span class="section-kicker">Section 4</span>
              <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Signature Services Required</h2>
              <p style="font-size: 0.92rem; color: var(--color-muted); margin-bottom: 1.5rem;">
                Select the offerings you would like included in your initial proposal (optional):
              </p>

              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                <?php 
                $postedServices = isset($_POST['services']) && is_array($_POST['services']) ? array_map('intval', $_POST['services']) : [];
                foreach ($availableServices as $svc): 
                  $isChecked = in_array((int)$svc['id'], $postedServices, true);
                ?>
                  <label style="display: block; background: var(--color-bg); border: 1px solid var(--color-border); padding: 1.2rem; cursor: pointer; transition: border-color 0.2s ease;">
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                      <input type="checkbox" name="services[]" value="<?php echo (int)$svc['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?> style="margin-top: 0.25rem; width: 18px; height: 18px; accent-color: var(--color-charcoal);">
                      <div>
                        <span class="service-category" style="font-size: 0.68rem; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($svc['category_name']); ?></span>
                        <strong style="display: block; font-size: 1rem; color: var(--color-charcoal); line-height: 1.3; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($svc['title']); ?></strong>
                        <span style="font-size: 0.85rem; font-weight: 700; color: var(--color-rose);">From PKR <?php echo number_format((float)$svc['price'], 2); ?></span>
                      </div>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Submit Button -->
          <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="width: 100%; min-height: 52px; font-size: 0.85rem;">
              Submit Event Plan &amp; Request Consultation &rarr;
            </button>
            <p style="text-align: center; font-size: 0.82rem; color: var(--color-muted); margin-top: 1rem;">
              Submitting creates a celebration plan in your account.
            </p>
          </div>

        </form>

      </div>

    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
