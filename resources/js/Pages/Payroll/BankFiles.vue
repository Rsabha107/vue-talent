<script setup>
import PayrollLayout from '@/Layouts/PayrollLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'

defineOptions({ layout: PayrollLayout })

const props = defineProps({
  bankFiles: { type: Array, default: () => [] },
})

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function downloadFile(fileId) {
  window.location.href = route('payroll.bank-files.download', fileId)
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Bank Files</h1>
        <p class="mhr-page-head__sub">Generate and download bank payment files for finalized batches</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--primary">
          <AppIcon name="doc" /> Generate Bank File
        </button>
      </div>
    </div>

    <!-- Bank Files Table -->
    <div class="mhr-card">
      <div class="mhr-table-wrap">
        <table class="mhr-table">
          <thead>
            <tr>
              <th>FILE NAME</th>
              <th>BATCH ID</th>
              <th>PERIOD</th>
              <th>EMPLOYEES</th>
              <th>FORMAT</th>
              <th>GENERATED</th>
              <th style="width:80px;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="bankFiles.length === 0">
              <td colspan="7" style="text-align:center;padding:32px;color:var(--mhr-ink-3);">
                No bank files generated yet. Finalize a payment batch to generate a file.
              </td>
            </tr>
            <tr v-for="file in bankFiles" :key="file.id">
              <td style="font-family:monospace;font-weight:500;color:var(--mhr-ink);">{{ file.fileName }}</td>
              <td style="font-family:monospace;font-size:12px;">#{{ file.batchId }}</td>
              <td style="color:var(--mhr-ink-2);">{{ file.period }}</td>
              <td style="color:var(--mhr-ink-2);">{{ file.employeeCount }}</td>
              <td>
                <span class="mhr-badge mhr-badge--neutral">{{ file.format }}</span>
              </td>
              <td style="color:var(--mhr-ink-3);font-size:12px;">{{ fmtDate(file.generatedAt) }}</td>
              <td>
                <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" @click="downloadFile(file.id)" title="Download">
                  <AppIcon name="download" :size="14" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Info card -->
    <div class="mhr-card" style="margin-top:16px;background:var(--mhr-accent-soft);border:1px solid var(--mhr-accent);">
      <div class="mhr-card__body">
        <div style="display:flex;gap:12px;">
          <AppIcon name="info" :size="20" style="color:var(--mhr-accent);flex-shrink:0;margin-top:2px;" />
          <div>
            <h3 style="font-size:14px;font-weight:600;color:var(--mhr-accent);margin:0 0 6px;">Bank File Generation</h3>
            <p style="font-size:13px;color:var(--mhr-ink-2);line-height:1.5;">
              Bank files are generated from finalized payment batches. Once generated, the file can be uploaded to your bank's payment portal to process payroll payments. 
              Files are formatted according to standard banking formats (ACH, BACS, SEPA, etc.).
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
