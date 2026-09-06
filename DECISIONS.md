# Technical Decisions

## Ticket A - Order Sync Webhook

### 1. What did the spec not tell you, and what did you assume?

**Assumptions:**
- Payment provider uses Stripe-style webhook format (as suggested)
- Signature verification uses HMAC-SHA256 (industry standard)
- Webhook payload includes payment ID for idempotency
- Failed processing should be logged to database (visible to humans)

**Least confident assumption:** 
I assumed the payment provider's webhook format matches Stripe's structure. In reality, I'd need to verify the actual payload format with Fenwick's team or check provider documentation. The `data.object` structure could be different.

### 2. What did you use AI for, and where did you override it?

**AI generated:**
- Initial webhook structure and signature verification logic
- Basic idempotency check

**Overrode:**
- Added comprehensive audit logging (AI missed this requirement)
- Implemented multiple signature format handling (AI assumed single format)
- Added retry-safe idempotency with database transactions

### 3. What would break first if Fenwick's order volume grew 100x?

**First to break:** The SQLite database would hit performance issues.

**How to know before customers complain:**
- Monitor database query times
- Track webhook processing latency
- Set up alerts for slow queries
- Plan migration to PostgreSQL/MySQL

**Solution:** Migrate to PostgreSQL with proper indexing on payment_id.

### 4. What did you deliberately not build?

- **Authentication system for Ticket C:** Spec said "no auth system exists" and to flag this concern, not build one
- **Complex retry logic:** Provider handles retries; we just need idempotency
- **Real-time notifications:** Out of scope for this assignment

---

## Ticket B - Northgate Date Parser

### 1. What did the spec not tell you?

**Assumptions:**
- The corrupted dates can be recovered by re-parsing
- Multiple date formats need to be supported
- Regional offices use different formats (UK, US, German)

**Least confident assumption:**
I assumed the corrupted dates were just format mismatches, not data corruption at the byte level. Some dates might be truly unrecoverable.

### 2. What did you use AI for?

**AI generated:**
- List of common date formats
- Basic parsing logic

**Overrode:**
- Added format detection for audit purposes
- Built comprehensive logging for compliance
- Added validation to prevent false positives

### 3. What would break first?

**First to break:** If new regional offices use date formats not in the parser.

**How to know:** Monitor parsing failures in production logs.

---

## Ticket C - Marlow Events API

### 1. What did the spec not tell you?

**Assumptions:**
- Events have a simple schema (title, date, location)
- "Upcoming" means future dates only
- 10 events is a reasonable default limit

**Least confident assumption:**
I assumed no pagination is needed. In production, Marlow might want to fetch all events or use cursor-based pagination.

### 2. What did you use AI for?

**AI generated:**
- Basic endpoint structure
- Simple query logic

**Overrode:**
- Added proper error handling
- Documented missing auth concern
- Added count in response for client convenience

### 3. What would break first?

**First to break:** The missing authentication. Any unauthorized user can access all upcoming events.

**How to know:** Security audit or data breach.

---

## Cross-Cutting Decisions

### Database Choice
- **Decision:** SQLite for simplicity
- **Reason:** No external dependencies, easy setup for assignment
- **Production:** Would use PostgreSQL

### Testing Strategy
- **Decision:** Unit tests for services, integration tests for database operations
- **Reason:** Different risk levels require different test coverage
- **Ticket A:** Most tests (payment processing is critical)
- **Ticket B:** Parser tests (data integrity is important)
- **Ticket C:** Basic tests (read-only, low risk)

### Error Handling
- **Decision:** Graceful degradation with logging
- **Reason:** Production systems need visibility into failures
- **Implementation:** All errors logged to webhook_logs table
