<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem auto; max-width: 1040px; line-height: 1.45; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
        .muted { color: #555; }
        .ok { color: #0a7f29; font-weight: bold; }
        .bad { color: #a10000; font-weight: bold; }
        button { padding: .6rem .8rem; cursor: pointer; margin-right: .5rem; }
        code { background: #f3f3f3; padding: 2px 5px; border-radius: 4px; }
        pre { background: #fafafa; border: 1px solid #eee; padding: .8rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: .7rem; }
        th, td { border: 1px solid #ddd; padding: .45rem; text-align: left; font-size: .92rem; }
        th { background: #f8f8f8; }
        .row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .chip { display: inline-block; border: 1px solid #ddd; border-radius: 999px; padding: .2rem .6rem; margin-right: .3rem; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<p class="muted">Beginner demo UI: run a single user signing flow or run experiment scenarios (OTP only / liveness only / combined) and export CSV.</p>

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
    <h3>Run Scenario Experiments</h3>
    <p class="muted">Select configurations, run all scenarios, then filter by scenario or export CSV for your paper table.</p>

    <div class="row">
        <label><input type="checkbox" class="configOption" value="configuration_a_otp_only" checked> Config A (OTP only)</label>
        <label><input type="checkbox" class="configOption" value="configuration_b_liveness_only" checked> Config B (Liveness only)</label>
        <label><input type="checkbox" class="configOption" value="configuration_c_otp_plus_liveness" checked> Config C (OTP + Liveness)</label>
    </div>

    <div style="margin-top: .75rem;">
        <label for="scenarioFilter">Scenario filter: </label>
        <select id="scenarioFilter">
            <option value="all">All scenarios</option>
            <option value="legitimate_user">legitimate_user</option>
            <option value="photo_spoofing">photo_spoofing</option>
            <option value="video_replay">video_replay</option>
            <option value="otp_channel_compromise">otp_channel_compromise</option>
        </select>
    </div>

    <div style="margin-top: .75rem;">
        <button id="runExperiments">Run Experiments</button>
        <button id="exportCsv">Export CSV</button>
        <span id="experimentStatus" class="muted"></span>
    </div>

    <div id="experimentMeta" style="margin-top:.7rem;"></div>
    <table id="experimentTable">
        <thead>
        <tr>
            <th>Configuration</th>
            <th>Scenario</th>
            <th>TAR</th>
            <th>FAR</th>
            <th>Attack Success Rate</th>
            <th>Completion Time (s)</th>
            <th>Verification Failure Rate</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
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
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const tableBody = document.querySelector('#experimentTable tbody');
const experimentStatus = document.getElementById('experimentStatus');
const experimentMeta = document.getElementById('experimentMeta');
const scenarioFilter = document.getElementById('scenarioFilter');
let latestExperimentResults = [];

const addLog = (label, obj) => {
  logEl.textContent += `${label}\n${JSON.stringify(obj, null, 2)}\n\n`;
};

async function postJson(url, payload) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin',
    body: JSON.stringify(payload)
  });

  let data = {};
  try {
    data = await res.json();
  } catch {
    data = { message: `Non-JSON response (HTTP ${res.status})` };
  }

  if (!res.ok) {
    const msg = data?.message || `Request failed with HTTP ${res.status}`;
    throw new Error(msg);
  }

  return data;
}

function getSelectedConfigurations() {
  return Array.from(document.querySelectorAll('.configOption:checked')).map((el) => el.value);
}

function renderExperimentTable() {
  tableBody.innerHTML = '';

  const selectedScenario = scenarioFilter.value;
  const rows = latestExperimentResults.filter((row) => selectedScenario === 'all' || row.scenario === selectedScenario);

  for (const row of rows) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.configuration}</td>
      <td>${row.scenario}</td>
      <td>${row.tar}</td>
      <td>${row.far}</td>
      <td>${row.attack_success_rate}</td>
      <td>${row.completion_time_seconds}</td>
      <td>${row.verification_failure_rate}</td>
    `;
    tableBody.appendChild(tr);
  }
}

function downloadCsv(filename, rows) {
  if (!rows.length) {
    throw new Error('No experiment data to export yet. Run experiments first.');
  }

  const headers = [
    'configuration',
    'scenario',
    'tar',
    'far',
    'attack_success_rate',
    'completion_time_seconds',
    'verification_failure_rate'
  ];

  const csv = [headers.join(',')]
    .concat(rows.map((r) => headers.map((h) => JSON.stringify(r[h] ?? '')).join(',')))
    .join('\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

document.getElementById('runFlow').addEventListener('click', async () => {
  logEl.textContent = '';
  resultEl.textContent = 'Running...';

  try {
    const init = await postJson('/signing/initiate', { document_id: 'DOC-DEMO-001', channel: 'email' });
    addLog('1) Initiate', init);

    if (!init?.otp?.otp) {
      throw new Error('Initiate response does not contain OTP payload.');
    }

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

    if (!apply?.signature?.document_hash) {
      throw new Error('Apply signature response missing document hash.');
    }

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

document.getElementById('runExperiments').addEventListener('click', async () => {
  experimentStatus.textContent = 'Running experiments...';

  try {
    const configurations = getSelectedConfigurations();
    if (!configurations.length) {
      throw new Error('Pick at least one configuration.');
    }

    const payload = { configurations };
    const response = await postJson('/experiments/run', payload);
    latestExperimentResults = response.results || [];

    renderExperimentTable();

    const scenarios = [...new Set(latestExperimentResults.map((r) => r.scenario))];
    experimentMeta.innerHTML = `
      <span class="chip">Rows: ${latestExperimentResults.length}</span>
      <span class="chip">Configurations: ${configurations.length}</span>
      <span class="chip">Scenarios: ${scenarios.join(', ')}</span>
    `;

    experimentStatus.innerHTML = '<span class="ok">Experiments completed.</span>';
  } catch (error) {
    experimentStatus.innerHTML = `<span class="bad">${error.message}</span>`;
  }
});

scenarioFilter.addEventListener('change', renderExperimentTable);

document.getElementById('exportCsv').addEventListener('click', () => {
  try {
    const selectedScenario = scenarioFilter.value;
    const rows = latestExperimentResults.filter((row) => selectedScenario === 'all' || row.scenario === selectedScenario);
    downloadCsv(`experiment-results-${selectedScenario}.csv`, rows);
    experimentStatus.innerHTML = '<span class="ok">CSV exported.</span>';
  } catch (error) {
    experimentStatus.innerHTML = `<span class="bad">${error.message}</span>`;
  }
});
</script>
</body>
</html>
