<script setup>
import { computed } from 'vue'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  pendingTimesheets: { type: Array, default: () => [] },
  missingTimesheets: { type: Array, default: () => [] },
  recentBatches: { type: Array, default: () => [] },
})

function fmtMoney(n) {
  return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Payroll Dashboard</h1>
        <p class="mhr-page-head__sub">Overview of payroll operations and pending items</p>
      </div>
      <div class="mhr-page-head__actions">
        <a :href="route('payroll.payment-batches.index')" class="mhr-btn mhr-btn--outline">
          <AppIcon name="wallet" /> Payment Batches
        </a>
        <a :href="route('payroll.timesheets.review')" class="mhr-btn mhr-btn--primary">
          <AppIcon name="clock" /> Review Timesheets
        </a>
      </div>
    </div>

    <!-- Stats row -->
    <div class="mhr-grid-4" style="margin-bottom:24px;">
      <div class="mhr-stat">
        <div class="mhr-stat__label">Pending Review</div>
        <div class="mhr-stat__value">
          <em>{{ stats.pendingTimesheetCount || 0 }}</em>
          <span class="mhr-stat__unit"> timesheets</span>
        </div>
        <a :href="route('payroll.timesheets.review')" class="mhr-stat__delta" style="color:var(--mhr-accent);cursor:pointer;">
          Review now →
        </a>
      </div>

      <div class="mhr-stat mhr-stat--warn">
        <div class="mhr-stat__label">Missing Timesheets</div>
        <div class="mhr-stat__value">
          <em>{{ stats.missingTimesheetCount || 0 }}</em>
          <span class="mhr-stat__unit"> employees</span>
        </div>
        <a :href="route('payroll.timesheets.missing')" class="mhr-stat__delta" style="color:var(--mhr-warn);cursor:pointer;">
          View list →
        </a>
      </div>

      <div class="mhr-stat">
        <div class="mhr-stat__label">Approved This Month</div>
        <div class="mhr-stat__value">
          <em>{{ stats.approvedTimesheetCount || 0 }}</em>
          <span class="mhr-stat__unit"> timesheets</span>
        </div>
        <div class="mhr-stat__delta">
          {{ fmtMoney(stats.totalApprovedAmount || 0) }} total
        </div>
      </div>

      <div class="mhr-stat" style="background:linear-gradient(135deg,var(--green-700),var(--green-800));color:#fff;border:none;">
        <div class="mhr-stat__label" style="color:rgba(255,255,255,0.7);">Next Payroll Run</div>
        <div class="mhr-stat__value" style="color:#fff;font-size:18px;">
          {{ stats.nextPayrollDate || 'Not scheduled' }}
        </div>
        <div class="mhr-stat__delta" style="color:rgba(255,255,255,0.85);">
          <AppIcon name="calendar" :size="12" /> {{ stats.daysUntilPayroll || 0 }} days
        </div>
      </div>
    </div>

    <!-- Main content area -->
    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
      <!-- Pending Timesheets -->
      <div class="mhr-card">
        <div class="mhr-card__hd">
          <div>
            <h3 class="mhr-card__title">Pending Timesheet Review</h3>
            <p class="mhr-card__sub">Timesheets awaiting payroll approval</p>
          </div>
          <div class="mhr-card__hd-actions">
            <a :href="route('payroll.timesheets.review')" class="mhr-btn mhr-btn--ghost mhr-btn--sm">View all</a>
          </div>
        </div>
        <div class="mhr-card__body" style="padding:0;">
          <p v-if="!pendingTimesheets.length" style="color:var(--mhr-ink-3);font-size:13px;padding:16px 20px;">
            No timesheets pending review.
          </p>
          <div v-for="ts in pendingTimesheets.slice(0, 5)" :key="ts.id"
            style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--mhr-line-2);">
            <div style="flex:1;min-width:0;">
              <div style="font-size:13.5px;font-weight:500;">{{ ts.employeeName }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);">{{ ts.period }} · {{ ts.daysWorked }} days</div>
            </div>
            <div style="text-align:right;font-size:13px;font-weight:600;color:var(--mhr-ink);">
              {{ fmtMoney(ts.totalPayment) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Missing Timesheets -->
      <div class="mhr-card">
        <div class="mhr-card__hd">
          <div>
            <h3 class="mhr-card__title">Missing Timesheets</h3>
            <p class="mhr-card__sub">Employees without submitted timesheets</p>
          </div>
          <div class="mhr-card__hd-actions">
            <a :href="route('payroll.timesheets.missing')" class="mhr-btn mhr-btn--ghost mhr-btn--sm">View all</a>
          </div>
        </div>
        <div class="mhr-card__body" style="padding:0;">
          <p v-if="!missingTimesheets.length" style="color:var(--mhr-ink-3);font-size:13px;padding:16px 20px;">
            All employees have submitted timesheets.
          </p>
          <div v-for="emp in missingTimesheets.slice(0, 5)" :key="emp.id"
            style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--mhr-line-2);">
            <div style="flex:1;min-width:0;">
              <div style="font-size:13.5px;font-weight:500;">{{ emp.fullName }}</div>
              <div style="font-size:12px;color:var(--mhr-ink-3);">{{ emp.employeeNumber }}</div>
            </div>
            <span class="mhr-badge mhr-badge--warn">Missing</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Payment Batches -->
    <div class="mhr-card" style="margin-top:16px;">
      <div class="mhr-card__hd">
        <div>
          <h3 class="mhr-card__title">Recent Payment Batches</h3>
          <p class="mhr-card__sub">Latest processed payment batches</p>
        </div>
        <div class="mhr-card__hd-actions">
          <a :href="route('payroll.payment-batches.index')" class="mhr-btn mhr-btn--ghost mhr-btn--sm">View all</a>
        </div>
      </div>
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>BATCH ID</th>
              <th>PERIOD</th>
              <th>EMPLOYEES</th>
              <th>TOTAL AMOUNT</th>
              <th>STATUS</th>
              <th>DATE</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!recentBatches.length">
              <td colspan="6" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No payment batches yet.
              </td>
            </tr>
            <tr v-for="batch in recentBatches.slice(0, 5)" :key="batch.id">
              <td style="font-family:monospace;font-weight:500;">#{{ batch.id }}</td>
              <td style="color:var(--mhr-ink-2);">{{ batch.period }}</td>
              <td style="color:var(--mhr-ink-2);">{{ batch.employeeCount }}</td>
              <td style="font-weight:600;color:var(--mhr-ink);">{{ fmtMoney(batch.totalAmount) }}</td>
              <td>
                <span class="mhr-badge"
                  :class="{
                    'mhr-badge--success': batch.status === 'Finalized',
                    'mhr-badge--neutral': batch.status === 'Draft',
                  }">{{ batch.status }}</span>
              </td>
              <td style="color:var(--mhr-ink-3);font-size:12px;">{{ fmtDate(batch.createdAt) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
