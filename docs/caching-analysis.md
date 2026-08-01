# Caching Opportunities Analysis Report
## Personal Debt Management Application

**Date:** 2025-11-26
**Last updated:** 2026-08-01 (version-based invalidation)
**Status:** Implementation complete (all medium/high priority items done)

---

## Implementation Status

| # | Opportunity | Status |
|---|-------------|--------|
| 1 | Payment Schedule Generation | ✅ **COMPLETED** (2025-11-27) |
| 2 | Strategy Comparison | ✅ **COMPLETED** (2025-11-27) |
| 3 | PayoffSettings Queries | ✅ **COMPLETED** (2025-11-27) |
| 4 | Debt Collection Queries | ✅ **COMPLETED** (2025-11-27) |
| 5 | Progress Chart Data | ✅ **COMPLETED** (2025-11-27) |
| 6 | YNAB Service Responses | ⏭️ **SKIPPED** |
| 7 | Minimum Payment Calculations | ✅ **COMPLETED** (2025-11-27) |

---

## Executive Summary

This analysis identified **12 distinct caching opportunities** across the Laravel application. The opportunities range from **HIGH** to **LOW** priority based on computation cost, frequency of access, and potential performance impact.

---

## HIGH PRIORITY OPPORTUNITIES

### 1. ✅ Payment Schedule Generation (DebtCalculationService::generatePaymentSchedule)

**STATUS:** ✅ **IMPLEMENTED** (2025-11-27)

**WHERE:** `app/Services/DebtCalculationService.php`
**WHAT:** Complex amortization calculations generating complete payment schedules (up to 600 months)

**IMPLEMENTATION:**
- Added `getPaymentScheduleCacheKey()` method
- Wrapped `generatePaymentSchedule()` with `Cache::remember()` (24 hour TTL)
- Moved calculation logic to `calculatePaymentSchedule()` protected method

**Cache key includes:**
- Cache version prefix (`v{version}:`) — see [Cache Invalidation Strategy](#cache-invalidation-strategy)
- Debt ID, balance, interest_rate, minimum_payment, custom_priority_order
- Payments hash (count + max updated_at)
- Extra payment amount
- Strategy name
- Current date (`Y-m-d`) — the schedule is built from `now()`, so a plan cached
  yesterday would otherwise be served today with every month shifted
- The `actualPaymentMonthOffset` is appended to the key by the caller

**PRIORITY:** **HIGH** - This single optimization could improve page load times by 50-80%.

---

### 2. ✅ Strategy Comparison Calculations (DebtCalculationService::compareStrategies)

**STATUS:** ✅ **IMPLEMENTED** (2025-11-27)

**WHERE:** `app/Services/DebtCalculationService.php`
**WHAT:** Generates payment schedules for **all three strategies** (Snowball, Avalanche, Custom)

**IMPLEMENTATION:**
- Added `getStrategyComparisonCacheKey()` method
- Added `clearAllCalculationCaches()` convenience method (now a single version bump)
- Wrapped `compareStrategies()` with `Cache::remember()` (24 hour TTL)
- Moved calculation logic to `calculateStrategyComparison()` protected method
- Added early return for empty debts (no caching needed)

**Cache key includes:**
- Cache version prefix (`v{version}:`)
- Debt ID, balance, interest_rate, minimum_payment, custom_priority_order
- Payments hash (count + max updated_at)
- Extra payment amount
- Current date (`Y-m-d`), for the same reason as the payment schedule

**PRIORITY:** **HIGH** - Triple computation eliminated with single cache.

---

### 3. ✅ PayoffSettings Database Queries (PayoffSettingsService)

**STATUS:** ✅ **IMPLEMENTED** (2025-11-27)

**WHERE:** `app/Services/PayoffSettingsService.php`
**WHAT:** Database query for PayoffSetting record

**IMPLEMENTATION:**
- Replaced instance-level `$cachedSettings` property with Laravel `Cache::remember()` (24 hour TTL)
- Added `CACHE_KEY` and `CACHE_TTL_HOURS` constants for maintainability
- Added `clearSettingsCache()` static method for manual cache invalidation
- Cache automatically cleared in `setExtraPayment()`, `setStrategy()`, and `saveSettings()`

**Cache key:** `payoff_settings` (simple key since single-user application)

**PRIORITY:** **HIGH** - Easy win, frequently accessed.

---

## MEDIUM PRIORITY OPPORTUNITIES

### 4. ✅ Debt Collection Queries (Multiple Components)

**STATUS:** ✅ **IMPLEMENTED** (2025-11-27)

**WHERE:** Multiple Livewire components
**WHAT:** `Debt::all()` or `Debt::with('payments')->get()` queries

**IMPLEMENTATION:**
- Created `app/Services/DebtCacheService.php` as centralized caching service
  (24 hour TTL on every entry, invalidated by version bump rather than expiry):
  - `getAll()` / `getAllWithPayments()` - all debts, optionally with payments
  - `getAllActive()` / `getAllActiveWithPayments()` - non-archived debts
  - `getAllArchived()` / `getAllArchivedWithPayments()` - paid-off debts
  - `clearCache()` static method for manual cache invalidation
- Invalidation is driven by `InvalidateDebtCacheSubscriber`, registered in
  `AppServiceProvider::boot()` via `Event::subscribe()`
- Updated components to use `DebtCacheService`:
  - `PaymentPlan.php` - 7 locations updated
  - `DebtList.php` - 5 locations updated
  - `DebtProgress.php` - 5 locations updated

**Cache keys** (all prefixed with `v{version}:`):
- `debts:all`, `debts:all_with_payments`
- `debts:active`, `debts:active_with_payments`
- `debts:archived`, `debts:archived_with_payments`

> `app/Observers/DebtObserver.php` still exists, but it only maintains the
> `paid_off_at` flag — it no longer takes part in cache invalidation.

**PRIORITY:** **MEDIUM** - Centralized caching now provides consistent behavior across all components.

---

### 5. ✅ Progress Chart Data Generation (DebtProgress::getProgressDataProperty)

**STATUS:** ✅ **IMPLEMENTED** (2025-11-27)

**WHERE:** `app/Livewire/DebtProgress.php`
**WHAT:** Generates monthly historical data points for chart visualization

**IMPLEMENTATION:**
- Cache logic extracted to `app/Services/ProgressCacheService.php` (24 hour TTL)
- Key derived from Payment and Debt max updated_at, plus the shared cache version
- Moved calculation logic to `ProgressChartService`
- Fixed N+1 queries by pre-calculating payments by debt and month
- Uses `DebtCacheService` for debt retrieval (eager loading)

**Cache key includes:**
- Cache version prefix (`v{version}:`)
- Environment name, so test and production caches never collide
- Payment max updated_at timestamp
- Debt max updated_at timestamp

**Cache invalidation:**
- Automatically via `InvalidateDebtCacheSubscriber` → `DebtCacheInvalidator` →
  a single version bump that covers this cache along with all the others

> `DebtProgress::getProgressDataCacheKey()` and `clearProgressDataCache()` remain
> as deprecated pass-throughs to `ProgressCacheService`.

**Tests:** `tests/Feature/DebtProgressCachingTest.php`

**PRIORITY:** **MEDIUM** - Expensive but only displayed on one page.

---

### 6. ~~YNAB Service Responses~~ - SKIPPED

**STATUS:** ⏭️ **SKIPPED** - Not beneficial since YNAB API is only called on manual user action (clicking "Check YNAB" buttons).

---

### 7. ✅ Minimum Payment Calculations (Multiple Places)

**STATUS:** ✅ **IMPLEMENTED** (2025-11-27)

**WHERE:** `app/Services/DebtCalculationService.php`
**WHAT:** Calculate payoff timeline and total interest with minimum payments only

**IMPLEMENTATION:**
- Added `getMinimumPaymentsCacheKey()` protected method for cache key generation
- Wrapped `calculateMinimumPaymentsOnly()` with `Cache::remember()` (24 hour TTL)
- Wrapped `calculateMinimumPaymentsInterest()` with `Cache::remember()` (24 hour TTL)
- Moved calculation logic to `performCalculateMinimumPaymentsOnly()` and `performCalculateMinimumPaymentsInterest()` protected methods

**Cache key includes:**
- Cache version prefix (`v{version}:`)
- Debt ID, balance, interest_rate, minimum_payment
- Type suffix ('months' or 'interest') to differentiate the two calculations
- **No date**, unlike the schedule keys: the result holds no calendar dates

**Cache invalidation:**
- Automatically via `InvalidateDebtCacheSubscriber` → version bump

**Tests:** `tests/Feature/MinimumPaymentCachingTest.php`

**PRIORITY:** **MEDIUM** - Lightweight but called frequently.

---

## LOW PRIORITY OPPORTUNITIES

### 8. Debt Ordering Methods (DebtCalculationService)

**WHERE:** `orderBySnowball()`, `orderByAvalanche()`, `orderByCustom()` (lines 21-48)
**WHAT:** Simple sorting operations
**WHY:**
- Very lightweight operations (just `sortBy()` or `sortByDesc()`)
- Called multiple times but execution cost is negligible
- Results change when debts change

**PRIORITY:** **LOW** - Not worth caching due to minimal computation cost.

---

### 9. Total Debt Calculation (DebtList)

**WHERE:** `app/Livewire/DebtList.php` (line 129)
**WHAT:** `Debt::sum('balance')`
**WHY:**
- Simple aggregation query
- Database can handle this efficiently
- Updates frequently as payments are made
- Caching would add complexity without significant benefit

**PRIORITY:** **LOW** - Database optimization better than cache here.

---

### 10. Payment Aggregations (DebtProgress)

**WHERE:** `getTotalPaidProperty()`, `getTotalInterestPaidProperty()`, etc.
**WHAT:** Simple aggregation queries on Payment model
**WHY:**
- Single aggregate queries (SUM, AVG)
- Database-level operations are fast
- Change frequently with new payments
- Cache invalidation complexity > performance gain

**PRIORITY:** **LOW** - Database handles these well.

---

### 11. Monthly Interest Calculation (DebtCalculationService::calculateMonthlyInterest)

**WHERE:** `app/Services/DebtCalculationService.php` (line 57-60)
**WHAT:** Simple mathematical formula
**WHY:**
- Trivial calculation: `balance * (rate / 100) / 12`
- Called thousands of times within payment schedule generation
- **Should NOT be cached individually** - would add overhead
- **Optimization happens at parent level** (cache the schedule, not individual calculations)

**PRIORITY:** **LOW** - Too granular; optimize at higher level.

---

### 12. Reconciliation Counts (DebtList)

**WHERE:** `app/Livewire/DebtList.php` (lines 664-671)
**WHAT:** Count reconciliation adjustments per debt
**WHY:**
- Uses efficient groupBy query with selectRaw
- Only displayed on debt list page
- Reconciliations are infrequent
- Single query handles all debts efficiently

**RECOMMENDED APPROACH:** Already well-optimized with aggregation query.

**PRIORITY:** **LOW** - Current implementation is efficient.

---

## CACHE INVALIDATION STRATEGY

**STATUS:** ✅ **IMPLEMENTED** (rewritten 2026-08-01)

Invalidation is **version-based**: every debt-related cache key carries a
`v{version}:` prefix, and invalidating means advancing a single counter. All
previously cached entries become unreachable at once, atomically, on every cache
driver.

### Why not pattern-based deletion

The previous implementation tried to delete keys by pattern
(`clearPaymentScheduleCache()` and friends, calling `KEYS payment_schedule:*`).
That approach was removed because it never worked and failed silently:

- It ran `Cache::getStore()->getRedis()`, which uses the **default** Redis
  connection rather than the cache connection — a different database entirely.
- It built the pattern as `config('cache.prefix').':'`, but Laravel's `RedisStore`
  writes the prefix verbatim, with no colon. The pattern matched nothing.
- It was guarded by `config('cache.default') === 'redis'`, so on any other driver
  it did nothing at all.
- `KEYS` is O(n) and blocks the Redis server, so it was the wrong tool regardless.

Stale calculation results could therefore survive for the full 24 hour TTL, which
is what produced inconsistent debt-free dates across pages after a reconciliation.

### Implementation

#### The version counter (`app/Cache/DebtCacheVersion.php`)

```php
DebtCacheVersion::key('payment_schedule:...')  // → 'v1754006400:payment_schedule:...'
DebtCacheVersion::bump()                        // → atomic Cache::increment()
```

Stored with `Cache::forever()` under `debt_cache_version`, and seeded from
`now()->timestamp` rather than `1` — so a version key that is evicted or lost can
never land back on a number that older cached entries were written under.

#### Deferred flush (`app/Cache/DebtCacheInvalidator.php`)

The flush is deferred until the surrounding database transaction **commits**.
Flushing mid-transaction let a concurrent request re-populate the cache from the
pre-commit database state, leaving stale data behind for the full cache lifetime.

```
InvalidateDebtCacheSubscriber  (eloquent.saved/deleted/restored/forceDeleted on Debt + Payment)
    └── DebtCacheInvalidator::flush()
            ├── DB::afterCommit(...)   → runs immediately when no transaction is active
            ├── DB::afterRollBack(...) → releases the pending guard, nothing was changed
            └── DebtCacheService::clearCache()
                    └── DebtCacheVersion::bump()   ← invalidates every layer at once:
                        debts:*, payment_schedule:*, strategy_comparison:*,
                        minimum_payments:*, progress_data*
```

A pending guard collapses many model events inside one transaction into a single
flush, and a re-entry guard protects against a flush that triggers further
Eloquent events.

#### PayoffSettings Cache

`PayoffSettingsService::clearSettingsCache()` is called automatically when:
- `setExtraPayment()` is called
- `setStrategy()` is called
- `saveSettings()` is called

### Events to Clear Cache

| Event | Caches Cleared |
|-------|----------------|
| Debt created/updated/deleted | All debt caches (one version bump, after commit) |
| Payment created/updated/deleted | All debt caches (one version bump, after commit) |
| PayoffSettings changed | `payoff_settings` only |

### Subscriber Registration

Registered in `AppServiceProvider::boot()`:
```php
Event::subscribe(InvalidateDebtCacheSubscriber::class);
```

`DebtObserver` is still registered there too, but it only keeps `paid_off_at`
consistent — it plays no part in cache invalidation.

**Tests:** `tests/Feature/DebtCacheInvalidationTest.php` covers deferred flushing,
rollback handling, version-based invalidation and date-sensitive keys.

---

## SUMMARY TABLE

| # | Opportunity | Location | Priority | Estimated Impact | Complexity |
|---|-------------|----------|----------|------------------|------------|
| 1 | Payment Schedule Generation | DebtCalculationService | **HIGH** | 50-80% page load improvement | Medium |
| 2 | Strategy Comparison | DebtCalculationService | **HIGH** | 3x reduction in calculations | Medium |
| 3 | PayoffSettings Queries | PayoffSettingsService | **HIGH** | Eliminate repeated DB calls | Low |
| 4 | Debt Collection Queries | Multiple Components | **MEDIUM** | 20-30% query reduction | Low |
| 5 | Progress Chart Data | DebtProgress | **MEDIUM** | Eliminate N+1 queries | Medium |
| 6 | ~~YNAB API Calls~~ | YnabService | ~~MEDIUM~~ | ⏭️ SKIPPED | N/A |
| 7 | Minimum Payment Calcs | DebtCalculationService | **MEDIUM** | ✅ COMPLETED | Low |
| 8 | Debt Ordering | DebtCalculationService | **LOW** | Negligible | N/A |
| 9 | Total Debt Sum | DebtList | **LOW** | Negligible | N/A |
| 10 | Payment Aggregations | DebtProgress | **LOW** | Negligible | N/A |
| 11 | Monthly Interest Formula | DebtCalculationService | **LOW** | Better optimized elsewhere | N/A |
| 12 | Reconciliation Counts | DebtList | **LOW** | Already optimized | N/A |

---

## RECOMMENDATIONS

### Immediate Actions (High Priority)

1. Cache `generatePaymentSchedule()` results - **biggest win**
2. Cache `compareStrategies()` output
3. Add Laravel cache to `PayoffSettingsService`

### Phase 2 (Medium Priority)

4. Implement request-level debt collection caching
5. Cache progress chart data
6. ~~Add caching to YNAB API calls~~ - SKIPPED (only called on manual user action)

### Configuration Recommendations

- Use **file** or **Redis** cache driver (not array for persistence)
- TTL is 24 hours across the board: entries are invalidated by version bump, so
  the TTL is only a backstop against unbounded growth, not the freshness
  mechanism
- Use the **version prefix** for invalidation, never key-pattern scanning —
  `KEYS` blocks Redis and is not portable across cache drivers
- Anything derived from `now()` must include the date in its cache key
- Monitor cache hit rates with Laravel Telescope (if installed)

### Notes

- This is a **single-user application**, so aggressive caching is safe
- No cache stampede concerns
- Concurrency still matters despite the single user: a page renders several
  Livewire components, each issuing its own request, so a flush that happens
  before commit can be undone by a sibling request
- Consider **eager loading** (`with()`) in addition to caching
