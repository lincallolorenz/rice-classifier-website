<?php
// ─── CONFIG ───────────────────────────────────────────────
// After deploying to Hugging Face, replace this URL:
$API_URL = "https://rorrenzz-rice-classifier.hf.space/predict";
// ──────────────────────────────────────────────────────────

$result     = null;
$error      = null;
$preview    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        // Validate image
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed)) {
            // Base64 preview for display
            $imgData   = file_get_contents($file['tmp_name']);
            $preview   = 'data:' . $mime . ';base64,' . base64_encode($imgData);

            // Send to Python API
            $curl = curl_init();
            $cfile = new CURLFile($file['tmp_name'], $mime, $file['name']);

            curl_setopt_array($curl, [
                CURLOPT_URL            => $API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => ['image' => $cfile],
                CURLOPT_TIMEOUT        => 30,
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($response && $httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['prediction'])) {
                    $result = $data;
                } else {
                    $error = "Unexpected response from the model.";
                }
            } else {
                $error = "Could not connect to the model API. Please try again.";
            }
        } else {
            $error = "Please upload a valid image (JPG, PNG, or WebP).";
        }
    } else {
        $error = "File upload failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rice Plant Health Classifier — Group 11</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --pup-maroon:  #7B1113;
      --pup-dark:    #5A0C0E;
      --pup-light:   #F9F2F2;
      --pup-accent:  #C8303A;
      --healthy:     #1A7A4A;
      --unhealthy:   #C8303A;
      --text:        #1C1C1E;
      --text-muted:  #6E6E73;
      --border:      #E5E1E0;
      --bg:          #FAFAF8;
      --card:        #FFFFFF;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── HEADER ── */
    header {
      background: var(--pup-maroon);
      padding: 0 2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      height: 68px;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }

    .header-logo {
      width: 40px;
      height: 40px;
      background: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 13px;
      color: var(--pup-maroon);
      flex-shrink: 0;
      letter-spacing: -0.5px;
    }

    .header-title {
      font-family: 'DM Serif Display', serif;
      color: #fff;
      font-size: 1.15rem;
      line-height: 1.2;
    }

    .header-sub {
      color: rgba(255,255,255,0.65);
      font-size: 0.75rem;
      margin-top: 1px;
    }

    .header-badge {
      margin-left: auto;
      background: rgba(255,255,255,0.15);
      color: #fff;
      font-size: 0.72rem;
      padding: 4px 10px;
      border-radius: 20px;
      font-weight: 500;
    }

    /* ── HERO ── */
    .hero {
      background: linear-gradient(135deg, var(--pup-dark) 0%, var(--pup-maroon) 60%, var(--pup-accent) 100%);
      padding: 3.5rem 2rem;
      text-align: center;
      color: #fff;
    }

    .hero-eyebrow {
      font-size: 0.78rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: rgba(255,255,255,0.65);
      margin-bottom: 0.75rem;
    }

    .hero h1 {
      font-family: 'DM Serif Display', serif;
      font-size: clamp(1.8rem, 5vw, 2.8rem);
      margin-bottom: 0.75rem;
      line-height: 1.15;
    }

    .hero p {
      color: rgba(255,255,255,0.75);
      font-size: 1rem;
      max-width: 500px;
      margin: 0 auto;
      line-height: 1.6;
    }

    /* ── STATS BAR ── */
    .stats-bar {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: center;
      gap: 0;
    }

    .stat-item {
      padding: 1rem 2.5rem;
      text-align: center;
      border-right: 1px solid var(--border);
    }

    .stat-item:last-child { border-right: none; }

    .stat-val {
      font-family: 'DM Serif Display', serif;
      font-size: 1.5rem;
      color: var(--pup-maroon);
      line-height: 1;
    }

    .stat-label {
      font-size: 0.72rem;
      color: var(--text-muted);
      margin-top: 4px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    /* ── MAIN LAYOUT ── */
    main {
      max-width: 960px;
      margin: 2.5rem auto;
      padding: 0 1.5rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      align-items: start;
    }

    @media (max-width: 640px) {
      main { grid-template-columns: 1fr; }
      .stats-bar { flex-wrap: wrap; }
      .stat-item { flex: 1 1 50%; border-right: none; border-bottom: 1px solid var(--border); }
    }

    /* ── CARD ── */
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 1.75rem;
    }

    .card-title {
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text);
    }

    .card-icon {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      background: var(--pup-light);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    /* ── UPLOAD ZONE ── */
    .upload-zone {
      border: 2px dashed var(--border);
      border-radius: 12px;
      padding: 2rem 1rem;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      position: relative;
      background: var(--bg);
    }

    .upload-zone:hover,
    .upload-zone.dragover {
      border-color: var(--pup-maroon);
      background: var(--pup-light);
    }

    .upload-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }

    .upload-icon {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
    }

    .upload-text {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.5;
    }

    .upload-text strong {
      color: var(--pup-maroon);
    }

    /* ── PREVIEW ── */
    .preview-wrap {
      margin-top: 1rem;
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--border);
      background: #f5f5f5;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 160px;
    }

    .preview-wrap img {
      width: 100%;
      max-height: 220px;
      object-fit: contain;
      display: block;
    }

    /* ── SUBMIT BTN ── */
    .btn-submit {
      margin-top: 1rem;
      width: 100%;
      padding: 0.85rem;
      background: var(--pup-maroon);
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      font-size: 0.95rem;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
      letter-spacing: 0.3px;
    }

    .btn-submit:hover  { background: var(--pup-dark); }
    .btn-submit:active { transform: scale(0.98); }
    .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

    /* ── RESULT CARD ── */
    .result-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 260px;
      color: var(--text-muted);
      text-align: center;
      gap: 0.5rem;
    }

    .result-empty .big-icon { font-size: 3rem; opacity: 0.3; }

    .result-box {
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
    }

    .result-box.healthy {
      background: #F0FAF4;
      border: 1px solid #A8DCC0;
    }

    .result-box.unhealthy {
      background: #FEF2F2;
      border: 1px solid #F5C6C6;
    }

    .result-emoji { font-size: 3rem; margin-bottom: 0.5rem; }

    .result-label {
      font-family: 'DM Serif Display', serif;
      font-size: 1.8rem;
      font-weight: 400;
    }

    .result-label.healthy   { color: var(--healthy); }
    .result-label.unhealthy { color: var(--unhealthy); }

    .result-confidence {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-top: 0.35rem;
    }

    /* ── CONFIDENCE BAR ── */
    .conf-bar-wrap {
      margin-top: 1.25rem;
    }

    .conf-bar-label {
      display: flex;
      justify-content: space-between;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 6px;
    }

    .conf-bar-track {
      height: 8px;
      background: #E8E8E8;
      border-radius: 99px;
      overflow: hidden;
    }

    .conf-bar-fill {
      height: 100%;
      border-radius: 99px;
      transition: width 0.6s ease;
    }

    .conf-bar-fill.healthy   { background: var(--healthy); }
    .conf-bar-fill.unhealthy { background: var(--unhealthy); }

    /* ── DETAILS TABLE ── */
    .details-table {
      margin-top: 1.25rem;
      width: 100%;
      font-size: 0.83rem;
      border-collapse: collapse;
    }

    .details-table td {
      padding: 7px 0;
      border-bottom: 1px solid var(--border);
    }

    .details-table td:first-child {
      color: var(--text-muted);
      width: 48%;
    }

    .details-table td:last-child {
      text-align: right;
      font-weight: 500;
    }

    .details-table tr:last-child td { border-bottom: none; }

    /* ── ERROR ── */
    .error-box {
      background: #FEF2F2;
      border: 1px solid #F5C6C6;
      border-radius: 10px;
      padding: 1rem 1.25rem;
      font-size: 0.875rem;
      color: #B91C1C;
      display: flex;
      gap: 8px;
      align-items: flex-start;
      margin-top: 1rem;
    }

    /* ── HOW IT WORKS ── */
    .how-section {
      grid-column: 1 / -1;
      margin-top: 0.5rem;
    }

    .steps-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
    }

    .step-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.25rem 1rem;
      text-align: center;
    }

    .step-num {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: var(--pup-maroon);
      color: #fff;
      font-size: 0.8rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 0.75rem;
    }

    .step-title {
      font-weight: 600;
      font-size: 0.88rem;
      margin-bottom: 0.3rem;
    }

    .step-desc {
      font-size: 0.78rem;
      color: var(--text-muted);
      line-height: 1.5;
    }

    /* ── FOOTER ── */
    footer {
      text-align: center;
      padding: 2.5rem 1rem;
      color: var(--text-muted);
      font-size: 0.78rem;
      border-top: 1px solid var(--border);
      margin-top: 2rem;
    }

    footer strong { color: var(--pup-maroon); }

    .spinner {
      display: none;
      width: 18px;
      height: 18px;
      border: 2px solid rgba(255,255,255,0.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
      margin: 0 auto;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

<!-- HEADER -->
<header>
  <div class="header-logo">PUP</div>
  <div>
    <div class="header-title">Polytechnic University of the Philippines</div>
    <div class="header-sub">BS Computer Science — Machine Learning Project</div>
  </div>
  <span class="header-badge">Group 11</span>
</header>

<!-- HERO -->
<div class="hero">
  <p class="hero-eyebrow">Midterm Project</p>
  <h1>🌾 Rice Plant Health Classifier</h1>
  <p>Upload a photo of a rice plant and our trained SVM model will determine if it is healthy or unhealthy in seconds.</p>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stat-item">
    <div class="stat-val">94.17%</div>
    <div class="stat-label">Model Accuracy</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">600</div>
    <div class="stat-label">Training Images</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">SVM</div>
    <div class="stat-label">Algorithm</div>
  </div>
  <div class="stat-item">
    <div class="stat-val">RBF</div>
    <div class="stat-label">Kernel</div>
  </div>
</div>

<!-- MAIN -->
<main>

  <!-- LEFT: Upload Form -->
  <div class="card">
    <div class="card-title">
      <div class="card-icon">📤</div>
      Upload Rice Plant Image
    </div>

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
      <div class="upload-zone" id="dropZone">
        <input type="file" name="image" id="imageInput" accept="image/*" required>
        <div class="upload-icon">🖼️</div>
        <div class="upload-text">
          <strong>Click to upload</strong> or drag & drop<br>
          JPG, PNG, or WebP accepted
        </div>
      </div>

      <?php if ($preview): ?>
      <div class="preview-wrap">
        <img src="<?= htmlspecialchars($preview) ?>" alt="Uploaded image preview">
      </div>
      <?php else: ?>
      <div class="preview-wrap" id="previewWrap" style="display:none;">
        <img id="previewImg" src="" alt="Preview">
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <button type="submit" class="btn-submit" id="submitBtn">
        <span id="btnText">Analyze Plant</span>
        <div class="spinner" id="spinner"></div>
      </button>
    </form>
  </div>

  <!-- RIGHT: Result -->
  <div class="card">
    <div class="card-title">
      <div class="card-icon">🔍</div>
      Classification Result
    </div>

    <?php if ($result): ?>
      <?php
        $isHealthy  = $result['prediction'] === 'Healthy';
        $cls        = $isHealthy ? 'healthy' : 'unhealthy';
        $emoji      = $isHealthy ? '🟢' : '🔴';
        $confidence = $result['confidence'];
      ?>
      <div class="result-box <?= $cls ?>">
        <div class="result-emoji"><?= $emoji ?></div>
        <div class="result-label <?= $cls ?>"><?= htmlspecialchars($result['prediction']) ?></div>
        <div class="result-confidence">Model confidence: <?= $confidence ?>%</div>

        <div class="conf-bar-wrap">
          <div class="conf-bar-label">
            <span>Confidence</span>
            <span><?= $confidence ?>%</span>
          </div>
          <div class="conf-bar-track">
            <div class="conf-bar-fill <?= $cls ?>" style="width: <?= $confidence ?>%"></div>
          </div>
        </div>
      </div>

      <table class="details-table">
        <tr>
          <td>Prediction</td>
          <td><?= htmlspecialchars($result['prediction']) ?></td>
        </tr>
        <tr>
          <td>Confidence Score</td>
          <td><?= $confidence ?>%</td>
        </tr>
        <tr>
          <td>Algorithm</td>
          <td>SVM (RBF Kernel)</td>
        </tr>
        <tr>
          <td>Features Used</td>
          <td>RGB + HOG + LBP</td>
        </tr>
        <tr>
          <td>PCA Components</td>
          <td>150</td>
        </tr>
        <tr>
          <td>Training Accuracy</td>
          <td>94.17%</td>
        </tr>
      </table>

    <?php else: ?>
      <div class="result-empty">
        <div class="big-icon">🌾</div>
        <p style="font-weight:500">No image analyzed yet</p>
        <p style="font-size:0.82rem">Upload a rice plant photo to see the classification result here.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- HOW IT WORKS -->
  <div class="how-section">
    <div class="card">
      <div class="card-title">
        <div class="card-icon">⚙️</div>
        How It Works
      </div>
      <div class="steps-grid">
        <div class="step-card">
          <div class="step-num">1</div>
          <div class="step-title">Image Upload</div>
          <div class="step-desc">You upload a rice plant photo. PHP receives and validates the file.</div>
        </div>
        <div class="step-card">
          <div class="step-num">2</div>
          <div class="step-title">Feature Extraction</div>
          <div class="step-desc">RGB histogram, HOG edges, and LBP texture features are extracted from the image.</div>
        </div>
        <div class="step-card">
          <div class="step-num">3</div>
          <div class="step-title">PCA Reduction</div>
          <div class="step-desc">150 PCA components reduce feature dimensionality while keeping 95%+ variance.</div>
        </div>
        <div class="step-card">
          <div class="step-num">4</div>
          <div class="step-title">SVM Prediction</div>
          <div class="step-desc">The trained SVM model (RBF kernel, C=10) classifies the plant as healthy or not.</div>
        </div>
        <div class="step-card">
          <div class="step-num">5</div>
          <div class="step-title">Result Display</div>
          <div class="step-desc">PHP renders the prediction and confidence score back to you on this page.</div>
        </div>
      </div>
    </div>
  </div>

</main>

<footer>
  <strong>GROUP 11</strong> &mdash; BS Computer Science &mdash; Polytechnic University of the Philippines<br>
  Rice Plant Health Classification using SVM &bull; Midterm Project &bull; 94.17% Accuracy
</footer>

<script>
  const input    = document.getElementById('imageInput');
  const preview  = document.getElementById('previewImg');
  const wrap     = document.getElementById('previewWrap');
  const dropZone = document.getElementById('dropZone');
  const form     = document.getElementById('uploadForm');
  const btn      = document.getElementById('submitBtn');
  const btnText  = document.getElementById('btnText');
  const spinner  = document.getElementById('spinner');

  input?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = e => {
        if (preview && wrap) {
          preview.src = e.target.result;
          wrap.style.display = 'flex';
        }
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  dropZone?.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });

  dropZone?.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
  });

  dropZone?.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files[0] && input) {
      const dt = new DataTransfer();
      dt.items.add(e.dataTransfer.files[0]);
      input.files = dt.files;
      input.dispatchEvent(new Event('change'));
    }
  });

  form?.addEventListener('submit', () => {
    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'block';
    if (btn) btn.disabled = true;
  });
</script>

</body>
</html>
