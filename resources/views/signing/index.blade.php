<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem auto; max-width: 920px; line-height: 1.45; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
        .muted { color: #555; }
        .ok { color: #0a7f29; font-weight: bold; }
        .bad { color: #a10000; font-weight: bold; }
        button { padding: .6rem .8rem; cursor: pointer; }
        code { background: #f3f3f3; padding: 2px 5px; border-radius: 4px; }
        pre { background: #fafafa; border: 1px solid #eee; padding: .8rem; overflow-x: auto; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<p class="muted">Beginner demo UI: click one button and it will execute the full hybrid flow step-by-step using the backend endpoints.</p>

<div class="card">
    <h3>How it works (simple)</h3>
    <ol>
        @foreach($workflow as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ol>
</div>

<div class="card">
    <h3>Run demo signing flow</h3>
    <p>This simulates a legitimate user passing OTP + liveness then receiving a digital signature.</p>
    <button id="runFlow">Run Full Flow</button>
    <p id="result"></p>
    <pre id="log"></pre>
</div>

<div class="card">
    <h3>API quick links</h3>
    <ul>
        <li><code>GET /</code> (this UI page, or JSON if <code>Accept: application/json</code>)</li>
        <li><code>POST /signing/initiate</code></li>
        <li><code>POST /signing/otp/verify</code></li>
        <li><code>POST /signing/liveness/verify</code></li>
        <li><code>POST /signing/apply</code></li>
        <li><code>POST /signing/verify</code></li>
        <li><code>GET /experiments</code></li>
        <li><code>POST /experiments/run</code></li>
    </ul>
</div>

<script>
const logEl = document.getElementById('log');
const resultEl = document.getElementById('result');
const addLog = (label, obj) => {
  logEl.textContent += `${label}\n${JSON.stringify(obj, null, 2)}\n\n`;
};

async function postJson(url, payload) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(payload)
  });
  return res.json();
}

document.getElementById('runFlow').addEventListener('click', async () => {
  logEl.textContent = '';
  resultEl.textContent = 'Running...';

  try {
    const init = await postJson('/signing/initiate', { document_id: 'DOC-DEMO-001', channel: 'email' });
    addLog('1) Initiate', init);

    const otpCheck = await postJson('/signing/otp/verify', {
      expected_otp: init.otp.otp,
      provided_otp: init.otp.otp
    });
    addLog('2) OTP Verify', otpCheck);

    const livenessCheck = await postJson('/signing/liveness/verify', {
      continuous_landmarks: true,
      correct_sequence: true,
      within_time_limit: true
    });
    addLog('3) Liveness Verify', livenessCheck);

    const apply = await postJson('/signing/apply', {
      document_content: 'This is a research demo document.',
      signer_id: 'user-001'
    });
    addLog('4) Apply Signature', apply);

    const verify = await postJson('/signing/verify', {
      document_content: 'This is a research demo document.',
      document_hash: apply.signature.document_hash
    });
    addLog('5) Verify Signature', verify);

    const success = otpCheck.otp_valid && livenessCheck.liveness_valid && verify.signature_valid;
    resultEl.innerHTML = success
      ? '<span class="ok">Success: signing flow passed.</span>'
      : '<span class="bad">Failed: one or more checks failed.</span>';
  } catch (error) {
    resultEl.innerHTML = `<span class="bad">Error: ${error.message}</span>`;
  }
});
</script>
</body>
</html>
