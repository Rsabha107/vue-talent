<script setup>
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  batches: { type: Array, default: () => [] },
})

function fmtMoney(n) {
  return '$' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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
        <h1 class="mhr-page-head__title">Payment Batches</h1>
        <p class="mhr-page-head__sub">Create and manage payment batches for approved timesheets</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--primary">
          <AppIcon name="plus" /> Create Payment Batch
        </button>
      </div>
    </div>

    <!-- Batches Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>BATCH ID</th>
              <th>PERIOD</th>
              <th>EMPLOYEES</th>
              <th>TOTAL AMOUNT</th>
              <th>STATUS</th>
              <th>CREATED</th>
              <th style="width:80px;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="batches.length === 0">
              <td colspan="7" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No payment batches yet. Create your first batch to get started.
              </td>
            </tr>
            <tr v-for="batch in batches" :key="batch.id">
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
              <td>
                <button class="mhr-btn mhr-btn--ghost mhr-btn--sm">
                  <AppIcon name="eye" :size="14" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
