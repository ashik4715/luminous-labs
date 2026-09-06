# Senior PHP Developer — Take-Home Assignment

## Setup (5 minutes)

### Prerequisites
- PHP 8.1+
- Composer
- SQLite (bundled with PHP)

### Install

```bash
composer install
cp .env.example .env
```

### Run Tests

```bash
composer test
```

### Run Locally

```bash
php -S localhost:8000 -t public
```

**Endpoints:**
- `POST /webhooks/payment-provider` — Payment webhook (Ticket A)
- `GET /api/events/upcoming` — Upcoming events (Ticket C)

### Test the Webhook

```bash
# Generate test signature
PAYLOAD='{"type":"payment.succeeded","data":{"object":{"id":"pay_123","amount":5000,"currency":"usd","billing_details":{"email":"test@example.com"}}}}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "whsec_test12345678901234567890" | awk '{print $2}')

curl -X POST http://localhost:8000/webhooks/payment-provider \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: sha256=$SIGNATURE" \
  -d "$PAYLOAD"
```

### Run Date Parser Fix (Ticket B)

```bash
php scripts/fix-northgate-dates.php
```

## Project Structure

```
├── src/
│   ├── Controllers/    # HTTP handlers
│   ├── Database/       # SQLite connection & migrations
│   ├── Models/         # Order, Event, WebhookLog
│   ├── Services/       # Business logic
│   └── Router.php      # Simple router
├── tests/
│   ├── Unit/           # Service tests
│   └── Integration/    # Database + service tests
├── scripts/            # Northgate date fix
├── public/             # Entry point
├── PROGRESS.md         # Work log
├── DECISIONS.md        # Technical decisions
└── STATUS_REPORT.md    # Client communication drafts
```

## What Was Built

| Ticket | Description | Status |
|--------|-------------|--------|
| A | Payment webhook with signature verification + idempotency | Complete |
| B | Multi-format date parser + corrupted data remediation | Complete |
| C | Read-only upcoming events API | Complete |
