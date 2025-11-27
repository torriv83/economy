FEATURE IDEAS 💡

Quick wins (high value, relatively simple):
- What-if scenarios: Compare 3-5 different extra payment amounts side-by-side
- Snowflake tracking: Log one-time windfalls (tax refunds, bonuses) and see immediate impact

High-value additions:
- Budget tracking: Set monthly debt budget and track adherence

Advanced features:
- Keyboard shortcuts: Alt+H (home), Alt+S (strategies), Alt+P (plan)

Long-term:
- PWA conversion for offline access + home screen install
- AI recommendations based on payment patterns


## The user wishlist: ##
- Be able to click on one debt, and get more details on that single debt (needs brainstorming)
  - first idea: have a what/if.. so i put in a sum i want to pay extra to get an idea how much faster the debt is paid off
- Integrate YNAB so i can add any wishlist or things i need to save for (like an upcoming dental appointment next year), and get help to know how much i can afford to put aside and still keep debt payments on track.


## YNAB Integration Ideas

### Current integration
- Syncs debt accounts from YNAB (balance, interest rate, minimum payment)

### New features from YNAB API

#### 1. Transaction History (High value, Low complexity) !!IMPLEMENTED!!
`GET /budgets/{budget_id}/accounts/{account_id}/transactions`
- Auto-import actual payments to debts instead of manual entry
- View payment history from YNAB directly
- Verify that recorded payments match YNAB
- Can fetch transactions for a specific month 

#### 2. Category Budgets with Goals (High value, Medium complexity)
`GET /budgets/{budget_id}/categories`
- See how much you've budgeted for debt payments
- `goal_target` - target amount for the category
- `budgeted` - what you've set aside this month
- `balance` - available in the category
- `goal_needs_whole_amount` - rollover behavior for NEED-type goals 
- Goal types: TB (Target Balance), TBD (Target Balance by Date), MF (Monthly Funding), NEED (Plan Your Spending)
- Perfect for: Showing "available for extra debt payment"

#### 3. Scheduled Transactions (Medium value, Low complexity)
`GET /budgets/{budget_id}/scheduled_transactions`
- Fetch upcoming debt payments automatically
- Display in payment calendar
- Predict cash flow
- Can now create/update/delete via API 

#### 4. "Ready to Assign" Balance (Medium value, Low complexity)
From budget endpoint - shows how much money is available to budget:
- Show "You have X kr available for extra debt payment"
- Help balance savings vs. debt payment

#### 5. Monthly Budget Data (Medium value, Medium complexity)
`GET /budgets/{budget_id}/months/{month}`
- Compare planned vs. actual over time
- Build historical graphs of debt payments

### Concrete use case: Balancing savings goals and debt
Solution for the wishlist item about dental appointments etc:
1. Fetch categories with savings goals (dentist, wishlist, etc.)
2. Fetch "Ready to Assign" + category balances
3. Calculate: `Available for extra debt payment = Ready to Assign - (Savings goals missing funding)`
4. Display: "You can pay X kr extra on debt and still reach your savings goals"

### Recommended priority

| Feature | Value | Complexity |
|---------|-------|------------|
| 1. Transactions → auto-import payments | High | Low |
| 2. Category goals → balance savings/debt | High | Medium |
| 3. Scheduled transactions → calendar | Medium | Low |
| 4. Ready to Assign → available extra | Medium | Low |


Good question. Here are things that make web apps feel more "native":
Quick Wins
Feature	Description
Keyboard shortcuts	Already on your list - Alt+H, Alt+S, Alt+P etc.
Toast notifications	Slide-in confirmations instead of page refreshes ("Debt saved!")
Loading states	Subtle spinners/skeleton screens during actions
Page transitions	Smooth fade/slide between views instead of hard refreshes
Medium Effort
Feature	Description
Command palette	Ctrl+K to search/navigate anywhere (very "app-like")
Drag & drop	Reorder debts by dragging (pairs well with custom ordering feature)
Undo support	"Debt deleted" toast with "Undo" button
Persistent settings	Remember sidebar state, dark/light mode, last viewed page
Polish
Feature	Description
Empty states	Friendly illustrations when no debts exist
Focus management	Proper tab order, visible focus rings
Confirmation modals	Native-feeling dialogs instead of browser confirm()
Favicon badge	Show count or status in the browser tab icon
The keyboard shortcuts and toast notifications would probably give you the biggest "feels like an app" improvement for the least effort. Livewire makes both pretty easy. Any of these catch your interest?