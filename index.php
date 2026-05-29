<?php
$API_URL = "https://rorrenzz-rice-classifier.hf.space/predict";

$result  = null;
$error   = null;
$preview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed)) {
            $imgData = file_get_contents($file['tmp_name']);
            $preview = 'data:' . $mime . ';base64,' . base64_encode($imgData);

            $curl  = curl_init();
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
                if (isset($data['prediction'])) $result = $data;
                else $error = "Unexpected response from model.";
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

$isHealthy  = $result && $result['prediction'] === 'Healthy';
$isUnhealthy= $result && $result['prediction'] === 'Unhealthy';
$confidence = $result ? $result['confidence'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>CropCheck AI — Rice Plant Health Classifier</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --green-900:#0a1f0e;
  --green-800:#122a16;
  --green-700:#1a3d1f;
  --green-600:#1f5c28;
  --green-500:#2a7a34;
  --green-400:#3a9e46;
  --green-300:#5cbe68;
  --green-100:#c8f0cc;
  --accent:#4ade6e;
  --accent-dark:#2fb84a;
  --gold:#f0c040;
  --red:#e84040;
  --red-light:#fff0f0;
  --white:#ffffff;
  --glass:rgba(10,31,14,0.72);
  --glass-light:rgba(255,255,255,0.07);
  --glass-border:rgba(255,255,255,0.12);
}

body{
  font-family:'Outfit',sans-serif;
  background:var(--green-900);
  color:var(--white);
  min-height:100vh;
  overflow-x:hidden;
}

/* ── HERO BG ── */
.hero-bg{
  position:fixed;
  inset:0;
  background:
    linear-gradient(180deg,rgba(10,31,14,0.55) 0%,rgba(10,31,14,0.82) 60%,rgba(10,31,14,0.97) 100%),
    url('https://images.unsplash.com/photo-1536304929831-ee1ca9d44906?w=1600&q=80') center/cover no-repeat;
  z-index:0;
}

.hero-bg::after{
  content:'';
  position:absolute;
  inset:0;
  background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(42,122,52,0.18) 0%,transparent 70%);
}

/* ── LAYOUT ── */
.page{position:relative;z-index:1;min-height:100vh}

/* ── NAVBAR ── */
nav{
  display:flex;
  align-items:center;
  padding:1.25rem 3rem;
  border-bottom:1px solid var(--glass-border);
  backdrop-filter:blur(12px);
  background:rgba(10,31,14,0.5);
  position:sticky;
  top:0;
  z-index:100;
}

.nav-brand{
  display:flex;
  align-items:center;
  gap:10px;
  font-size:1.25rem;
  font-weight:700;
  letter-spacing:-0.3px;
}

.nav-brand .leaf{
  width:34px;height:34px;
  background:var(--accent);
  border-radius:50% 50% 50% 8px;
  display:flex;align-items:center;justify-content:center;
  font-size:17px;
}

.nav-brand span{color:var(--accent)}

.nav-right{
  margin-left:auto;
  display:flex;
  align-items:center;
  gap:0.5rem;
}

.pup-badge{
  background:rgba(123,17,19,0.85);
  border:1px solid rgba(200,80,80,0.35);
  color:#ffcccc;
  font-size:0.72rem;
  font-weight:600;
  padding:5px 12px;
  border-radius:20px;
  letter-spacing:0.5px;
}

/* ── HERO HEADER ── */
.hero-header{
  text-align:center;
  padding:4rem 2rem 2.5rem;
}

.hero-eyebrow{
  display:inline-flex;
  align-items:center;
  gap:7px;
  background:rgba(74,222,110,0.12);
  border:1px solid rgba(74,222,110,0.25);
  color:var(--accent);
  font-size:0.72rem;
  font-weight:600;
  letter-spacing:2px;
  text-transform:uppercase;
  padding:6px 16px;
  border-radius:20px;
  margin-bottom:1.25rem;
}

.hero-header h1{
  font-size:clamp(2rem,5vw,3.2rem);
  font-weight:800;
  letter-spacing:-1px;
  line-height:1.1;
  margin-bottom:0.75rem;
}

.hero-header h1 span{color:var(--accent)}

.hero-header p{
  color:rgba(255,255,255,0.55);
  font-size:1rem;
  max-width:520px;
  margin:0 auto 2.5rem;
  line-height:1.65;
  font-weight:300;
}

/* ── STATS ROW ── */
.stats-row{
  display:flex;
  justify-content:center;
  gap:1rem;
  flex-wrap:wrap;
  margin-bottom:3rem;
}

.stat-pill{
  display:flex;
  align-items:center;
  gap:8px;
  background:var(--glass-light);
  border:1px solid var(--glass-border);
  backdrop-filter:blur(8px);
  padding:8px 18px;
  border-radius:30px;
  font-size:0.82rem;
}

.stat-pill .val{
  font-weight:700;
  color:var(--accent);
  font-size:0.95rem;
}

/* ── MAIN CARD AREA ── */
.main-area{
  max-width:1000px;
  margin:0 auto;
  padding:0 1.5rem 4rem;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:1.25rem;
  align-items:start;
}

@media(max-width:680px){
  .main-area{grid-template-columns:1fr}
  nav{padding:1rem 1.25rem}
  .hero-header{padding:2.5rem 1.25rem 1.5rem}
}

/* ── GLASS CARD ── */
.card{
  background:var(--glass);
  border:1px solid var(--glass-border);
  border-radius:20px;
  padding:1.75rem;
  backdrop-filter:blur(16px);
}

.card-label{
  font-size:0.7rem;
  font-weight:700;
  letter-spacing:2px;
  text-transform:uppercase;
  color:var(--accent);
  margin-bottom:1.1rem;
  display:flex;
  align-items:center;
  gap:7px;
}

.card-label::before{
  content:'';
  width:16px;height:2px;
  background:var(--accent);
  border-radius:2px;
  display:block;
}

/* ── UPLOAD ZONE ── */
.upload-zone{
  border:1.5px dashed rgba(74,222,110,0.3);
  border-radius:14px;
  padding:2rem 1rem;
  text-align:center;
  cursor:pointer;
  transition:all 0.2s;
  position:relative;
  background:rgba(74,222,110,0.03);
}

.upload-zone:hover,.upload-zone.dragover{
  border-color:var(--accent);
  background:rgba(74,222,110,0.07);
}

.upload-zone input[type="file"]{
  position:absolute;inset:0;
  opacity:0;cursor:pointer;
  width:100%;height:100%;
}

.upload-icon-wrap{
  width:56px;height:56px;
  border-radius:50%;
  background:rgba(74,222,110,0.1);
  border:1px solid rgba(74,222,110,0.2);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 0.85rem;
  font-size:1.6rem;
}

.upload-text{
  font-size:0.88rem;
  color:rgba(255,255,255,0.5);
  line-height:1.6;
}

.upload-text strong{color:var(--accent);font-weight:600}

/* ── PREVIEW ── */
.preview-box{
  margin-top:1rem;
  border-radius:12px;
  overflow:hidden;
  border:1px solid var(--glass-border);
  background:rgba(0,0,0,0.3);
  display:flex;
  align-items:center;
  justify-content:center;
  min-height:140px;
}

.preview-box img{
  width:100%;
  max-height:200px;
  object-fit:contain;
  display:block;
}

/* ── ANALYZE BTN ── */
.btn-analyze{
  margin-top:1rem;
  width:100%;
  padding:0.9rem;
  background:var(--accent);
  color:var(--green-900);
  font-family:'Outfit',sans-serif;
  font-weight:700;
  font-size:0.95rem;
  letter-spacing:0.5px;
  border:none;
  border-radius:12px;
  cursor:pointer;
  transition:all 0.2s;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
}

.btn-analyze:hover{background:var(--accent-dark);color:#fff}
.btn-analyze:active{transform:scale(0.98)}
.btn-analyze:disabled{opacity:0.5;cursor:not-allowed}

/* ── RESULT ── */
.result-empty{
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  min-height:320px;
  text-align:center;
  color:rgba(255,255,255,0.25);
  gap:0.75rem;
}

.result-empty .big{font-size:3.5rem;opacity:0.4}

.result-banner{
  border-radius:14px;
  padding:1.25rem 1.5rem;
  margin-bottom:1.25rem;
  display:flex;
  align-items:center;
  gap:14px;
}

.result-banner.healthy{
  background:rgba(74,222,110,0.12);
  border:1px solid rgba(74,222,110,0.3);
}

.result-banner.unhealthy{
  background:rgba(232,64,64,0.12);
  border:1px solid rgba(232,64,64,0.35);
}

.result-emoji{font-size:2.2rem;flex-shrink:0}

.result-label-wrap{}

.result-tag{
  font-size:0.65rem;
  font-weight:700;
  letter-spacing:2px;
  text-transform:uppercase;
  color:rgba(255,255,255,0.45);
  margin-bottom:2px;
}

.result-label{
  font-size:1.6rem;
  font-weight:800;
  letter-spacing:-0.5px;
}

.result-label.healthy{color:var(--accent)}
.result-label.unhealthy{color:#f07070}

/* ── CONFIDENCE BAR ── */
.conf-section{margin-bottom:1.25rem}

.conf-head{
  display:flex;justify-content:space-between;
  font-size:0.78rem;
  color:rgba(255,255,255,0.45);
  margin-bottom:7px;
}

.conf-head strong{color:var(--white);font-size:0.9rem}

.conf-track{
  height:8px;
  background:rgba(255,255,255,0.08);
  border-radius:99px;
  overflow:hidden;
}

.conf-fill{
  height:100%;
  border-radius:99px;
  transition:width 0.8s cubic-bezier(.22,.68,0,1.2);
}

.conf-fill.healthy{background:linear-gradient(90deg,#2fb84a,#4ade6e)}
.conf-fill.unhealthy{background:linear-gradient(90deg,#c03030,#f07070)}

/* ── DETAILS TABLE ── */
.details{width:100%;border-collapse:collapse;font-size:0.82rem}

.details td{
  padding:8px 0;
  border-bottom:1px solid rgba(255,255,255,0.07);
}

.details td:first-child{color:rgba(255,255,255,0.4);width:50%}
.details td:last-child{text-align:right;font-weight:600;color:rgba(255,255,255,0.9)}
.details tr:last-child td{border-bottom:none}

/* ── DIAGNOSIS BOX ── */
.diagnosis-box{
  margin-top:1.25rem;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  border-radius:12px;
  padding:1rem 1.25rem;
}

.diag-title{
  font-size:0.7rem;
  font-weight:700;
  letter-spacing:1.5px;
  text-transform:uppercase;
  color:rgba(255,255,255,0.35);
  margin-bottom:0.5rem;
}

.diag-name{
  font-size:1rem;
  font-weight:700;
  color:var(--white);
  margin-bottom:0.4rem;
}

.diag-rec{
  font-size:0.8rem;
  color:rgba(255,255,255,0.45);
  line-height:1.7;
}

.diag-rec li{list-style:none;padding-left:1rem;position:relative}
.diag-rec li::before{content:'→';position:absolute;left:0;color:var(--accent);font-size:0.75rem}

/* ── ERROR ── */
.error-box{
  background:rgba(232,64,64,0.1);
  border:1px solid rgba(232,64,64,0.3);
  border-radius:10px;
  padding:0.9rem 1.1rem;
  font-size:0.85rem;
  color:#f09090;
  margin-top:1rem;
  display:flex;gap:8px;align-items:flex-start;
}

/* ── HOW IT WORKS ── */
.how-section{
  grid-column:1/-1;
}

.how-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
  gap:1rem;
  margin-top:0;
}

.how-card{
  background:var(--glass-light);
  border:1px solid var(--glass-border);
  border-radius:14px;
  padding:1.25rem 1rem;
  text-align:center;
}

.how-num{
  width:32px;height:32px;
  border-radius:50%;
  background:rgba(74,222,110,0.15);
  border:1px solid rgba(74,222,110,0.25);
  color:var(--accent);
  font-size:0.8rem;
  font-weight:700;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 0.75rem;
}

.how-title{font-weight:600;font-size:0.88rem;margin-bottom:0.3rem}
.how-desc{font-size:0.75rem;color:rgba(255,255,255,0.4);line-height:1.55}

/* ── FOOTER ── */
footer{
  text-align:center;
  padding:2rem 1rem;
  border-top:1px solid rgba(255,255,255,0.06);
  font-size:0.75rem;
  color:rgba(255,255,255,0.25);
  line-height:1.8;
}

footer strong{color:rgba(255,255,255,0.5)}

/* ── SPINNER ── */
.spinner{
  display:none;
  width:18px;height:18px;
  border:2.5px solid rgba(10,31,14,0.3);
  border-top-color:var(--green-900);
  border-radius:50%;
  animation:spin 0.6s linear infinite;
}

@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<div class="hero-bg"></div>

<div class="page">

<!-- NAV -->
<nav>
  <div class="nav-brand">
    <div class="leaf">🌿</div>
    CropCheck<span>AI</span>
  </div>
  <div class="nav-right">
    <span class="pup-badge">PUP · GROUP 11</span>
  </div>
</nav>

<!-- HERO HEADER -->
<div class="hero-header">
  <div class="hero-eyebrow">
    🌾 Midterm Project
  </div>
  <h1>Automated Rice Plant<br><span>Health Classification</span></h1>
  <p>Machine Learning–powered rice health assessment using SVM with RGB, HOG, and LBP feature extraction.</p>

  <div class="stats-row">
    <div class="stat-pill"><span class="val">94.17%</span> Model Accuracy</div>
    <div class="stat-pill"><span class="val">600</span> Training Images</div>
    <div class="stat-pill"><span class="val">SVM</span> RBF Kernel</div>
    <div class="stat-pill"><span class="val">2</span> Classes</div>
  </div>
</div>

<!-- MAIN -->
<div class="main-area">

  <!-- LEFT: Upload -->
  <div class="card">
    <div class="card-label">Upload Image for Analysis</div>

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
      <div class="upload-zone" id="dropZone">
        <input type="file" name="image" id="imageInput" accept="image/*" required>
        <div class="upload-icon-wrap">📷</div>
        <div class="upload-text">
          <strong>Drag & Drop or Click to Upload</strong><br>
          Rice Leaf or Plant Image<br>
          <span style="font-size:0.75rem;opacity:0.6">JPG, PNG, WebP supported</span>
        </div>
      </div>

      <?php if ($preview): ?>
      <div class="preview-box">
        <img src="<?= htmlspecialchars($preview) ?>" alt="Uploaded image preview">
      </div>
      <?php else: ?>
      <div class="preview-box" id="previewWrap" style="display:none">
        <img id="previewImg" src="" alt="Preview">
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <button type="submit" class="btn-analyze" id="submitBtn">
        <span id="btnText">🔬 Analyze Plant</span>
        <div class="spinner" id="spinner"></div>
      </button>
    </form>
  </div>

  <!-- RIGHT: Result -->
  <div class="card">
    <div class="card-label">Classification Result</div>

    <?php if ($result): ?>
      <div class="result-banner <?= $isHealthy ? 'healthy' : 'unhealthy' ?>">
        <div class="result-emoji"><?= $isHealthy ? '✅' : '❌' ?></div>
        <div class="result-label-wrap">
          <div class="result-tag">Classification</div>
          <div class="result-label <?= $isHealthy ? 'healthy' : 'unhealthy' ?>">
            <?= htmlspecialchars($result['prediction']) ?>
          </div>
        </div>
      </div>

      <div class="conf-section">
        <div class="conf-head">
          <span>Confidence Score</span>
          <strong><?= $confidence ?>%</strong>
        </div>
        <div class="conf-track">
          <div class="conf-fill <?= $isHealthy ? 'healthy' : 'unhealthy' ?>"
               style="width:<?= $confidence ?>%"></div>
        </div>
      </div>

      <table class="details">
        <tr><td>Result</td><td><?= htmlspecialchars($result['prediction']) ?></td></tr>
        <tr><td>Confidence</td><td><?= $confidence ?>%</td></tr>
        <tr><td>Algorithm</td><td>SVM (RBF Kernel)</td></tr>
        <tr><td>Features</td><td>RGB + HOG + LBP</td></tr>
        <tr><td>PCA Components</td><td>150</td></tr>
        <tr><td>Model Accuracy</td><td>94.17%</td></tr>
      </table>

      <div class="diagnosis-box">
        <div class="diag-title">Diagnosis</div>
        <?php if ($isHealthy): ?>
          <div class="diag-name">Plant appears healthy</div>
          <ul class="diag-rec">
            <li>Continue regular irrigation schedule</li>
            <li>Monitor for early signs of disease</li>
            <li>Maintain proper fertilization</li>
          </ul>
        <?php else: ?>
          <div class="diag-name">Disease indicators detected</div>
          <ul class="diag-rec">
            <li>Apply relevant bactericide or fungicide</li>
            <li>Ensure proper field drainage</li>
            <li>Consult an agricultural specialist</li>
            <li>Isolate affected plants if possible</li>
          </ul>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <div class="result-empty">
        <div class="big">🌾</div>
        <p style="font-weight:600;color:rgba(255,255,255,0.35);font-size:0.95rem">No image analyzed yet</p>
        <p style="font-size:0.8rem">Upload a rice plant photo to see<br>the AI classification result here.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- HOW IT WORKS -->
  <div class="how-section">
    <div class="card">
      <div class="card-label">How It Works</div>
      <div class="how-grid">
        <div class="how-card">
          <div class="how-num">1</div>
          <div class="how-title">Image Upload</div>
          <div class="how-desc">PHP receives and validates your rice plant photo securely.</div>
        </div>
        <div class="how-card">
          <div class="how-num">2</div>
          <div class="how-title">Feature Extraction</div>
          <div class="how-desc">RGB histogram, HOG edge features, and LBP texture patterns are extracted.</div>
        </div>
        <div class="how-card">
          <div class="how-num">3</div>
          <div class="how-title">PCA Reduction</div>
          <div class="how-desc">150 principal components reduce noise while keeping 95%+ variance.</div>
        </div>
        <div class="how-card">
          <div class="how-num">4</div>
          <div class="how-title">SVM Prediction</div>
          <div class="how-desc">Trained SVM model (C=10, RBF kernel) classifies the plant health status.</div>
        </div>
        <div class="how-card">
          <div class="how-num">5</div>
          <div class="how-title">Result Display</div>
          <div class="how-desc">Prediction, confidence score, and care recommendations are shown instantly.</div>
        </div>
      </div>
    </div>
  </div>

</div><!-- end main-area -->

<footer>
  <strong>GROUP 11</strong> &mdash; BS Computer Science &mdash; Polytechnic University of the Philippines<br>
  Rice Plant Health Classification &bull; SVM Machine Learning &bull; Midterm Project &bull; 94.17% Accuracy
</footer>

</div><!-- end page -->

<script>
const input   = document.getElementById('imageInput');
const preview = document.getElementById('previewImg');
const wrap    = document.getElementById('previewWrap');
const drop    = document.getElementById('dropZone');
const form    = document.getElementById('uploadForm');
const btn     = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');
const spinner = document.getElementById('spinner');

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

drop?.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('dragover'); });
drop?.addEventListener('dragleave', () => drop.classList.remove('dragover'));
drop?.addEventListener('drop', e => {
  e.preventDefault();
  drop.classList.remove('dragover');
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
