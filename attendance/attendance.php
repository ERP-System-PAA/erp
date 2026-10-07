<?php
// ============================================================
// QR Attendance Scanner
// Location: /erp/attendance/attendance.php
// Decoder:  @zxing/browser (CDN — no local file needed)
// Access:   Any logged-in user (employee or admin)
// ============================================================

require_once __DIR__ . '/../include/config.php';

// Must be logged in — any role can scan their own QR
if (!is_logged_in()) {
    header("Location: ../auth/admin_login.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$role     = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance Scanner — ERP</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* =========================================================
           PAGE HEAD
           ========================================================= */
        .scan-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 22px;
        }

        /* =========================================================
           STATUS STRIP
           ========================================================= */
        .status-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 12px 18px;
            background: var(--maroon-card);
            border: 1px solid rgba(232, 140, 46, 0.15);
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--text-muted);
        }
        .status-strip .live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--off-white);
        }
        .status-strip .live .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #555;
            transition: background .2s ease;
        }
        .status-strip .live.on .dot {
            background: #E88C2E;
            animation: pulse 1.6s ease-out infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(232,140,46,0.55); }
            70%  { box-shadow: 0 0 0 8px rgba(232,140,46,0); }
            100% { box-shadow: 0 0 0 0 rgba(232,140,46,0); }
        }
        .status-strip .live i { font-size: 14px; color: var(--amber); }
        .status-strip .last-scan { color: var(--text-muted); }
        .status-strip .last-scan strong {
            color: var(--off-white);
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-weight: 700;
            margin-left: 4px;
        }

        /* =========================================================
           SCANNER STAGE
           ========================================================= */
        .scanner-stage {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 380px);
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .scanner-stage { grid-template-columns: 1fr; }
        }

        .video-frame {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(232, 140, 46, 0.25);
            box-shadow: 0 15px 40px rgba(0,0,0,0.55);
            aspect-ratio: 4 / 3;
        }
        #preview {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
        }

        /* Corner brackets */
        .video-frame::before,
        .video-frame::after,
        .video-frame .bracket-bl::before,
        .video-frame .bracket-bl::after {
            content: "";
            position: absolute;
            width: 32px;
            height: 32px;
            border-color: var(--amber);
            border-style: solid;
            pointer-events: none;
            z-index: 2;
            opacity: .85;
        }
        .video-frame::before {
            top: 14px; left: 14px;
            border-width: 3px 0 0 3px;
            border-top-left-radius: 6px;
        }
        .video-frame::after {
            top: 14px; right: 14px;
            border-width: 3px 3px 0 0;
            border-top-right-radius: 6px;
        }
        .video-frame .bracket-bl::before {
            bottom: 14px; left: 14px;
            border-width: 0 0 3px 3px;
            border-bottom-left-radius: 6px;
        }
        .video-frame .bracket-bl::after {
            bottom: 14px; right: 14px;
            border-width: 0 3px 3px 0;
            border-bottom-right-radius: 6px;
        }

        /* Overlay */
        .video-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(180deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.85) 100%);
            color: #ddd;
            text-align: center;
            z-index: 3;
            transition: opacity .3s ease;
            padding: 20px;
        }
        .video-overlay.hide { opacity: 0; pointer-events: none; }
        .video-overlay i {
            font-size: 42px;
            color: var(--amber);
            filter: drop-shadow(0 0 12px rgba(232,140,46,0.6));
        }
        .video-overlay .title {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #fff;
        }
        .video-overlay .sub {
            font-size: 12px;
            letter-spacing: .5px;
            color: #aaa;
            max-width: 260px;
            line-height: 1.5;
        }

        /* =========================================================
           SIDE PANEL
           ========================================================= */
        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .panel {
            background: var(--maroon-card);
            border: 1px solid rgba(232, 140, 46, 0.15);
            border-radius: 10px;
            overflow: hidden;
        }
        .panel-head {
            padding: 12px 16px;
            background: rgba(0,0,0,0.25);
            border-bottom: 1px solid rgba(232, 140, 46, 0.12);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-head i { color: var(--amber); font-size: 12px; }

        .panel-body { padding: 16px; }

        .cam-field {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cam-field label {
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--text-muted);
            flex-shrink: 0;
        }
        #cameraSelect {
            flex: 1;
            min-width: 0;
            padding: 10px 12px;
            border-radius: 6px;
            background: rgba(0,0,0,0.3);
            color: var(--off-white);
            border: 1px solid rgba(232, 140, 46, 0.2);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
            cursor: pointer;
        }
        #cameraSelect:focus {
            border-color: var(--amber);
            box-shadow: 0 0 0 3px rgba(232,140,46,0.15);
        }
        #cameraSelect:disabled { opacity: .5; cursor: not-allowed; }
        #cameraSelect option { background: #2b0e0e; color: #f0e6e6; }

        /* Buttons */
        .btn-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background .18s ease, transform .1s ease,
                        box-shadow .18s ease, border-color .18s ease, color .18s ease;
            font-family: inherit;
            line-height: 1;
            flex: 1 1 auto;
            min-width: 130px;
        }
        .btn i { font-size: 14px; line-height: 1; }
        .btn:active { transform: translateY(1px); }
        .btn:disabled { opacity: .45; cursor: not-allowed; }

        .btn-primary { background: var(--amber); color: #2b0e0e; }
        .btn-primary:hover:not(:disabled) {
            background: var(--amber-bright);
            box-shadow: 0 6px 18px rgba(232,140,46,0.28);
        }
        .btn-stop {
            background: transparent;
            color: #ff8f9a;
            border-color: rgba(220,53,69,0.5);
        }
        .btn-stop:hover:not(:disabled) {
            background: rgba(220,53,69,0.12);
            border-color: #dc3545;
        }

        /* Result box */
        .result-box {
            padding: 18px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
            text-align: center;
            letter-spacing: .3px;
            background: rgba(0,0,0,0.25);
            color: #ccc;
            border: 1px solid rgba(232, 140, 46, 0.15);
            transition: all .22s ease;
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .result-box i { font-size: 18px; flex-shrink: 0; }
        .result-box.ok   { background: rgba(40,167,69,0.14);  color: #7ee29c; border-color: #28a745; }
        .result-box.ok i { color: #7ee29c; }
        .result-box.warn { background: rgba(255,193,7,0.14);  color: #ffdb6e; border-color: #ffc107; }
        .result-box.warn i { color: #ffdb6e; }
        .result-box.err  { background: rgba(220,53,69,0.14);  color: #ff8f9a; border-color: #dc3545; }
        .result-box.err i { color: #ff8f9a; }

        /* Hint list */
        .hint-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .hint-list li { display: flex; gap: 10px; align-items: flex-start; }
        .hint-list li i {
            color: var(--amber);
            font-size: 13px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* Permission warning */
        .perm-warning {
            display: none;
            padding: 14px 16px;
            border-radius: 8px;
            background: rgba(220,53,69,0.12);
            color: #ffb3ba;
            border: 1px solid rgba(220,53,69,0.5);
            font-size: 13px;
            line-height: 1.6;
        }
        .perm-warning.show { display: block; }
        .perm-warning strong { color: #ff8f9a; }
        .perm-warning code {
            background: rgba(0,0,0,0.4);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--amber);
            font-size: 12px;
        }

        /* Last scan card */
        .last-scan-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: rgba(0,0,0,0.25);
            border-radius: 8px;
            border: 1px solid rgba(232, 140, 46, 0.12);
            transition: border-color .2s ease;
        }
        .last-scan-card.ok   { border-color: rgba(40,167,69,0.45); }
        .last-scan-card.warn { border-color: rgba(255,193,7,0.45); }
        .last-scan-card.err  { border-color: rgba(220,53,69,0.45); }
        .last-scan-card .icon-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(232,140,46,0.15);
            color: var(--amber);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .last-scan-card.ok   .icon-badge { background: rgba(40,167,69,0.18);  color: #7ee29c; }
        .last-scan-card.warn .icon-badge { background: rgba(255,193,7,0.18);  color: #ffdb6e; }
        .last-scan-card.err  .icon-badge { background: rgba(220,53,69,0.18);  color: #ff8f9a; }

        .last-scan-card .info { flex: 1; min-width: 0; }
        .last-scan-card .info .primary {
            font-size: 14px;
            font-weight: 700;
            color: var(--off-white);
            line-height: 1.3;
            word-break: break-word;
        }
        .last-scan-card .info .secondary {
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 3px;
            font-family: ui-monospace, Menlo, Consolas, monospace;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../component/admin_nav.php'; ?>

<div class="admin-content">

    <!-- ================= PAGE HEAD ================= -->
    <div class="scan-head">
        <div>
            <h2>QR <span>Attendance Scanner</span></h2>
            <p class="lead" style="margin-top:6px;">
                Signed in as <strong style="color:var(--amber)"><?= $username ?></strong>
                · Scan your badge to record Time In / Time Out.
            </p>
        </div>
    </div>

    <!-- ================= STATUS STRIP ================= -->
    <div class="status-strip">
        <span class="live" id="liveIndicator">
            <span class="dot"></span>
            <i class="bi bi-camera-video"></i>
            <span id="liveLabel">Camera Offline</span>
        </span>
        <span class="last-scan">
            <i class="bi bi-clock-history"></i>
            Last Scan:<strong id="lastScanTime">—</strong>
        </span>
    </div>

    <!-- ================= SCANNER STAGE ================= -->
    <div class="scanner-stage">

        <!-- LEFT: Video + Result -->
        <div>
            <div class="video-frame">
                <video id="preview" playsinline muted></video>

                <div class="video-overlay" id="videoOverlay">
                    <i class="bi bi-camera-video-off-fill"></i>
                    <div class="title">Camera Offline</div>
                    <div class="sub">Press <strong style="color:var(--amber)">Start Camera</strong> to request permission.</div>
                </div>

                <span class="bracket-bl"></span>
            </div>

            <div style="margin-top: 16px;">
                <div id="result" class="result-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Ready. Start the camera to begin scanning.</span>
                </div>
            </div>
        </div>

        <!-- RIGHT: Side panel -->
        <div class="side-panel">

            <!-- Camera control panel -->
            <div class="panel">
                <div class="panel-head">
                    <i class="bi bi-camera-video-fill"></i>Camera Control
                </div>
                <div class="panel-body" style="display:flex; flex-direction:column; gap:14px;">

                    <div class="cam-field">
                        <label for="cameraSelect">
                            <i class="bi bi-webcam-fill"></i> Device
                        </label>
                        <select id="cameraSelect" disabled>
                            <option>Click "Start Camera" first…</option>
                        </select>
                    </div>

                    <div class="btn-row">
                        <button id="startBtn" class="btn btn-primary">
                            <i class="bi bi-play-fill"></i>
                            <span>Start Camera</span>
                        </button>
                        <button id="stopBtn" class="btn btn-stop" disabled>
                            <i class="bi bi-stop-fill"></i>
                            <span>Stop</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Last scan panel -->
            <div class="panel">
                <div class="panel-head">
                    <i class="bi bi-activity"></i>Latest Activity
                </div>
                <div class="panel-body">
                    <div class="last-scan-card" id="lastScanCard">
                        <div class="icon-badge"><i class="bi bi-hourglass-split"></i></div>
                        <div class="info">
                            <div class="primary" id="lastScanPrimary">Waiting for first scan…</div>
                            <div class="secondary" id="lastScanSecondary">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permission warning -->
            <div class="perm-warning" id="permWarning"></div>

            <!-- Hints -->
            <div class="panel">
                <div class="panel-head">
                    <i class="bi bi-lightbulb-fill"></i>Quick Guide
                </div>
                <div class="panel-body">
                    <ul class="hint-list">
                        <li><i class="bi bi-1-circle-fill"></i><span>Click <strong>Start Camera</strong> and allow browser access.</span></li>
                        <li><i class="bi bi-2-circle-fill"></i><span>Hold your QR badge steady in front of the lens.</span></li>
                        <li><i class="bi bi-3-circle-fill"></i><span>First scan records <strong>Time In</strong>; second scan records <strong>Time Out</strong>.</span></li>
                        <li><i class="bi bi-4-circle-fill"></i><span>Repeated scans within 4 seconds are ignored.</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ZXing Browser — loaded from jsDelivr CDN
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.20.0/umd/index.min.js"></script>
<script>
const resultBox      = document.getElementById('result');
const videoEl        = document.getElementById('preview');
const camSelect      = document.getElementById('cameraSelect');
const permWarn       = document.getElementById('permWarning');
const startBtn       = document.getElementById('startBtn');
const stopBtn        = document.getElementById('stopBtn');
const videoOverlay   = document.getElementById('videoOverlay');
const liveIndicator  = document.getElementById('liveIndicator');
const liveLabel      = document.getElementById('liveLabel');
const lastScanTime   = document.getElementById('lastScanTime');
const lastScanCard   = document.getElementById('lastScanCard');
const lastScanPri    = document.getElementById('lastScanPrimary');
const lastScanSec    = document.getElementById('lastScanSecondary');

let codeReader   = null;
let controls     = null;
let cameras      = [];
let lastScan     = 0;
let isScanning   = false;

// -------------------------------------------------------------
// Sanity check: did the ZXing library load?
// -------------------------------------------------------------
if (typeof ZXing === 'undefined') {
    console.error('ZXing library failed to load from CDN.');
    document.addEventListener('DOMContentLoaded', () => {
        setResult('err', 'bi-x-octagon-fill',
            'Scanner library missing. Check your internet connection.');
        startBtn.disabled = true;
    });
}

// -------------------------------------------------------------
// UI helpers
// -------------------------------------------------------------
function setResult(kind, icon, message) {
    resultBox.className = 'result-box ' + (kind || '');
    resultBox.innerHTML = '<i class="bi ' + icon + '"></i><span>' + message + '</span>';
}
function setResultDefault(msg) {
    setResult('', 'bi-info-circle-fill', msg || 'Ready.');
}
function setLive(on, label) {
    liveIndicator.classList.toggle('on', !!on);
    liveLabel.textContent = label;
}
function setLastScan(kind, primary, secondary) {
    lastScanCard.className = 'last-scan-card ' + (kind || '');
    lastScanPri.textContent = primary;
    lastScanSec.textContent = secondary || '—';
    const iconMap = {
        ok:   'bi-check-circle-fill',
        warn: 'bi-exclamation-triangle-fill',
        err:  'bi-x-circle-fill',
        '':   'bi-hourglass-split'
    };
    lastScanCard.querySelector('.icon-badge').innerHTML =
        '<i class="bi ' + (iconMap[kind] || iconMap['']) + '"></i>';
}
function stampNow() {
    return new Date().toLocaleTimeString(undefined,
        { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// -------------------------------------------------------------
// Error display
// -------------------------------------------------------------
function showError(prefix, err) {
    const name = err && err.name    ? err.name    : 'Error';
    const msg  = err && err.message ? err.message : String(err);

    setResult('err', 'bi-x-octagon-fill', prefix + ' [' + name + ']: ' + msg);
    setLive(false, 'Camera Error');
    setLastScan('err', prefix, name);
    console.error(prefix, err);

    if (name === 'NotAllowedError') {
        permWarn.innerHTML =
            '<strong><i class="bi bi-shield-exclamation"></i> Permission denied.</strong><br>' +
            'Click the lock/camera icon in the address bar, set Camera to Allow, then reload.';
    } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
        permWarn.innerHTML = '<strong><i class="bi bi-camera-video-off-fill"></i> No camera found.</strong> Connect a webcam and try again.';
    } else if (name === 'NotReadableError' || name === 'TrackStartError') {
        permWarn.innerHTML = '<strong><i class="bi bi-exclamation-octagon-fill"></i> Camera is busy.</strong> Close other apps using the webcam.';
    } else if (name === 'SecurityError') {
        permWarn.innerHTML = '<strong><i class="bi bi-shield-lock-fill"></i> Insecure context.</strong> Camera requires HTTPS or http://localhost. You are on <code>' + location.origin + '</code>.';
    } else {
        permWarn.innerHTML = '<strong><i class="bi bi-exclamation-triangle-fill"></i> Camera error.</strong> See console for details.';
    }
    permWarn.classList.add('show');
}
function clearError() {
    permWarn.classList.remove('show');
    permWarn.innerHTML = '';
}

// -------------------------------------------------------------
// Start scanning with a specific device ID
// -------------------------------------------------------------
function startWith(deviceId) {
    if (!deviceId) return;

    // Stop any previous session
    stopCamera();

    try {
        codeReader = new ZXing.BrowserMultiFormatReader();

        controls = codeReader.decodeFromVideoDevice(
            deviceId,
            videoEl,
            function (result, err) {
                if (result) {
                    const content = result.getText();
                    console.log('[SCAN] Decoded:', JSON.stringify(content));

                    const now = Date.now();
                    if (now - lastScan < 4000) return;   // debounce
                    lastScan = now;

                    handleScan(content.trim());
                }
                // err is normal when no QR is in frame — ignore it
            }
        );

        isScanning = true;
        videoOverlay.classList.add('hide');
        startBtn.disabled = true;
        stopBtn.disabled  = false;
        setLive(true, 'Camera Live');
        setResultDefault('Camera active — point your QR badge at the lens…');
        setLastScan('', 'Listening for scans…', '—');

    } catch (err) {
        showError('Camera start failed', err);
        startBtn.disabled = false;
        stopBtn.disabled  = true;
    }
}

// -------------------------------------------------------------
// Stop camera
// -------------------------------------------------------------
function stopCamera() {
    if (codeReader) {
        try { codeReader.reset(); } catch (e) {}
        codeReader = null;
    }
    if (controls) {
        try { controls.stop(); } catch (e) {}
        controls = null;
    }
    isScanning = false;
    startBtn.disabled = false;
    stopBtn.disabled  = true;
    videoOverlay.classList.remove('hide');
    setLive(false, 'Camera Offline');
}

// -------------------------------------------------------------
// Send scan to backend
// -------------------------------------------------------------
function handleScan(content) {
    console.log('[SCAN] POST body: employee_code=' + content);
    setResult('warn', 'bi-hourglass-split', 'Processing…');
    setLive(true, 'Scanning…');

    fetch('process_scan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'employee_code=' + encodeURIComponent(content)
    })
    .then(async r => {
        const text = await r.text();
        console.log('[SCAN] HTTP', r.status, 'body:', text);
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Server returned non-JSON: ' + text.slice(0, 120));
        }
    })
    .then(data => {
        const status = data.status === 'ok'   ? 'ok'
                     : data.status === 'warn' ? 'warn' : 'err';

        let icon = 'bi-info-circle-fill';
        if (status === 'ok')   icon = 'bi-check-circle-fill';
        if (status === 'warn') icon = 'bi-exclamation-triangle-fill';
        if (status === 'err')  icon = 'bi-x-circle-fill';

        setResult(status, icon, data.message || 'Done.');
        setLive(true, 'Camera Live');

        const time = stampNow();
        lastScanTime.textContent = time;

        const plain = (data.message || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const short = plain.length > 60 ? plain.slice(0, 60) + '…' : plain;
        setLastScan(status, short || 'Scan received',
                    'Code: ' + content + '  ·  ' + time);
    })
    .catch(err => {
        console.error('[SCAN] Fetch error:', err);
        setResult('err', 'bi-wifi-off', 'Network error: ' + err.message);
        setLive(true, 'Camera Live');
        setLastScan('err', 'Network error', stampNow());
    });
}

// -------------------------------------------------------------
// Start button
// -------------------------------------------------------------
startBtn.addEventListener('click', async () => {
    if (typeof ZXing === 'undefined') {
        setResult('err', 'bi-x-octagon-fill',
            'Scanner library missing. Check internet / CDN access.');
        return;
    }

    clearError();
    startBtn.disabled = true;
    setResult('warn', 'bi-hourglass-split', 'Requesting camera permission…');

    try {
        // Trigger permission prompt + enumerate labeled devices
        const tmp = await navigator.mediaDevices.getUserMedia({ video: true });
        tmp.getTracks().forEach(t => t.stop());

        const devices = await navigator.mediaDevices.enumerateDevices();
        cameras = devices.filter(d => d.kind === 'videoinput');

        if (!cameras.length) throw new Error('No cameras available');

        camSelect.innerHTML = '';
        cameras.forEach((cam, i) => {
            const opt = document.createElement('option');
            opt.value = cam.deviceId;
            opt.textContent = cam.label || ('Camera ' + (i + 1));
            camSelect.appendChild(opt);
        });
        camSelect.disabled = false;

        // Prefer rear/environment camera; fall back to last one
        let bestIdx = cameras.findIndex(c => /back|rear|environment/i.test(c.label || ''));
        if (bestIdx < 0) bestIdx = cameras.length - 1;

        camSelect.selectedIndex = bestIdx;
        startWith(cameras[bestIdx].deviceId);

    } catch (err) {
        showError('Permission request failed', err);
        startBtn.disabled = false;
    }
});

// -------------------------------------------------------------
// Stop button
// -------------------------------------------------------------
stopBtn.addEventListener('click', () => {
    stopCamera();
    setResultDefault('Camera stopped. Press Start to resume.');
    setLastScan('', 'Camera stopped', '—');
});

// -------------------------------------------------------------
// Switch camera from dropdown
// -------------------------------------------------------------
camSelect.addEventListener('change', () => {
    if (camSelect.value) {
        startWith(camSelect.value);
    }
});

window.addEventListener('beforeunload', stopCamera);

// -------------------------------------------------------------
// Secure-context check
// -------------------------------------------------------------
if (!window.isSecureContext) {
    setResult('err', 'bi-shield-lock-fill',
        'Camera requires HTTPS or http://localhost. Origin: ' + location.origin);
    permWarn.innerHTML =
        '<strong><i class="bi bi-shield-lock-fill"></i> Insecure context.</strong> ' +
        'Browsers block camera access on plain HTTP except for <code>localhost</code>. ' +
        'You are on <code>' + location.origin + '</code>.';
    permWarn.classList.add('show');
    startBtn.disabled = true;
}
</script>

</body>
</html>