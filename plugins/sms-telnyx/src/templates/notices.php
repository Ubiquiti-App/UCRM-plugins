<?php
/** @var string $csrf @var bool $configured @var array $user */
$e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SMS notices</title>
<style>
:root{--bg:#f6f7f9;--card:#fff;--ink:#1d2330;--muted:#687285;--line:#dfe3ea;--accent:#1e6fd9;--accent-ink:#fff;--ok:#1d8a4b;--warn:#b86e00;--bad:#c62f2f;--chip:#eef2f8}
@media (prefers-color-scheme:dark){:root{--bg:#161a21;--card:#1f242d;--ink:#e7ebf2;--muted:#9aa4b5;--line:#343b47;--accent:#5b9dff;--accent-ink:#0b1220;--chip:#2a313c}}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.45 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
.wrap{max-width:1100px;margin:0 auto;padding:16px}
h1{font-size:18px;margin:0 0 12px}h2{font-size:15px;margin:0 0 10px}
.tabs{display:flex;gap:4px;border-bottom:1px solid var(--line);margin-bottom:16px}
.tabs button{background:none;border:0;border-bottom:2px solid transparent;padding:8px 12px;color:var(--muted);font:inherit;cursor:pointer}
.tabs button.on{color:var(--ink);border-color:var(--accent);font-weight:600}
.card{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:16px;margin-bottom:16px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}@media(max-width:800px){.grid{grid-template-columns:1fr}}
label{display:block;font-weight:600;margin:10px 0 4px}.hint{color:var(--muted);font-size:12px;font-weight:400}
input[type=text],input[type=number],textarea,select{width:100%;padding:8px;border:1px solid var(--line);border-radius:6px;background:var(--card);color:var(--ink);font:inherit}
textarea{min-height:120px;resize:vertical}
.radios{display:flex;flex-wrap:wrap;gap:6px}.radios label{display:flex;align-items:center;gap:6px;margin:0;font-weight:500;padding:6px 10px;border:1px solid var(--line);border-radius:6px;cursor:pointer}
.radios input{margin:0}.radios label:has(input:checked){border-color:var(--accent);background:var(--chip)}
.chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}.chips label{margin:0;font-weight:500;padding:4px 10px;border-radius:14px;background:var(--chip);cursor:pointer;display:flex;gap:5px;align-items:center}
.chips label:has(input:checked){background:var(--accent);color:var(--accent-ink)}
.tokens button{margin:2px 4px 2px 0;padding:2px 8px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--muted);font-size:12px;cursor:pointer}
.btn{padding:8px 14px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--ink);font:inherit;font-weight:600;cursor:pointer}
.btn.primary{background:var(--accent);border-color:var(--accent);color:var(--accent-ink)}.btn:disabled{opacity:.5;cursor:default}
.row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:12px}
.meta{color:var(--muted);font-size:12px}
.phone{background:var(--chip);border-radius:14px;padding:12px;white-space:pre-wrap;max-width:340px;font-size:13px}
table{width:100%;border-collapse:collapse;font-size:13px}th,td{text-align:left;padding:6px 8px;border-bottom:1px solid var(--line);vertical-align:top}
th{color:var(--muted);font-weight:600;font-size:12px}tr.click{cursor:pointer}tr.click:hover{background:var(--chip)}
.scroll{max-height:380px;overflow:auto}
.s-sent,.s-delivered{color:var(--ok)}.s-failed,.s-delivery_failed,.s-sending_failed{color:var(--bad)}.s-pending,.s-queued{color:var(--muted)}.s-skipped,.s-cancelled{color:var(--warn)}
.alert{padding:10px 12px;border-radius:6px;margin-bottom:12px;border:1px solid var(--line)}.alert.warn{border-color:var(--warn);color:var(--warn)}.alert.bad{border-color:var(--bad);color:var(--bad)}.alert.ok{border-color:var(--ok);color:var(--ok)}
.bar{height:8px;background:var(--chip);border-radius:4px;overflow:hidden;margin-top:8px}.bar div{height:100%;background:var(--accent);width:0;transition:width .3s}
.hide{display:none!important}
</style>
</head>
<body>
<div class="wrap">
  <h1>SMS notices</h1>
  <?php if (! $configured): ?><div class="alert bad">Telnyx isn't configured yet — set the API key and sending number in System → Plugins → SMS via Telnyx.</div><?php endif; ?>
  <div class="tabs">
    <button class="on" data-tab="compose">Compose</button>
    <button data-tab="history">History</button>
    <button data-tab="optouts">Opt-outs &amp; replies</button>
  </div>

  <section id="tab-compose">
    <div id="msg"></div>
    <div class="grid">
      <div class="card">
        <h2>1. Recipients</h2>
        <div class="radios" id="aud-type">
          <label><input type="radio" name="aud" value="pop" checked> By POP / tower</label>
          <label><input type="radio" name="aud" value="all"> All active clients</label>
          <label><input type="radio" name="aud" value="tag"> By tag</label>
          <label><input type="radio" name="aud" value="overdue"> Overdue</label>
        </div>
        <div data-aud="pop">
          <label>POPs <span class="hint" id="pop-hint"></span></label>
          <div class="chips" id="pops"></div>
        </div>
        <div data-aud="all" class="hide">
          <label><input type="checkbox" id="incSusp"> Include suspended services</label>
        </div>
        <div data-aud="tag" class="hide">
          <label>Tags <span class="hint">clients with any selected tag</span></label>
          <div class="chips" id="tags"></div>
          <label><input type="checkbox" id="activeOnly" checked> Only clients with an active service</label>
        </div>
        <div data-aud="overdue" class="hide">
          <label>Oldest unpaid invoice at least <input type="number" id="odDays" value="60" min="0" style="width:80px;display:inline-block"> days past due</label>
          <div class="hint">Uses each client's billing contact number.</div>
        </div>
      </div>

      <div class="card">
        <h2>2. Message</h2>
        <label>Internal title <span class="hint">for History only</span></label>
        <input type="text" id="title" placeholder="e.g. SID tower maintenance Sat 6am">
        <label>Text</label>
        <textarea id="text" placeholder="Planned maintenance on the %%site.pop%% tower Saturday 6-7 AM. Service may drop briefly."></textarea>
        <div class="tokens">
          <button data-t="%%client.firstName%%">first name</button><button data-t="%%client.name%%">name</button>
          <button data-t="%%client.accountOutstanding%%">balance</button><button data-t="%%site.pop%%">POP</button>
          <button data-t="%%overdue.amount%%">overdue $</button><button data-t="%%overdue.days%%">days overdue</button>
          <button data-t="%%overdue.count%%">unpaid invoices</button>
        </div>
        <div class="meta" id="counter"></div>
      </div>
    </div>

    <div class="card">
      <div class="row" style="margin-top:0">
        <button class="btn" id="btn-preview">Preview recipients</button>
        <button class="btn" id="btn-test" disabled>Send test to me</button>
        <span style="flex:1"></span>
        <label id="urgent-wrap" class="hide" style="margin:0"><input type="checkbox" id="urgent"> Urgent (send during quiet hours)</label>
        <button class="btn primary" id="btn-send" disabled>Send</button>
      </div>
      <div id="progress" class="hide"><div class="bar"><div id="bar"></div></div><div class="meta" id="prog-text"></div>
        <div class="row"><button class="btn" id="btn-cancel">Stop sending</button></div></div>
      <div id="preview" class="hide" style="margin-top:16px">
        <div class="meta" id="pv-summary"></div>
        <div class="grid" style="margin-top:12px">
          <div><div class="meta" style="margin-bottom:6px">First message as it will arrive:</div><div class="phone" id="pv-sample"></div></div>
          <div class="scroll"><table><thead><tr><th>Client</th><th>Phone</th><th>Seg</th></tr></thead><tbody id="pv-rows"></tbody></table></div>
        </div>
        <details style="margin-top:12px"><summary class="meta" id="pv-skip-sum"></summary>
          <table><tbody id="pv-skips"></tbody></table></details>
      </div>
    </div>
  </section>

  <section id="tab-history" class="hide">
    <div class="card"><table><thead><tr><th>When</th><th>Title</th><th>Audience</th><th>By</th><th>Sent</th><th>Failed</th><th>Skipped</th><th>State</th></tr></thead>
      <tbody id="hist"></tbody></table></div>
    <div class="card hide" id="detail"><h2 id="d-title"></h2><div class="phone" id="d-msg" style="max-width:none;margin-bottom:12px"></div>
      <div class="scroll"><table><thead><tr><th>Client</th><th>Phone</th><th>Status</th><th>Delivery</th><th>Error</th></tr></thead><tbody id="d-rows"></tbody></table></div></div>
  </section>

  <section id="tab-optouts" class="hide">
    <div class="grid">
      <div class="card"><h2>Opted out</h2><div class="scroll"><table><thead><tr><th>Number</th><th>When</th><th>How</th></tr></thead><tbody id="oo"></tbody></table></div></div>
      <div class="card"><h2>Recent replies</h2><div class="scroll"><table><thead><tr><th>When</th><th>From</th><th>Text</th></tr></thead><tbody id="rep"></tbody></table></div></div>
    </div>
  </section>
</div>

<script>
(() => {
  const CSRF = <?= json_encode($csrf) ?>;
  const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const api = async (action, body, qs = '') => {
    const base = location.pathname + '?page=notices&api=' + action + qs;
    const r = await fetch(base, body ? {method:'POST', headers:{'Content-Type':'application/json','X-CSRF':CSRF}, body:JSON.stringify(body), credentials:'same-origin'}
                                   : {credentials:'same-origin'});
    const j = await r.json().catch(() => ({ok:false, error:'Bad response (' + r.status + ')'}));
    if (!j.ok) throw new Error(j.error || 'Request failed');
    return j;
  };
  const note = (html, kind = 'warn') => { $('#msg').innerHTML = html ? `<div class="alert ${kind}">${html}</div>` : ''; };
  let lastPreview = null, job = null, stop = false, tagNames = {};

  // tabs
  $$('.tabs button').forEach(b => b.onclick = () => {
    $$('.tabs button').forEach(x => x.classList.toggle('on', x === b));
    ['compose','history','optouts'].forEach(t => $('#tab-' + t).classList.toggle('hide', t !== b.dataset.tab));
    if (b.dataset.tab === 'history') loadHistory();
    if (b.dataset.tab === 'optouts') loadOptouts();
  });

  // audience switching
  const audType = () => $('input[name=aud]:checked').value;
  $$('input[name=aud]').forEach(r => r.onchange = () => { $$('[data-aud]').forEach(d => d.classList.toggle('hide', d.dataset.aud !== audType())); invalidate(); });
  const audience = () => {
    const t = audType(), a = {type: t};
    if (t === 'pop') a.pops = $$('#pops input:checked').map(i => i.value);
    if (t === 'all') a.includeSuspended = $('#incSusp').checked;
    if (t === 'tag') { a.tags = $$('#tags input:checked').map(i => +i.value); a.tagNames = a.tags.map(i => tagNames[i]); a.activeOnly = $('#activeOnly').checked; }
    if (t === 'overdue') a.overdueDays = +$('#odDays').value || 0;
    return a;
  };
  const invalidate = () => { lastPreview = null; $('#btn-send').disabled = true; $('#btn-send').textContent = 'Send'; $('#btn-test').disabled = true; $('#preview').classList.add('hide'); };
  document.addEventListener('change', e => { if (e.target.closest('#pops,#tags,#incSusp,#activeOnly,#odDays')) invalidate(); });
  $('#odDays').oninput = invalidate;

  // tokens + counter
  $$('.tokens button').forEach(b => b.onclick = () => {
    const t = $('#text'), p = t.selectionStart ?? t.value.length;
    t.value = t.value.slice(0, p) + b.dataset.t + t.value.slice(t.selectionEnd ?? p); t.focus(); t.oninput();
  });
  const GSM = /^[@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&'()*+,\-.\/0-9:;<=>?¡A-ZÄÖÑÜ§¿a-zäöñüà^{}\\\[~\]|€]*$/;
  $('#text').oninput = () => {
    invalidate();
    const v = $('#text').value, gsm = GSM.test(v), n = [...v].length;
    const seg = n === 0 ? 0 : gsm ? (n <= 160 ? 1 : Math.ceil(n / 153)) : (n <= 70 ? 1 : Math.ceil(n / 67));
    const odd = gsm ? [] : [...new Set([...v].filter(c => !GSM.test(c)))];
    $('#counter').innerHTML = n ? `${n} chars before prefix/footer/tokens · ~${seg} segment${seg > 1 ? 's' : ''}` +
      (gsm ? '' : ` · <span style="color:var(--warn)">${odd.map(c => '“' + esc(c) + '”').join(' ')} ${odd.length > 1 ? 'aren’t' : 'isn’t'} standard SMS text, so each segment holds 70 chars instead of 160 — replace with plain characters (e.g. - instead of –) to cut cost</span>`) : '';
  };

  // options
  api('options').then(o => {
    $('#pops').innerHTML = o.pops.map(p => `<label><input type="checkbox" value="${esc(p)}" hidden>${esc(p.toUpperCase())}</label>`).join('')
      || '<span class="meta">No UISP token set — POP list unavailable. Add the UISP API token in plugin settings, or target by tag.</span>';
    $('#pop-hint').textContent = o.nms ? 'from UISP sites; clients not linked in UISP fall back to a same-named CRM tag' : '';
    o.tags.forEach(t => tagNames[t.id] = t.name);
    $('#tags').innerHTML = o.tags.map(t => `<label><input type="checkbox" value="${t.id}" hidden>${esc(t.name)}</label>`).join('');
  }).catch(e => note(esc(e.message), 'bad'));

  // preview
  $('#btn-preview').onclick = async () => {
    note('');
    const b = $('#btn-preview'); b.disabled = true; b.textContent = 'Loading…';
    try {
      const p = await api('preview', {audience: audience(), message: $('#text').value});
      lastPreview = p;
      $('#preview').classList.remove('hide');
      $('#pv-summary').innerHTML = `<b>${p.count}</b> recipients · ${p.segments} segments · est. <b>$${p.cost.toFixed(2)}</b>`;
      $('#pv-sample').textContent = p.rows[0]?.text ?? '(no recipients)';
      $('#pv-rows').innerHTML = p.rows.map(r => `<tr title="${esc(r.text)}"><td>${esc(r.name)}</td><td>${esc(r.phone)}</td><td>${r.segments}</td></tr>`).join('');
      $('#pv-skip-sum').textContent = `${p.skipped.length} skipped (no phone, opted out, duplicate)`;
      $('#pv-skips').innerHTML = p.skipped.map(s => `<tr><td>${esc(s.name)}</td><td>${esc(s.reason)}</td></tr>`).join('');
      $('#urgent-wrap').classList.toggle('hide', !p.quiet);
      if (p.quiet) note('It is quiet hours (9 PM–8 AM). Mass notices are blocked unless marked urgent.');
      $('#btn-send').disabled = p.count === 0; $('#btn-test').disabled = p.count === 0;
      $('#btn-send').textContent = `Send to ${p.count} client${p.count === 1 ? '' : 's'}`;
    } catch (e) { note(esc(e.message), 'bad'); }
    b.disabled = false; b.textContent = 'Preview recipients';
  };

  $('#btn-test').onclick = async () => {
    try { const r = await api('test', {audience: audience(), message: $('#text').value}); note(`Test sent to ${esc(r.sentTo)}.`, 'ok'); }
    catch (e) { note(esc(e.message), 'bad'); }
  };

  // send
  $('#btn-send').onclick = async () => {
    if (!lastPreview) return;
    if (!confirm(`Send this text to ${lastPreview.count} clients now?\n\nEstimated cost $${lastPreview.cost.toFixed(2)}.`)) return;
    note(''); stop = false;
    $('#btn-send').disabled = $('#btn-preview').disabled = $('#btn-test').disabled = true;
    try {
      job = await api('create', {audience: audience(), message: $('#text').value, title: $('#title').value, urgent: $('#urgent').checked});
      $('#progress').classList.remove('hide');
      while (!stop) {
        job = await api('batch', {id: job.id});
        const done = job.sent + job.failed + job.cancelled;
        $('#bar').style.width = (job.total ? 100 * done / job.total : 100) + '%';
        $('#prog-text').textContent = `${job.sent} sent · ${job.failed} failed · ${job.pending} left`;
        if (job.state !== 'sending') break;
      }
      note(`Finished: ${job.sent} sent, ${job.failed} failed, ${job.skippedCount} skipped. Details in History.`, job.failed ? 'warn' : 'ok');
    } catch (e) { note(esc(e.message) + (job ? ' — the job can be resumed from History.' : ''), 'bad'); }
    $('#btn-preview').disabled = false; $('#progress').classList.add('hide'); invalidate();
  };
  $('#btn-cancel').onclick = async () => { stop = true; if (job) { try { await api('cancel', {id: job.id}); } catch (e) {} } };

  // history
  const fmt = s => s ? new Date(s).toLocaleString([], {month:'short', day:'numeric', hour:'numeric', minute:'2-digit'}) : '';
  async function loadHistory() {
    try {
      const h = await api('history');
      $('#hist').innerHTML = h.jobs.map(j => `<tr class="click" data-id="${esc(j.id)}"><td>${fmt(j.created)}</td><td>${esc(j.title || '—')}</td><td>${esc(j.audience)}</td>
        <td>${esc(j.by)}</td><td>${j.sent}/${j.total}</td><td>${j.failed}</td><td>${j.skippedCount}</td><td class="s-${esc(j.state)}">${esc(j.state)}
        ${j.state === 'sending' ? ` <button class="btn resume" data-id="${esc(j.id)}">Resume</button>` : ''}</td></tr>`).join('')
        || '<tr><td colspan="8" class="meta">No mass notices yet.</td></tr>';
      $$('#hist tr.click').forEach(tr => tr.onclick = e => { if (!e.target.classList.contains('resume')) showDetail(tr.dataset.id); });
      $$('#hist .resume').forEach(b => b.onclick = async () => {
        b.disabled = true;
        let j; do { j = await api('batch', {id: b.dataset.id}); b.textContent = `${j.pending} left…`; } while (j.state === 'sending');
        loadHistory();
      });
    } catch (e) { $('#hist').innerHTML = `<tr><td colspan="8" class="s-failed">${esc(e.message)}</td></tr>`; }
  }
  async function showDetail(id) {
    const d = await api('detail', null, '&id=' + encodeURIComponent(id));
    $('#detail').classList.remove('hide');
    $('#d-title').textContent = `${d.title || 'Mass notice'} — ${d.audience}`;
    $('#d-msg').textContent = d.message;
    $('#d-rows').innerHTML = d.queue.map(q => `<tr><td>${esc(q.name)}</td><td>${esc(q.phone)}</td><td class="s-${esc(q.status)}">${esc(q.status)}</td>
      <td class="s-${esc(q.delivery || '')}">${esc(q.delivery || '')}</td><td>${esc(q.error || q.deliveryError || '')}</td></tr>`).join('')
      + d.skipped.map(s => `<tr><td>${esc(s.name)}</td><td>${esc(s.phone || '')}</td><td class="s-skipped">skipped</td><td></td><td>${esc(s.reason)}</td></tr>`).join('');
    $('#detail').scrollIntoView({behavior: 'smooth'});
  }

  async function loadOptouts() {
    const o = await api('optouts');
    const oo = Object.entries(o.optouts);
    $('#oo').innerHTML = oo.map(([n, v]) => `<tr><td>${esc(n)}</td><td>${fmt(v.at)}</td><td>${esc(v.source)}</td></tr>`).join('') || '<tr><td colspan="3" class="meta">None</td></tr>';
    $('#rep').innerHTML = o.replies.map(r => `<tr><td>${fmt(r.at)}</td><td>${esc(r.from)}</td><td>${esc(r.text)}</td></tr>`).join('') || '<tr><td colspan="3" class="meta">None</td></tr>';
  }
})();
</script>
</body>
</html>
