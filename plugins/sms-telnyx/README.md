# SMS via Telnyx — UISP CRM plugin (v1.0.0)

An alternative to the "SMS notifications via Twilio" plugin using [Telnyx](https://telnyx.com), with mass notices and reply handling. Contributed by BitWind LLC.

- **Event texts** — same model as the Twilio plugin: a message template per CRM event (invoice overdue, near due,
  service suspended/restored, payment received, client message…). Blank template = no text for that event.
- **Mass notices** — *Billing → SMS notices* in CRM. Pick recipients **by POP/tower**, **all active clients**,
  **by tag**, or **overdue N+ days**; preview every rendered message with cost estimate; send a test to yourself;
  send in batches with progress; full history with per-recipient delivery status.
- **Replies** — STOP/START/HELP handled automatically (opt-outs are enforced on every send); any other reply is
  texted to your phone with the client's name, and kept on the *Opt-outs & replies* tab.
- No third-party PHP dependencies. Inbound Telnyx webhooks are verified (Ed25519); CRM webhook events are verified
  against the CRM's own webhook log; the admin page requires a CRM admin session with client edit rights.

---

## 1. Telnyx setup (one time)

1. **Account** — sign up at telnyx.com, verify, and add a small prepaid balance.
2. **Number** — *Numbers → Search & Buy*: a local number (e.g. 712 or 402) with **SMS** capability.
3. **Messaging profile** — *Messaging → Programmable Messaging → Add new profile*:
   - Assign your number to it.
   - **Inbound → Send a webhook to this URL**: the plugin's public URL with `?hook=telnyx` appended
     (the plugin writes the exact URL to its log after you run it once — see step 2.4 below), e.g.
     `https://your-uisp.example.com/crm/_plugins/sms-telnyx/public.php?hook=telnyx`
   - Webhook API version: **API v2**.
   - Leave Telnyx's built-in opt-out handling **on** (it auto-replies to STOP). The plugin still records the opt-out.
4. **10DLC registration (required for US business texting)** — *Messaging → Compliance*:
   - **Brand**: your company, EIN, business address and website.
   - **Campaign**: use case *Mixed* (or *Account Notification* + *Customer Care*). Suggested wording:
     - *Description*: "Account and service notices for our internet customers: billing reminders, past-due and
       suspension notices, outage and maintenance alerts, and replies to customer questions."
     - *Opt-in*: "Customers provide their mobile number when signing up for service and agree to receive
       account and service text messages. Reply STOP to opt out, HELP for help."
     - *Sample messages*: "YourISP: Invoice 2026-0101 for $70.00 is past due. Reply STOP to opt out." and
       "YourISP: Planned maintenance on the SID tower Saturday 6-7 AM. Service may drop briefly. Reply STOP to opt out."
   - Assign your number to the approved campaign. Approval typically takes a few days to two weeks;
     unregistered traffic is filtered by carriers.
5. **Keys** — *Account → API Keys*: create a v2 API key. *Account → Keys & Credentials → Public Key*: copy the
   webhook public key.

## 2. Install the plugin

1. UISP → CRM → **System → Plugins → Add plugin** → upload [`sms-telnyx.zip`](https://github.com/Ubiquiti-App/UCRM-plugins/raw/master/plugins/sms-telnyx/sms-telnyx.zip).
2. Fill in the settings:
   | Setting | Value |
   |---|---|
   | Telnyx API key | from step 1.5 |
   | Telnyx sending number | E.164, e.g. `+17125551234` |
   | Messaging profile ID | optional (the number's profile is used otherwise) |
   | Telnyx webhook public key | from step 1.5 — required for STOP/HELP/replies |
   | Forward customer replies to | your mobile, e.g. `+14025550123` (also receives "Send test to me") |
   | Message prefix / Opt-out footer | e.g. `YourISP: ` and `Reply STOP to opt out.` (identify your business in every text) |
   | Time zone | default `America/Chicago` — quiet hours and dates use it (the CRM container runs on UTC) |
   | UISP (NMS) API token | read-only token from UISP → Settings → Users → App Tokens — enables POP targeting |
   | Event templates | defaults are set for near-due, overdue, suspended, restored and client message |
3. **Enable** the plugin. Leave *Execution period* at "don't execute automatically".
4. Click **Execute manually** once — the log shows a config check and the exact Telnyx webhook URL for step 1.3.
5. **CRM event webhooks** — on the plugin page click **Add webhook** (next to Public URL) and select the events you
   have templates for: `invoice.near_due`, `invoice.overdue`, `service.suspend`, `service.suspend_cancel`,
   `client.message` (+ `payment.add` etc. if you fill those templates). Then *System → Webhooks → Endpoints →
   Test endpoint*: the plugin log shows `Webhook test successful.`
6. If the old Twilio plugin is installed, disable it or remove its webhook endpoint so clients don't get two texts.

## 3. Using mass notices

*Billing → SMS notices*:
1. **Recipients** — POP chips come from UISP tower sites; a client is matched through the UISP site linked to
   their service, or (if not linked) a CRM tag with the same name as the POP. *All active* = clients with an active
   service (optionally suspended too). *Tag* = any selected tag. *Overdue* = oldest unpaid invoice ≥ N days past
   due, sent to the billing contact.
2. **Message** — token buttons insert `%%client.firstName%%`, `%%client.name%%`, `%%client.accountOutstanding%%`,
   `%%site.pop%%`, `%%overdue.amount%%`, `%%overdue.days%%`, `%%overdue.count%%`. Any `%%client.<field>%%` from the
   CRM API also works. The counter flags characters (smart quotes, en dashes, emoji) that cut a segment from 160 to
   70 characters.
3. **Preview recipients** → check the rendered first message, recipient list, skipped list (no phone / opted out /
   duplicate number) and estimated cost → **Send test to me** → **Send**.
4. Keep the page open while it sends (10 per batch, ~4/second). If it's closed mid-send, **Resume** from History.
5. Quiet hours (9 PM–8 AM in the plugin's *Time zone* setting, default America/Chicago) block mass notices unless you tick *Urgent* — use that for outages.

## 4. Event template tokens

`%%client.*%%`, `%%invoice.*%%` (invoice events), `%%payment.*%%` (payment events), `%%service.*%%` (service events,
plus `%%service.stopReason%%`), `%%client.message%%` (client.message). Money fields are formatted `70.00`, dates
`Sep 22, 2026`. Unknown tokens render as empty. Invoice, payment and suspension texts go to the client's billing
contact; others to the general contact; either falls back to any contact with a valid phone.

## 5. Files the plugin keeps (in `data/`, preserved across updates)

`plugin.log` (shown in CRM) · `store/optouts.json` · `store/replies.json` (last 1,000) · `store/sent.json` (last
5,000 sends) · `store/dlr.json` (delivery receipts) · `store/job-*.json` (mass notices, pruned after 180 days).

## Changelog

- **1.0.0** (2026-09-22) — initial release: Telnyx event texts, mass notices (POP / all / tag / overdue), STOP/START/HELP,
  reply forwarding, delivery receipts, quiet hours, cost estimate.
