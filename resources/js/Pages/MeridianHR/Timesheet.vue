<script setup>
import { ref, computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:    { type: String, default: 'employee' },
  timesheets: { type: Object, default: () => ({}) },
})

const monthKey = ref(Object.keys(props.timesheets)[0] || '2026-05')
const current  = computed(() => props.timesheets[monthKey.value] || { label: '', days: [], submitted: false })

const months   = computed(() => Object.entries(props.timesheets).map(([k, v]) => ({ key: k, label: v.label })))

const summary  = computed(() => {
  const days = current.value.days || []
  return {
    worked:  days.filter(d => d.type === 'W').reduce((s, d) => s + d.hours, 0),
    leave:   days.filter(d => d.type === 'L').length * 8,
    unpaid:  days.filter(d => d.type === 'U').length * 8,
    weekend: days.filter(d => d.type === '0').length,
  }
})

const typeConfig = {
  W: { label: 'Worked',   cls: 'mhr-ts-day--worked' },
  L: { label: 'Leave',    cls: 'mhr-ts-day--leave'  },
  U: { label: 'Unpaid',   cls: 'mhr-ts-day--unpaid' },
  '0': { label: '',       cls: 'mhr-ts-day--weekend' },
  H: { label: 'Holiday',  cls: 'mhr-ts-day--holiday' },
  ' ': { label: '',       cls: 'mhr-ts-day--future'  },
}

const WEEKDAYS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']

function getFirstDayOffset() {
  const [y, m] = monthKey.value.split('-').map(Number)
  const d = new Date(y, m - 1, 1)
  return (d.getDay() + 6) % 7
}

function submit() {
  router.post(route('hr.timesheet.submit'), { month: monthKey.value }, {
    onSuccess: () => {},
  })
}
</script>

<template>
  <div>
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">Timesheet</h1>
        <p class="mhr-page-head__sub">Monthly attendance record</p>
      </div>
      <div class="mhr-page-head__actions">
        <div style="display:flex;gap:4px;padding:3px;background:var(--mhr-surface);border:1px solid var(--mhr-line);border-radius:9px;">
          <button v-for="m in months" :key="m.key"
            class="mhr-btn mhr-btn--sm"
            :style="monthKey === m.key ? 'background:var(--green-700);color:#fff;' : 'background:transparent;color:var(--mhr-ink-2);'"
            @click="monthKey = m.key">
            {{ m.label }}
          </button>
        </div>
        <button v-if="!current.submitted" class="mhr-btn mhr-btn--primary" @click="submit">
          <AppIcon name="send" /> Submit timesheet
        </button>
        <span v-else class="mhr-pill mhr-pill--success">
          <AppIcon name="check" :size="11" /> Submitted
        </span>
      </div>
    </div>

    <!-- Summary stats -->
    <div class="mhr-grid-4" style="margin-bottom:24px;">
      <div class="mhr-stat">
        <div class="mhr-stat__label">Hours worked</div>
        <div class="mhr-stat__value"><em>{{ summary.worked }}</em><span class="mhr-stat__unit">h</span></div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Paid leave</div>
        <div class="mhr-stat__value"><em>{{ summary.leave }}</em><span class="mhr-stat__unit">h</span></div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Unpaid leave</div>
        <div class="mhr-stat__value"><em>{{ summary.unpaid }}</em><span class="mhr-stat__unit">h</span></div>
      </div>
      <div class="mhr-stat">
        <div class="mhr-stat__label">Weekends</div>
        <div class="mhr-stat__value"><em>{{ summary.weekend }}</em><span class="mhr-stat__unit">days</span></div>
      </div>
    </div>

    <!-- Calendar grid -->
    <div class="mhr-card">
      <div class="mhr-card__hd">
        <h3 class="mhr-card__title">{{ current.label }}</h3>
        <div class="mhr-card__hd-actions" style="gap:16px;">
          <div style="display:flex;gap:12px;font-size:12px;align-items:center;">
            <span v-for="(cfg, t) in { W: typeConfig['W'], L: typeConfig['L'], U: typeConfig['U'] }" :key="t"
              style="display:flex;align-items:center;gap:6px;">
              <span :class="cfg.cls" style="width:14px;height:14px;border-radius:4px;display:inline-block;" />
              {{ cfg.label }}
            </span>
          </div>
        </div>
      </div>
      <div class="mhr-card__body">
        <!-- Weekday headers -->
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:4px;">
          <div v-for="wd in WEEKDAYS" :key="wd"
            style="text-align:center;font-size:11px;font-weight:600;color:var(--mhr-ink-4);text-transform:uppercase;letter-spacing:0.05em;padding:4px 0;">
            {{ wd }}
          </div>
        </div>
        <!-- Day cells -->
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
          <!-- Offset blank cells -->
          <div v-for="i in getFirstDayOffset()" :key="'blank-'+i" />
          <!-- Day cells -->
          <div v-for="day in current.days" :key="day.d"
            class="mhr-ts-day"
            :class="typeConfig[day.type]?.cls || 'mhr-ts-day--future'"
            :title="day.hours > 0 ? `${day.hours}h` : typeConfig[day.type]?.label || ''">
            <span style="font-size:11px;">{{ day.d }}</span>
            <span v-if="day.hours > 0" style="font-size:9px;opacity:0.7;margin-left:2px;">{{ day.hours }}h</span>
          </div>
        </div>

        <!-- Legend -->
        <div style="display:flex;gap:24px;margin-top:20px;padding-top:16px;border-top:1px solid var(--mhr-line-2);font-size:12px;color:var(--mhr-ink-3);">
          <span>
            <strong style="color:var(--mhr-ink);">{{ summary.worked }}h</strong> worked ·
            <strong style="color:var(--mhr-ink);">{{ summary.leave }}h</strong> paid leave ·
            <strong style="color:var(--mhr-ink);">{{ summary.unpaid }}h</strong> unpaid
          </span>
          <span v-if="current.submitted" style="margin-left:auto;color:var(--green-600);font-weight:500;">
            <AppIcon name="check" :size="12" /> Submitted for approval
          </span>
          <span v-else style="margin-left:auto;color:var(--mhr-warn);">
            Not yet submitted
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
