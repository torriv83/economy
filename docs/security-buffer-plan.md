# Security Buffer & SelfLoan Integration

## Fremdrift

### Fase 1: SelfLoan-siden komplett ✅
**Del A: Buffer-visning** ✅
- [x] `YnabService::fetchSavingsAccounts()` - Hent savings-kontoer
- [x] `YnabService::fetchAssignedNextMonth()` - Hent assigned neste måned
- [x] `YnabService::fetchNeedCategories()` - Hent NEED-kategorier
- [x] `Overview::getBufferStatusProperty()` - Buffer-beregning
- [x] Buffer-kort i `overview.blade.php`
- [x] Oversettelser i `no/app.php`
- [x] Tester: `OverviewBufferTest.php`

**Del B: SelfLoan YNAB-kobling** ✅
- [x] Migrasjon: `add_ynab_fields_to_self_loans_table`
- [x] Model: Legg til fillable fields
- [x] `CreateSelfLoan.php` - YNAB dropdown
- [x] `create-self-loan.blade.php` - UI for kobling
- [x] Vis kobling i Overview-listen
- [x] Tester: `CreateSelfLoanYnabTest.php`

### Fase 2: Anbefalinger og beslutningsstøtte ✅
**Anbefalingslogikk** ✅
- [x] `BufferRecommendationService` - Smart anbefalingslogikk
- [x] Lag 1 anbefalinger (operasjonell buffer)
- [x] Lag 2 & Gjeld dynamisk avveining
- [x] Høy rente (≥15%): Prioriter gjeldsnedbetalning
- [x] Lav rente (<5%): Prioriter bufferbygging
- [x] Balansert scenario (5-15%): Info om begge alternativer
- [x] Tester: `BufferRecommendationServiceTest.php` (16 tester)

**Integrasjon med DebtCalculationService** ✅
- [x] `calculateDebtImpact()` - Beregn rentesparing og måneder spart
- [x] Sammenligning av gjeldsnedbetalingsscenarioer

**Scenario-sammenlikning UI** ✅
- [x] `Overview::getRecommendationsProperty()` - Computed property
- [x] `Overview::getScenarioComparisonProperty()` - Computed property
- [x] `Overview::toggleScenarioComparison()` - Toggle-handling
- [x] Anbefalingskort i `overview.blade.php`
- [x] Scenario-sammenligning panel med konfigurerbart beløp
- [x] Oversettelser i `en/app.php` og `no/app.php`

### Fase 3-6: Fremtidige faser
- [ ] Fase 3: Buffer på andre sider
- [ ] Fase 4: Brukerinnstillinger
- [ ] Fase 5: SecurityBufferService
- [ ] Fase 6: Historisk forbruksanalyse

---

## Oversikt
Koble SelfLoan (lån fra øremerkede midler) med sikkerhetsbuffer-beregning basert på YNAB-data. Systemet skal gi automatiske anbefalinger om hva brukeren BØR gjøre med pengene sine.

---

## Forskning: Norske anbefalinger for buffer

Basert på [DNB](https://www.dnb.no/dnbnyheter/no/din-okonomi/her-er-summene-du-bor-ha-i-buffer), [NRK](https://www.nrk.no/rogaland/okonomenes-beste-rad-til-sparing-i-bufferkonto-1.16235540), og [Handelsbanken](https://www.handelsbanken.no/no/privat/spare/begynn-a-spare/spar-til-buffer):

- **Anbefalt buffer:** 1-3 månedslønninger ELLER 2-3 måneders faste utgifter
- **Minimum:** 20 000 - 40 000 kr
- **Høyere hvis:** Du eier bolig, bil, hytte eller har variabel inntekt
- **Standard innstilling:** 2 måneder (midt i 1-3 intervallet)

---

## Krav (fra diskusjon)

| Spørsmål | Svar |
|----------|------|
| **Formål** | Automatiske anbefalinger: "Du bør fokusere på buffer", "Overfør X til gjeld for å spare Y kr" |
| **Buffer-kilder** | 1) Savings-kontoer fra YNAB, 2) Penger assigned til neste måned, 3) Valgfrie spare-kategorier |
| **Essensielle utgifter** | Sum av NEED-kategorier (budgeted amounts) |
| **SelfLoan-kobling** | Valgfritt knytte til YNAB-konto/kategori for å vite om det påvirker buffer |
| **Buffer-terskel** | Bruker-konfigurerbart, standard 2 måneder |
| **UI-plassering** | INGEN separat dashboard - integrer i SelfLoan-siden |
| **Uten YNAB** | Funksjoner ikke tilgjengelig (lav prioritet - eneste bruker har YNAB) |

---

## Kjernekonsept: Hvordan buffer beregnes

### Formål
Gi automatiske anbefalinger om:
- **Bygge buffer** - "Du bør fokusere på bufferen denne måneden"
- **Betale gjeld** - "Overfør X fra buffer til lån Y, sparer deg Z kr"
- **Tilbakebetale SelfLoan** - "Du bør tilbakebetale det du lånte fra sparekontoen"

### To-lags buffer-modell

```
┌─────────────────────────────────────────────────────┐
│  Lag 1: Operasjonell buffer                         │
│  "Assigned neste måned" i YNAB                      │
│  Formål: Leve på forrige måneds lønn                │
│  Mål: 1 full måned (100% av neste måned tildelt)    │
├─────────────────────────────────────────────────────┤
│  Lag 2: Nødbuffer                                   │
│  Sparekontoer + valgfrie spare-kategorier           │
│  Formål: Ekte sikkerhetsnett for uforutsette ting   │
│  Mål: 1-2 måneder (bruker-konfigurerbart)           │
└─────────────────────────────────────────────────────┘
```

**Total buffer:**
```
Total buffer = Lag 1 (assigned neste måned)
             + Lag 2 (sparekontoer + spare-kategorier)
```

**Viktig:** YNAB-saldoer reflekterer allerede virkeligheten. Ingen manuell fratrekking trengs.

### SelfLoan og buffer

**SelfLoan kan valgfritt knyttes til YNAB-konto eller kategori.**

Koblingen brukes til å:
1. Vite om SelfLoan påvirker buffer-beregningen
2. Synce saldo/status fra YNAB
3. Gi smartere anbefalinger

**Eksempler:**
- SelfLoan knyttet til **sparekonto** → påvirker buffer (YNAB viser redusert saldo)
- SelfLoan knyttet til **Idrett-konto** → påvirker IKKE buffer (Idrett er ikke buffer-kilde)
- SelfLoan **uten kobling** → bare manuell tracking, påvirker ikke buffer

### Månedlige utgifter
For å beregne "måneder sikkerhet":

**Fase 1: Budgeted amounts**
- Sum av NEED-kategorier (budgeted)
- Stabilt og intensjonelt

**Fremtidig: Historisk gjennomsnitt**
- Gjennomsnitt av siste 3-12 måneder faktisk forbruk
- Krever historiske API-kall

### Formlene
```
Lag 1 (operasjonell) = assigned_neste_måned
Lag 2 (nødbuffer)    = savings_kontoer + spare_kategorier

Total buffer         = Lag 1 + Lag 2
Måneder sikkerhet    = total_buffer / månedlige_essensielle_utgifter

Lag 1 status         = assigned_neste_måned / månedlige_essensielle_utgifter
                       (mål: >= 1.0 = "en måned foran")
```

---

## Arkitektur

### Nye tjenester (fremtidige faser)

1. **SecurityBufferService** (`app/Services/SecurityBufferService.php`)
   - Beregner buffer-status
   - Henter savings-kontoer + assigned neste måned fra YNAB
   - Genererer anbefalinger

2. **SpendingAnalysisService** (Fase 6)
   - Historisk forbruksanalyse
   - Ikke nødvendig for Fase 1

### Dataflyt
```
YNAB API
    ↓
┌───┴───────────────────────────────┐
│                                   │
↓                                   ↓
Savings-kontoer              Kategorier
+ Assigned neste måned       (NEED goal_type)
+ Spare-kategorier                  │
         │                          ↓
         ↓                   Månedlige utgifter
    Total buffer                    │
         │                          │
         └──────────┬───────────────┘
                    ↓
           Måneder sikkerhet
                    ↓
        ┌───────────┴───────────┐
        ↓                       ↓
   Buffer-status          Anbefalinger
   (kritisk/ok/god)       "Du bør..."
```

---

## Implementeringsfaser

### Fase 1: SelfLoan-siden komplett (START HER)

**Mål:** Utvid SelfLoan-siden med buffer-visning og YNAB-kobling. Alt som gjelder SelfLoan-siden samles i én fase.

**Innhold:**
1. Buffer-visning (Lag 1 + Lag 2)
2. SelfLoan YNAB-kobling (konto/kategori)
3. Komplett og fungerende når fasen er ferdig

---

#### Del A: Buffer-visning

**Tilnærming:**
1. Legg til "Sikkerhetsbuffer"-kort på SelfLoan Overview
2. Hent savings-kontoer fra YNAB
3. Hent assigned neste måned fra YNAB
4. Beregn måneder sikkerhet

**Filer å endre:**
- `app/Services/YnabService.php` - Legg til `fetchSavingsAccounts()` og `fetchAssignedNextMonth()`
- `app/Livewire/SelfLoans/Overview.php` - Legg til buffer-beregning
- `resources/views/livewire/self-loans/overview.blade.php` - Legg til buffer-kort
- `resources/lang/nb/app.php` - Oversettelser

**UI:**
```
┌─────────────────────────────────────────────────────┐
│  Sikkerhetsbuffer                                   │
│                                                     │
│  Lag 1: Operasjonell buffer                         │
│  Assigned neste måned:        18 000 kr            │
│  [████████████████████] 100% - En måned foran ✓    │
│                                                     │
│  Lag 2: Nødbuffer                                   │
│  Sparekontoer:                27 000 kr            │
│  [██████████████░░░░░░] 1.5 mnd av 2 mnd mål       │
│                                                     │
│  ─────────────────────────────────────────────     │
│  Total buffer:                45 000 kr            │
│  ≈ 2.5 måneder sikkerhet                           │
└─────────────────────────────────────────────────────┘
```

**Logikk:**
```php
public function getBufferStatusProperty(): ?array
{
    if (!$this->settingsService->isYnabConfigured()) {
        return null;
    }

    $savingsAccounts = $this->ynabService->fetchSavingsAccounts();
    $assignedNextMonth = $this->ynabService->fetchAssignedNextMonth();
    $needCategories = $this->ynabService->fetchNeedCategories();

    $monthlyEssential = $needCategories->sum('budgeted');
    $savingsTotal = $savingsAccounts->sum('balance');

    // Lag 1: Operasjonell buffer (en måned foran)
    $layer1Amount = $assignedNextMonth;
    $layer1Percentage = $monthlyEssential > 0
        ? min(100, ($layer1Amount / $monthlyEssential) * 100)
        : 0;
    $isMonthAhead = $layer1Percentage >= 100;

    // Lag 2: Nødbuffer (sparekontoer)
    $layer2Amount = $savingsTotal;
    $recommendedEmergencyMonths = 2; // Bruker-konfigurerbart senere
    $layer2Target = $monthlyEssential * $recommendedEmergencyMonths;
    $layer2Months = $monthlyEssential > 0 ? $layer2Amount / $monthlyEssential : 0;

    // Total
    $totalBuffer = $layer1Amount + $layer2Amount;
    $totalMonths = $monthlyEssential > 0 ? $totalBuffer / $monthlyEssential : 0;

    return [
        'layer1' => [
            'amount' => $layer1Amount,
            'percentage' => round($layer1Percentage, 0),
            'is_month_ahead' => $isMonthAhead,
        ],
        'layer2' => [
            'amount' => $layer2Amount,
            'months' => round($layer2Months, 1),
            'target_months' => $recommendedEmergencyMonths,
        ],
        'total_buffer' => $totalBuffer,
        'monthly_essential' => $monthlyEssential,
        'months_of_security' => round($totalMonths, 1),
        'status' => $this->getBufferStatus($totalMonths),
    ];
}
```

**Status-farger (basert på total buffer):**
- `critical` (< 1 måned): Rød
- `warning` (1-2 måneder): Gul/Amber
- `healthy` (2+ måneder): Grønn

---

#### Del B: SelfLoan YNAB-kobling

**Mål:** La brukeren knytte SelfLoan til YNAB-konto eller kategori.

**Endringer i SelfLoan-modell:**
```php
// Ny migrasjon: add_ynab_fields_to_self_loans_table
$table->string('ynab_account_id')->nullable();
$table->string('ynab_category_id')->nullable();
```

**UI ved opprettelse/redigering av SelfLoan:**
```
Navn: [Lånte fra sparekonto        ]
Beløp: [5000] kr

Knytt til YNAB (valgfritt):
○ Ingen kobling
● Konto: [Sparekonto ▾]
○ Kategori: [Velg kategori ▾]

[Opprett lån]
```

**Funksjonalitet:**
- Dropdown med YNAB-kontoer og kategorier
- Valgfritt - kan være null
- Vises i Overview hvilken konto/kategori lånet er knyttet til
- Brukes til å forstå om lånet påvirker buffer

**Filer å endre:**
- `database/migrations/xxxx_add_ynab_fields_to_self_loans_table.php` - Ny migrasjon
- `app/Models/SelfLoan/SelfLoan.php` - Legg til fillable fields
- `app/Livewire/SelfLoans/CreateSelfLoan.php` - YNAB dropdown
- `resources/views/livewire/self-loans/create.blade.php` - UI for kobling
- `app/Livewire/SelfLoans/Overview.php` - Vis kobling i liste

---

### Fase 2: Anbefalinger og beslutningsstøtte (krever gjeldsdelen)

**Mål:** Gi smarte anbefalinger basert på faktisk matematikk, ikke rigide regler.

**Prinsipp:** Ingen "harde regler" - systemet veier alltid buffer-sikkerhet mot rentekostnader og gir den anbefalingen som faktisk er best økonomisk.

#### Lag 1: Operasjonell buffer (en måned foran)

```
Ikke en måned foran ennå:
  → "Overfør X kr fra sparekonto til brukskonto"
  → "Da kan du tildele hele neste måned"
  → "Du har fortsatt Y kr i nødbuffer (Lag 2)"

En måned foran:
  → "Du er en måned foran - bra!"
  → (Fortsett til Lag 2 eller gjeld-anbefalinger)
```

#### Lag 2: Nødbuffer + Gjeld - Dynamisk avveining

**Faktorer som veies:**
1. **Gjeldsrente** - Høy rente = mer lønnsomt å betale ned
2. **Buffer-nivå** - Kritisk lav = høyere risiko
3. **Rentekostnad vs sikkerhet** - Konkret kr-beløp sammenlikning

**Smart anbefalingslogikk:**
```
Scenario A: Høy gjeldsrente (f.eks. 20%+ kredittkort)
  → "Selv med lav buffer, spar X kr/år ved å betale ned gjeld"
  → "Anbefaling: Betal ned kredittkort, aksepter midlertidig lav buffer"
  → Viser: rentekostnad vs buffer-risiko

Scenario B: Lav gjeldsrente (f.eks. 5% billån)
  → "Buffer gir mer trygghet enn rentesparingen"
  → "Anbefaling: Bygg buffer først, gjelden koster lite"

Scenario C: God buffer + gjeld
  → "Buffer er solid - ekstra mot gjeld sparer deg X kr"
  → "Overfør Y kr til lån Z - nedbetalt N måneder tidligere"

Scenario D: Balansert situasjon
  → "Fordel ekstra penger: 50% buffer, 50% gjeld"
  → "Alternativer:" (viser begge scenarioer med tall)
```

#### Scenario-sammenlikning (alltid tilgjengelig)

Viser konkrete tall for hver mulighet:
```
┌─────────────────────────────────────────────────────┐
│  Hva bør jeg gjøre med 5 000 kr ekstra?            │
├─────────────────────────────────────────────────────┤
│  A) Til buffer:                                     │
│     → +5 dager ekstra sikkerhet                    │
│     → Buffer: 1.2 mnd → 1.4 mnd                    │
│                                                     │
│  B) Til gjeld (Kredittkort 22%):                   │
│     → Sparer 1 100 kr i renter                     │
│     → Nedbetalt 2 måneder tidligere                │
│                                                     │
│  C) Til gjeld (Billån 5%):                         │
│     → Sparer 250 kr i renter                       │
│     → Nedbetalt 1 måned tidligere                  │
│                                                     │
│  ★ Anbefaling: B - Høy rente gir best avkastning   │
└─────────────────────────────────────────────────────┘
```

#### Integrasjon med AccelerationService

- Bruker eksisterende beregninger for gjeldsimpact
- Kombinerer med buffer-status for helhetlig anbefaling
- Viser: "Med X kr ekstra til gjeld Y, sparer du Z kr og er gjeldfri N måneder tidligere"

---

### Fase 3: Buffer-status på andre sider

**Mål:** Vis buffer-status der beslutninger tas.

**Mulige plasseringer:**
- Dashboard-oversikt
- Gjeldsnedbetalingsplan
- AccelerationOpportunities-komponenten

---

### Fase 4: Brukerinnstillinger for buffer

**Mål:** La bruker konfigurere buffer-preferanser.

**Innstillinger:**
- `buffer.recommended_months` (standard: 2)
- `buffer.include_assigned_next_month` (standard: true)
- `buffer.extra_savings_categories` - Valgfrie spare-kategorier utover savings-kontoer

**Filer:**
- `app/Services/SettingsService.php`
- Innstillinger-side

---

### Fase 5: SecurityBufferService (Full)

**Mål:** Trekk ut buffer-logikk til dedikert service for gjenbruk.

**Ny fil:** `app/Services/SecurityBufferService.php`

```php
class SecurityBufferService
{
    public function __construct(
        private YnabService $ynabService,
        private SettingsService $settings,
    ) {}

    public function getBufferStatus(): array
    {
        // Full implementasjon med alle funksjoner
    }

    public function getRecommendations(): array
    {
        // Genererer anbefalinger basert på status
    }

    public function compareScenarios(float $amount): array
    {
        // Sammenlikner: buffer vs gjeld vs SelfLoan
    }
}
```

---

### Fase 6: Historisk forbruksanalyse (fremtidig)

**Mål:** Mer nøyaktig buffer-beregning basert på faktisk forbrukshistorikk.

**Ny fil:** `app/Services/SpendingAnalysisService.php`

**YNAB API-utvidelse:**
```php
// I YnabService.php
public function fetchMonthlyHistory(int $months = 6): Collection
{
    // GET /budgets/{budget_id}/months
    // Returnerer historisk kategoridata
}
```

---

## Detaljert Fase 1-implementeringsplan

### Steg 1: Utvid YnabService

**Fil:** `app/Services/YnabService.php`

Nye metoder:
```php
/**
 * Henter alle savings-kontoer fra YNAB.
 * @return Collection<int, array{id: string, name: string, balance: float}>
 */
public function fetchSavingsAccounts(): Collection
{
    // GET /budgets/{budget_id}/accounts
    // Filtrer på type === 'savings'
}

/**
 * Henter beløp assigned til neste måned.
 * @return float
 */
public function fetchAssignedNextMonth(): float
{
    // GET /budgets/{budget_id}/months/{next_month}
    // Returner total budgeted for neste måned
}

/**
 * Henter kategorier med goal_type === 'NEED'.
 * @return Collection<int, array{id: string, name: string, budgeted: float}>
 */
public function fetchNeedCategories(): Collection
{
    // Bruk eksisterende fetchCategories() og filtrer
}
```

### Steg 2: Legg til buffer-beregning i Overview

**Fil:** `app/Livewire/SelfLoans/Overview.php`

```php
use App\Services\YnabService;
use App\Services\SettingsService;

public function boot(YnabService $ynabService, SettingsService $settingsService): void
{
    $this->ynabService = $ynabService;
    $this->settingsService = $settingsService;
}

public function getBufferStatusProperty(): ?array
{
    if (!$this->settingsService->isYnabConfigured()) {
        return null;
    }

    try {
        $savingsAccounts = $this->ynabService->fetchSavingsAccounts();
        $assignedNextMonth = $this->ynabService->fetchAssignedNextMonth();
        $needCategories = $this->ynabService->fetchNeedCategories();

        $monthlyEssential = $needCategories->sum('budgeted');
        $savingsTotal = $savingsAccounts->sum('balance');

        // Lag 1: Operasjonell buffer (en måned foran)
        $layer1Amount = $assignedNextMonth;
        $layer1Percentage = $monthlyEssential > 0
            ? min(100, ($layer1Amount / $monthlyEssential) * 100)
            : 0;
        $isMonthAhead = $layer1Percentage >= 100;

        // Lag 2: Nødbuffer (sparekontoer)
        $layer2Amount = $savingsTotal;
        $recommendedEmergencyMonths = 2;
        $layer2Months = $monthlyEssential > 0 ? $layer2Amount / $monthlyEssential : 0;

        // Total
        $totalBuffer = $layer1Amount + $layer2Amount;
        $totalMonths = $monthlyEssential > 0 ? $totalBuffer / $monthlyEssential : 0;

        return [
            'layer1' => [
                'amount' => $layer1Amount,
                'percentage' => round($layer1Percentage, 0),
                'is_month_ahead' => $isMonthAhead,
            ],
            'layer2' => [
                'amount' => $layer2Amount,
                'months' => round($layer2Months, 1),
                'target_months' => $recommendedEmergencyMonths,
            ],
            'total_buffer' => $totalBuffer,
            'monthly_essential' => $monthlyEssential,
            'months_of_security' => round($totalMonths, 1),
            'status' => $this->getBufferStatus($totalMonths),
        ];
    } catch (\Exception $e) {
        return null; // Graceful fallback
    }
}

private function getBufferStatus(float $months): string
{
    if ($months < 1) return 'critical';
    if ($months < 2) return 'warning';
    return 'healthy';
}
```

### Steg 3: Legg til UI

**Fil:** `resources/views/livewire/self-loans/overview.blade.php`

Plassering: Nytt kort over SelfLoan-listen.

Vis kun hvis `$this->bufferStatus !== null`.

### Steg 4: Oversettelser

```php
// resources/lang/nb/app.php
'security_buffer' => 'Sikkerhetsbuffer',
'layer1_operational_buffer' => 'Operasjonell buffer',
'layer2_emergency_buffer' => 'Nødbuffer',
'savings_accounts' => 'Sparekontoer',
'assigned_next_month' => 'Satt av neste måned',
'total_buffer' => 'Total buffer',
'month_ahead' => 'En måned foran',
'months_of_security' => ':count måneder sikkerhet',
'recommended_buffer' => 'Anbefalt: :count mnd',
'of_target' => ':months mnd av :target mnd mål',
'buffer_status_critical' => 'Kritisk lav',
'buffer_status_warning' => 'Under anbefalt',
'buffer_status_healthy' => 'God sikkerhet',
```

### Steg 5: Tester

```php
// tests/Feature/Livewire/SelfLoans/OverviewBufferTest.php
it('viser buffer-status når YNAB er konfigurert', function () {...});
it('skjuler buffer-status når YNAB ikke er konfigurert', function () {...});
it('beregner lag 1 (operasjonell) korrekt', function () {...});
it('viser "en måned foran" når lag 1 >= 100%', function () {...});
it('beregner lag 2 (nødbuffer) i måneder korrekt', function () {...});
it('beregner total buffer korrekt (lag 1 + lag 2)', function () {...});
it('viser kritisk status når total buffer < 1 måned', function () {...});
it('viser advarsel når total buffer er 1-2 måneder', function () {...});
it('viser god status når total buffer > 2 måneder', function () {...});
```

---

## Filoversikt

### Fase 1: Filer å endre (SelfLoan-siden komplett)

**Del A: Buffer-visning**
| Fil | Endringer |
|-----|-----------|
| `app/Services/YnabService.php` | Nye metoder: `fetchSavingsAccounts()`, `fetchAssignedNextMonth()`, `fetchNeedCategories()` |
| `app/Livewire/SelfLoans/Overview.php` | Buffer-beregning, YNAB DI |
| `resources/views/livewire/self-loans/overview.blade.php` | Buffer-kort |
| `resources/lang/nb/app.php` | Oversettelser |
| `tests/Feature/Livewire/SelfLoans/OverviewBufferTest.php` | Ny testfil |

**Del B: SelfLoan YNAB-kobling**
| Fil | Endringer |
|-----|-----------|
| `database/migrations/xxxx_add_ynab_fields_to_self_loans_table.php` | Ny migrasjon |
| `app/Models/SelfLoan/SelfLoan.php` | Nye fillable fields |
| `app/Livewire/SelfLoans/CreateSelfLoan.php` | YNAB dropdown |
| `resources/views/livewire/self-loans/create.blade.php` | UI for kobling |
| `app/Livewire/SelfLoans/Overview.php` | Vis YNAB-kobling i listen |
| `resources/views/livewire/self-loans/overview.blade.php` | UI for kobling-visning |
| `tests/Feature/Livewire/SelfLoans/CreateSelfLoanYnabTest.php` | Tester for kobling |

### Fremtidige faser
| Fase | Innhold |
|------|---------|
| Fase 2 | Anbefalinger og beslutningsstøtte (krever gjeldsdelen) |
| Fase 3 | Buffer-visning på dashboard og andre sider |
| Fase 4 | Brukerinnstillinger for buffer |
| Fase 5 | `SecurityBufferService.php` |
| Fase 6 | Historisk forbruksanalyse |

---

## Åpne spørsmål for fremtidige faser

1. **Spare-kategorier i tillegg til kontoer:**
   - Fase 1: Kun savings-kontoer + assigned neste måned
   - Fase 4: La bruker velge ekstra spare-kategorier i innstillinger

2. **Anbefalingslogikk:**
   - Fase 2: Implementer "Du bør..."-anbefalinger
   - Integrer med AccelerationService for gjeldsnedbetalingsberegninger

---

## Klar for implementering

**Fase 1 er klar.** Den er selvforsynt og gir verdi uten full arkitektur.

**Fase 1 inneholder:**
- Del A: Buffer-visning på SelfLoan-siden
- Del B: SelfLoan YNAB-kobling

**Fasene i rekkefølge:**
1. SelfLoan-siden komplett (buffer + YNAB-kobling)
2. Anbefalinger og beslutningsstøtte (krever gjeldsdelen)
3. Buffer på andre sider
4. Brukerinnstillinger
5. SecurityBufferService
6. Historisk forbruksanalyse
