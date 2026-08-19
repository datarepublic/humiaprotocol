# HUMIA public-content guardrails

These guardrails are intended to protect credibility while HUMIA is still experimental.

- Always identify the protocol as draft / experimental until that status changes.
- Never claim support, adoption or compliance by an AI company without direct evidence.
- Never claim that a crawler discovered or respected HUMIA merely because it visited a HUMIA-enabled website.
- Present HangarRC only as an early public pilot on the publisher side.
- Describe HUMIA as complementary to robots.txt, not as a replacement.
- Keep payment, settlement and commercial exchanges optional and outside the mandatory core.
- Do not present illustrative example manifests as the authoritative schema.
- Keep the protocol neutral between AI companies, publishers, business models and implementation vendors.

- Describe `Humia:` in robots.txt as an experimental discovery extension, not a standardized robots.txt directive.
- Do not claim that existing crawlers read `Humia:` unless direct evidence exists.
- Do not describe `/.well-known/humia.json` as IANA-registered until a registration actually exists.

## Generator V0 guardrails

- No account, email, captcha, cookies, analytics, backend or remote form submission.
- All generation happens locally in the browser.
- Balanced is the default preset.
- Training is never enabled by a default preset in Generator V0.
- Generated output must remain visibly labeled as HUMIA Protocol v0.3 Draft / experimental.
- The generator must not claim that robots or AI providers will honor the generated policy.
- The generator must not overwrite or generate replacements for existing robots.txt Allow/Disallow rules; it emits only the HUMIA discovery snippet.
