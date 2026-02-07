<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem auto; max-width: 1100px; line-height: 1.45; }
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
        .row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .chip { display: inline-block; border: 1px solid #ddd; border-radius: 999px; padding: .2rem .6rem; margin-right: .3rem; margin-top:.2rem; }
        label { font-size: .95rem; }
        .stack { display:flex; flex-direction:column; gap:.45rem; }
        input[type="text"], input[type="number"], select { padding:.35rem; min-width:200px; }
    </style>
</head>
<body>
<h1>{{ $title }}</h1>
<p class="muted">Run live flow, record empirical attempts (labels + outcomes), then compute TAR/FAR/ASR/VFR from stored data.</p>

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
    <p>This simulates a legitimate user passing OTP + liveness then receiving a digital signature. It also stores one empirical attempt automatically.</p>
    <button id="runFlow">Run Full Flow</button>
    <p id="result"></p>
    <pre id="log"></pre>
</div>

<div class="card">
    <h3>Record empirical attempt (manual)</h3>
    <p class="muted">Use this to store real experiment outcomes per-attempt. Labels and accepted/rejected outcome are computed server-side.</p>
    <div class="row">
        <div class="stack">
            <label>Document ID <input id="attDocumentId" type="text" value="DOC-MANUAL-001"></label>
            <label>Signer ID <input id="attSigner" type="text" value="participant-01"></label>
            <label>Configuration
                <select id="attConfig">
                    <option value="configuration_a_otp_only">configuration_a_otp_only</option>
                    <option value="configuration_b_liveness_only">configuration_b_liveness_only</option>
                    <option value="configuration_c_otp_plus_liveness" selected>configuration_c_otp_plus_liveness</option>
                </select>
            </label>
            <label>Threat Scenario
                <select id="attScenario">
                    <option value="legitimate_user">legitimate_user</option>
                    <option value="photo_spoofing">photo_spoofing</option>
                    <option value="video_replay">video_replay</option>
                    <option value="otp_channel_compromise">otp_channel_compromise</option>
                </select>
            </label>
        </div>

        <div class="stack">
            <label><input id="attOtp" type="checkbox" checked> OTP passed</label>
            <label><input id="attLiveness" type="checkbox" checked> Liveness passed</label>
            <label><input id="attSignature" type="checkbox" checked> Signature verify passed</label>
            <label>Completion time (seconds)
                <input id="attTime" type="number" min="0" value="18">
            </label>
            <label>Failure reason <input id="attFailure" type="text" placeholder="optional"></label>
        </div>
    </div>
    <div style="margin-top: .75rem;">
        <button id="saveAttempt">Save Attempt</button>
        <span id="attemptStatus" class="muted"></span>
    </div>
</div>

<div class="card">
    <h3>Run Scenario Experiments from stored attempts</h3>
    <p class="muted">Metrics are now aggregated from saved attempts (empirical) rather than generated synthetic numbers.</p>

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
            <th>Attempts</th>
            <th>TP</th>
            <th>FN</th>
            <th>FP</th>
            <th>TN</th>
            <th>TAR (%)</th>
            <th>FAR (%)</th>
            <th>Attack Success Rate (%)</th>
            <th>Avg Completion Time (s)</th>
            <th>Verification Failure Rate (%)</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
const logEl = document.getElementById('log');
const resultEl = document.getElementById('result');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const tableBody = document.querySelector('#experimentTable tbody');
const experimentStatus = document.getElementById('experimentStatus');
const experimentMeta = document.getElementById('experimentMeta');
const scenarioFilter = document.getElementById('scenarioFilter');
const attemptStatus = document.getElementById('attemptStatus');
let latestExperimentResults = [];

const addLog = (label, obj) => {
  logEl.textContent += `${label}\n${JSON.stringify(obj, null, 2)}\n\n`;
};

const showNum = (value) => value === null || value === undefined ? 'N/A' : value;

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
      <td>${showNum(row.attempt_count)}</td>
      <td>${showNum(row.tp)}</td>
      <td>${showNum(row.fn)}</td>
      <td>${showNum(row.fp)}</td>
      <td>${showNum(row.tn)}</td>
      <td>${showNum(row.tar)}</td>
      <td>${showNum(row.far)}</td>
      <td>${showNum(row.attack_success_rate)}</td>
      <td>${showNum(row.completion_time_seconds)}</td>
      <td>${showNum(row.verification_failure_rate)}</td>
    `;
    tableBody.appendChild(tr);
  }
}

function downloadCsv(filename, rows) {
  if (!rows.length) {
    throw new Error('No experiment data to export yet. Run experiments first.');
  }

  const headers = [
    'configuration', 'scenario', 'attempt_count', 'tp', 'fn', 'fp', 'tn',
    'tar', 'far', 'attack_success_rate', 'completion_time_seconds', 'verification_failure_rate'
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

async function saveAttempt(payload) {
  return postJson('/experiments/attempts', payload);
}

document.getElementById('saveAttempt').addEventListener('click', async () => {
  attemptStatus.textContent = 'Saving...';

  try {
    const payload = {
      document_id: document.getElementById('attDocumentId').value,
      signer_identifier: document.getElementById('attSigner').value,
      verification_configuration: document.getElementById('attConfig').value,
      threat_scenario: document.getElementById('attScenario').value,
      otp_status: document.getElementById('attOtp').checked,
      liveness_status: document.getElementById('attLiveness').checked,
      signature_status: document.getElementById('attSignature').checked,
      completion_time_seconds: Number(document.getElementById('attTime').value || 0),
      failure_reason: document.getElementById('attFailure').value || null
    };

    const response = await saveAttempt(payload);
    attemptStatus.innerHTML = `<span class="ok">Saved attempt #${response.attempt.id} (${response.attempt.actor_label} / ${response.attempt.outcome_label}).</span>`;
  } catch (error) {
    attemptStatus.innerHTML = `<span class="bad">${error.message}</span>`;
  }
});

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

    const stored = await saveAttempt({
      document_id: init.document_id || `DOC-${Date.now()}`,
      signer_identifier: 'demo-user',
      verification_configuration: 'configuration_c_otp_plus_liveness',
      threat_scenario: 'legitimate_user',
      otp_status: !!otpCheck.otp_valid,
      liveness_status: !!livenessCheck.liveness_valid,
      signature_status: !!verify.signature_valid,
      completion_time_seconds: 12,
      failure_reason: success ? null : 'Demo flow check failed'
    });
    addLog('6) Stored Empirical Attempt', stored);

    resultEl.innerHTML = success
      ? '<span class="ok">Success: signing flow passed and attempt stored.</span>'
      : '<span class="bad">Failed: one or more checks failed, attempt stored as rejected.</span>';
  } catch (error) {
    resultEl.innerHTML = `<span class="bad">Error: ${error.message}</span>`;
  }
});

document.getElementById('runExperiments').addEventListener('click', async () => {
  experimentStatus.textContent = 'Computing metrics from stored attempts...';

  try {
    const configurations = getSelectedConfigurations();
    if (!configurations.length) {
      throw new Error('Pick at least one configuration.');
    }

    const payload = { configurations };
    const response = await postJson('/experiments/run', payload);
    latestExperimentResults = response.results || [];

    renderExperimentTable();

    const totalAttempts = latestExperimentResults.reduce((sum, row) => sum + (row.attempt_count || 0), 0);
    const scenarios = [...new Set(latestExperimentResults.map((r) => r.scenario))];
    experimentMeta.innerHTML = `
      <span class="chip">Rows: ${latestExperimentResults.length}</span>
      <span class="chip">Configurations: ${configurations.length}</span>
      <span class="chip">Scenarios: ${scenarios.join(', ')}</span>
      <span class="chip">Total attempts counted: ${totalAttempts}</span>
    `;

    experimentStatus.innerHTML = '<span class="ok">Empirical metrics computed.</span>';
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
