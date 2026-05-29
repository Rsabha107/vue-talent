<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import RefreshButton from '@/Components/MeridianHR/RefreshButton.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  batch: { type: Object, required: true },
})

const page = usePage()
const toast = ref(null)
const isRefreshing = ref(false)
const showFinalizeModal = ref(false)
const showProcessModal = ref(false)

function showToast(msg, isError = false) {
  toast.value = { msg, isError }
  setTimeout(() => toast.value = null, 3000)
}

// Watch for flash messages
if (page.props.flash?.success) {
  showToast(page.props.flash.success)
}
if (page.props.flash?.error) {
  showToast(page.props.flash.error, true)
}

function backToBatches() {
  router.visit(route('payroll.payment-batches'))
}

function openFinalizeModal() {
  showFinalizeModal.value = true
}

function closeFinalizeModal() {
  showFinalizeModal.value = false
}

function confirmFinalize() {
  router.post(route('payroll.payment-batches.finalize', props.batch.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast('Batch finalized successfully')
      closeFinalizeModal()
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to finalize batch', true)
    }
  })
}

function openProcessModal() {
  showProcessModal.value = true
}

function closeProcessModal() {
  showProcessModal.value = false
}

function confirmProcess() {
  router.post(route('payroll.payment-batches.process', props.batch.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast('Batch marked as processed')
      closeProcessModal()
    },
    onError: (errors) => {
      showToast(Object.values(errors)[0] || 'Failed to process batch', true)
    }
  })
}

function fmtMoney(n) {
  return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function getStatusClass(status) {
  const statusLower = status.toLowerCase()
  if (statusLower === 'processed') return 'mhr-badge--success'
  if (statusLower === 'finalized') return 'mhr-badge--info'
  return 'mhr-badge--neutral'
}

function refreshData() {
  isRefreshing.value = true
  router.get(route('payroll.payment-batches.show', props.batch.id), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { isRefreshing.value = false }, 500)
    }
  })
}

function exportBatch() {
  window.location.href = route('payroll.payment-batches.export', props.batch.id)
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
          <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="backToBatches">
            <AppIcon name="arrow-left" :size="14" />
          </button>
          <h1 class="mhr-page-head__title" style="margin:0;">{{ batch.batchNumber }}</h1>
          <span class="mhr-badge" :class="getStatusClass(batch.status)">{{ batch.status }}</span>
        </div>
        <p class="mhr-page-head__sub">{{ batch.batchName }}</p>
      </div>
      <div class="mhr-page-head__actions">
        <RefreshButton variant="outline" :is-refreshing="isRefreshing" @refresh="refreshData" />
        <button class="mhr-btn mhr-btn--outline" @click="exportBatch">
          <AppIcon name="download" /> Export to Excel
        </button>
        <button 
          v-if="batch.canFinalize" 
          class="mhr-btn mhr-btn--primary" 
          @click="openFinalizeModal"
        >
          <AppIcon name="check" /> Finalize Batch
        </button>
        <button 
          v-if="batch.canProcess" 
          class="mhr-btn mhr-btn--outline" 
          @click="openProcessModal"
        >
          <AppIcon name="dollar" /> Mark as Processed
        </button>
      </div>
    </div>

    <!-- Batch Summary -->
    <div class="mhr-grid-4" style="margin-bottom:24px;">
      <div class="mhr-stat">
        <div class="mhr-stat__label">Period</div>
        <div class="mhr-stat__value"><em>{{ batch.period }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">payroll cycle</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Timesheets</div>
        <div class="mhr-stat__value"><em>{{ batch.timesheetCount }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">submitted and approved</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Employees</div>
        <div class="mhr-stat__value"><em>{{ batch.employeeCount }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">unique payees</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Total Amount</div>
        <div class="mhr-stat__value"><em>{{ fmtMoney(batch.totalAmount) }}</em></div>
        <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:4px;">batch payment total</div>
      </div>
    </div>

    <!-- Batch Details -->
    <div class="mhr-card" style="margin-bottom:24px;">
      <div style="padding:20px;border-bottom:1px solid var(--mhr-line);">
        <h3 style="font-size:14px;font-weight:600;color:var(--mhr-ink);margin:0;">Batch Information</h3>
      </div>
      <div style="padding:20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
          <div>
            <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Created</div>
            <div style="font-size:14px;color:var(--mhr-ink);">{{ fmtDate(batch.createdAt) }}</div>
            <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">by {{ batch.createdBy }}</div>
          </div>
          <div v-if="batch.finalizedAt">
            <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Finalized</div>
            <div style="font-size:14px;color:var(--mhr-ink);">{{ fmtDate(batch.finalizedAt) }}</div>
            <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">by {{ batch.finalizedBy }}</div>
          </div>
          <div v-if="batch.processedAt">
            <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Processed</div>
            <div style="font-size:14px;color:var(--mhr-ink);">{{ fmtDate(batch.processedAt) }}</div>
            <div style="font-size:12px;color:var(--mhr-ink-3);margin-top:2px;">by {{ batch.processedBy }}</div>
          </div>
        </div>
        <div v-if="batch.notes" style="margin-top:20px;padding-top:20px;border-top:1px solid var(--mhr-line);">
          <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:6px;">Notes</div>
          <div style="font-size:14px;color:var(--mhr-ink-2);line-height:1.5;">{{ batch.notes }}</div>
        </div>
      </div>
    </div>

    <!-- Batch Items -->
    <div class="mhr-card">
      <div style="padding:20px;border-bottom:1px solid var(--mhr-line);">
        <h3 style="font-size:14px;font-weight:600;color:var(--mhr-ink);margin:0;">Payment Details ({{ batch.items.length }})</h3>
      </div>
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>TIMESHEET PERIOD</th>
              <th>AGREEMENT #</th>
              <th>NAME</th>
              <th>ROLE</th>
              <th>START DATE</th>
              <th>END DATE</th>
              <th style="text-align:right;">SALARY/MONTH</th>
              <th>SALARY BASIS</th>
              <th style="text-align:right;">DAYS WORKED</th>
              <th style="text-align:right;">TOTAL</th>
              <th>IBAN</th>
              <th>ACCOUNT HOLDER NAME</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in batch.items" :key="item.id">
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ item.timesheetPeriod }}</td>
              <td style="font-family:monospace;font-weight:500;">{{ item.agreementNumber }}</td>
              <td style="font-weight:500;color:var(--mhr-ink);">{{ item.employeeName }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ item.role }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ item.startDate }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ item.endDate }}</td>
              <td style="text-align:right;font-family:monospace;color:var(--mhr-ink-2);">{{ fmtMoney(item.monthlySalary) }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ item.salaryBasis }}</td>
              <td style="text-align:right;color:var(--mhr-ink);">{{ item.daysWorked }}</td>
              <td style="text-align:right;font-weight:600;color:var(--green-700);font-family:monospace;">{{ fmtMoney(item.paymentAmount) }}</td>
              <td style="font-family:monospace;font-size:13px;color:var(--mhr-ink-2);">{{ item.iban }}</td>
              <td style="color:var(--mhr-ink-2);font-size:13px;">{{ item.accountHolderName }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toast" class="mhr-toast" :style="toast.isError ? 'background:var(--mhr-danger);' : ''">
      <AppIcon :name="toast.isError ? 'x' : 'check'" :size="16" />
      {{ toast.msg }}
    </div>

    <!-- Finalize Confirmation Modal -->
    <div v-if="showFinalizeModal" class="mhr-modal__scrim" @click.self="closeFinalizeModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Finalize Payment Batch</h2>
          <p class="mhr-modal__sub">{{ batch.batchNumber }}</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--mhr-warn-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="alert" :size="16" style="color:var(--mhr-warn);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--mhr-warn);line-height:1.5;">
              <strong>This will lock the batch.</strong><br>
              Once finalized, you cannot edit or delete this batch. It will be ready for processing.
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:14px;">Are you sure you want to finalize this batch?</p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeFinalizeModal">
            Cancel
          </button>
          <button class="mhr-btn mhr-btn--primary" @click="confirmFinalize">
            <AppIcon name="check" :size="14" /> Finalize Batch
          </button>
        </div>
      </div>
    </div>

    <!-- Process Confirmation Modal -->
    <div v-if="showProcessModal" class="mhr-modal__scrim" @click.self="closeProcessModal">
      <div class="mhr-modal">
        <div class="mhr-modal__hd">
          <h2 class="mhr-modal__title">Process Payment Batch</h2>
          <p class="mhr-modal__sub">{{ batch.batchNumber }}</p>
        </div>
        <div class="mhr-modal__body">
          <div style="background:var(--green-bg);border-radius:8px;padding:12px;display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;">
            <AppIcon name="check" :size="16" style="color:var(--green-500);flex-shrink:0;" />
            <div style="font-size:13px;color:var(--green-700);line-height:1.5;">
              <strong>Mark as processed.</strong><br>
              This will mark the batch as paid and complete the payment cycle.
            </div>
          </div>
          <p style="color:var(--mhr-ink-2);font-size:14px;">Are you sure you want to mark this batch as processed?</p>
        </div>
        <div class="mhr-modal__ft">
          <button class="mhr-btn mhr-btn--outline" @click="closeProcessModal">
            Cancel
          </button>
          <button class="mhr-btn mhr-btn--primary" @click="confirmProcess">
            <AppIcon name="dollar" :size="14" /> Mark as Processed
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
