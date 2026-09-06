# Interview Follow-Up Prep

## Walk me through what was happening in your head when Ticket B landed.

Ticket B arrived while I was mid-way through Ticket A. My first thought was triage: this is marked URGENT, and it's a data integrity issue — wrong data silently written is worse than an error because nobody knows to look for it.

I paused Ticket A, assessed the damage scope: "silent" misparse means the data is in the database already, not just failing at import. That changes the fix from "fix the parser" to "fix the parser AND remediate historical data." The parser alone only prevents future damage; the corrupted records are already there.

I built the parser to handle multiple formats (ISO, UK, US, German, common written) and created a remediation script that logs every correction for audit. If Northgate's data matters to billing or compliance, they need to know which records changed and why.

I flagged that some dates might be truly unrecoverable — the script reports failures separately from fixes so they can investigate those manually.

---

## Show me the exact place the AI got the webhook idempotency wrong.

AI's initial suggestion was a simple `if (orderExists) return success` check without considering race conditions or the database transaction boundary.

The problem: if two webhooks for the same payment arrive nearly simultaneously, both could pass the existence check before either writes, creating duplicate orders.

The fix I implemented:

```php
// PaymentWebhookService.php - the critical section
if ($this->orderModel->exists($paymentId)) {
    $logData['status'] = 'duplicate';
    $this->webhookLogModel->create($logData);
    return ['success' => true, 'message' => 'Order already exists', 'statusCode' => 200];
}

$orderData = [
    'payment_id' => $paymentId,
    // ...
];
$this->orderModel->create($orderData);
```

With the Order model using a UNIQUE constraint on `payment_id`:

```php
// Order.php
$stmt = $this->db->prepare("
    INSERT INTO orders (payment_id, customer_email, amount, currency, status, metadata)
    VALUES (:payment_id, :customer_email, :amount, :currency, :status, :metadata)
");
```

The UNIQUE constraint is the real safety net. If two inserts race, the second one throws a constraint violation, and our error handling logs it and returns success (since the order exists — mission accomplished). The application-level `exists()` check is an optimization to avoid unnecessary work, not the idempotency guarantee. The database is.

AI missed that the idempotency guarantee must live at the data layer, not just the application layer.

---

## If Marlow calls tomorrow asking for auth, what do you build and how long does it take?

I'd ask one clarifying question first: "Is this internal-only, or do external users need access?"

**Option A — Internal users only (1-2 hours):**
Simple API key authentication. Generate keys, require `Authorization: Bearer <key>` header. Store keys hashed in a table. Check on each request. Quick, low friction, appropriate for internal tooling.

**Option B — External users or multiple tenants (4-8 hours):**
JWT-based auth with a lightweight middleware layer. I'd use `firebase/php-jwt` (already Composer-friendly). Register/login endpoints, token validation middleware on the events route. Still no user management UI — that's a separate ticket.

**What I would NOT do:**
Build a full OAuth2 system or integrate a third-party auth provider without being asked. That's scope creep for a read-only events endpoint. If Marlow needs that later, it's a new ticket with proper requirements.

**My recommendation:** Start with API keys. It solves the immediate security gap without over-engineering. If they need more later, we iterate.

---

## Read me your STATUS_REPORT for Fenwick like you're on the phone with their owner right now.

"Hi, quick update on the order sync webhook.

The good news: it's built and tested. When your payment provider sends a successful payment notification, our system automatically creates the order. It verifies the notification actually came from your provider — not just anyone who finds the URL — and handles retries gracefully. If the provider sends the same payment twice, we won't create duplicate orders.

What you need to do on your end: log into your payment provider's dashboard and set up the webhook. I've provided the URL path, and you'll need to enter the webhook secret we've configured. That's about a five-minute task.

One thing I need from you: can you confirm your payment provider uses a standard webhook format? I've built it to handle Stripe-style payloads, which covers most providers, but I want to make sure we're aligned before you go live.

Otherwise, this is ready to test whenever you are."

---

## Good Questions to Ask (Signals)

- **About Ticket C's missing auth:** "For Marlow, I flagged that the endpoint has no authentication. Should we discuss what auth approach makes sense for their use case, or is the open endpoint intentional for now?"
- **About Ticket B's data recovery:** "For Northgate, some dates might be corrupted beyond programmatic recovery. What's their process for handling records that can't be automatically fixed — manual review, or is there a source of truth they can reconcile against?"
- **About scaling Ticket A:** "If Fenwick's volume grows significantly, the SQLite database would be the first bottleneck. At what point should we plan a migration to PostgreSQL — is that something they're tracking?"
