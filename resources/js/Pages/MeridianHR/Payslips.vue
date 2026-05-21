<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import StatusPill from '@/Components/MeridianHR/StatusPill.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  payslips: { type: Array, default: () => [] },
})

const active = ref(props.payslips[0] || null)

const ytd = computed(() => props.payslips
  .filter(p => p.period.includes('2026'))
  .reduce((a, p) => ({ gross: a.gross + p.gross, net: a.net + p.net, tax: a.tax + p.tax }), { gross: 0, net: 0, tax: 0 })
)

function fmtMoney(n) {
  return '$' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}
function fmtShortDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short' })
}
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Payslips</h1>
        <p class="mhr-page-head__sub">Issued payslips and year-to-date summary</p>
      </div>
      <div class="mhr-page-head__actions">
        <button class="mhr-btn mhr-btn--outline"><AppIcon name="download" /> Download year</button>
      </div>
    </div>

    <!-- YTD stats -->
    <div class="mhr-grid-3" style="margin-bottom:20px;">
      <div class="mhr-stat">
        <div class="mhr-stat__label">YTD Gross</div>
        <div class="mhr-stat__value">{{ fmtMoney(ytd.gross) }}</div>
        <div class="mhr-stat__delta">Jan–Apr 2026</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">YTD Net</div>
        <div class="mhr-stat__value">{{ fmtMoney(ytd.net) }}</div>
        <div class="mhr-stat__delta">After tax &amp; deductions</div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">YTD Tax</div>
        <div class="mhr-stat__value">{{ fmtMoney(ytd.tax) }}</div>
        <div class="mhr-stat__delta">Federal + state + FICA</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;">
      <!-- Table -->
      <div class="mhr-card">
        <div class="mhr-card__hd">
          <h3 class="mhr-card__title">All payslips</h3>
          <p class="mhr-card__sub">{{ payslips.length }} issued</p>
        </div>
        <table class="mhr-table">
          <thead>
            <tr>
              <th>Period</th>
              <th>Issued</th>
              <th style="text-align:right;">Gross</th>
              <th style="text-align:right;">Net</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in payslips" :key="p.id"
              style="cursor:pointer;"
              :data-selected="active?.id === p.id ? '1' : undefined"
              @click="active = p">
              <td>
                <div style="font-weight:500;">{{ p.period }}</div>
                <div v-if="p.note" style="font-size:11.5px;color:var(--green-700);margin-top:2px;">{{ p.note }}</div>
              </td>
              <td style="color:var(--mhr-ink-3);">{{ fmtShortDate(p.issued) }}</td>
              <td style="text-align:right;" class="mhr-mono">{{ fmtMoney(p.gross) }}</td>
              <td style="text-align:right;" class="mhr-mono"><strong>{{ fmtMoney(p.net) }}</strong></td>
              <td><StatusPill status="paid" /></td>
              <td>
                <button class="mhr-icon-btn" style="width:28px;height:28px;" @click.stop>
                  <AppIcon name="download" :size="13" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payslip detail -->
      <div class="mhr-card" style="overflow:hidden;">
        <template v-if="active">
          <div class="mhr-card__hd">
            <div>
              <h3 class="mhr-card__title">{{ active.period }}</h3>
              <p class="mhr-card__sub">Issued {{ fmtDate(active.issued) }} · {{ active.method }}</p>
            </div>
          </div>
          <div class="mhr-card__body" style="background:var(--mhr-surface-2);">
            <div style="background:var(--mhr-surface);border-radius:10px;padding:20px;border:1px solid var(--mhr-line);">
              <!-- Header -->
              <div style="display:flex;justify-content:space-between;margin-bottom:18px;">
                <div>
                  <div style="font-family:var(--mhr-font-display);font-size:18px;font-weight:500;">Meridian</div>
                  <div style="font-size:11px;color:var(--mhr-ink-3);">123 Birch Lane, Brooklyn NY</div>
                </div>
                <div style="text-align:right;">
                  <div style="font-size:11px;color:var(--mhr-ink-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Pay period</div>
                  <div style="font-size:14px;font-weight:500;">{{ active.period }}</div>
                </div>
              </div>
              <!-- Gross -->
              <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:1px solid var(--mhr-line-2);font-size:13px;">
                <span>Gross earnings</span><span class="mhr-mono">{{ fmtMoney(active.gross) }}</span>
              </div>
              <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;color:var(--mhr-ink-3);">
                <span style="padding-left:14px;">Base salary</span>
                <span class="mhr-mono">{{ fmtMoney(active.gross - (active.note ? 3500 : 0)) }}</span>
              </div>
              <div v-if="active.note" style="display:flex;justify-content:space-between;padding:4px 0 8px;font-size:13px;color:var(--mhr-ink-3);">
                <span style="padding-left:14px;">Annual bonus</span>
                <span class="mhr-mono">{{ fmtMoney(3500) }}</span>
              </div>
              <!-- Tax -->
              <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:1px solid var(--mhr-line-2);font-size:13px;color:var(--mhr-danger);">
                <span>Federal &amp; state tax</span><span class="mhr-mono">−{{ fmtMoney(active.tax) }}</span>
              </div>
              <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;color:var(--mhr-danger);">
                <span>Deductions (401k, health)</span><span class="mhr-mono">−{{ fmtMoney(active.deductions) }}</span>
              </div>
              <!-- Net -->
              <div style="display:flex;justify-content:space-between;padding:16px 0 0;border-top:2px solid var(--mhr-ink);margin-top:8px;">
                <span style="font-weight:600;">Net pay</span>
                <span style="font-family:var(--mhr-font-display);font-size:22px;font-weight:500;color:var(--green-700);">
                  {{ fmtMoney(active.net) }}
                </span>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
