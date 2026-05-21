<script setup>
import { computed } from 'vue'
import MeridianLayout from '@/Layouts/MeridianLayout.vue'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'

defineOptions({ layout: MeridianLayout })

const props = defineProps({
  hrRole:   { type: String, default: 'employee' },
  me:       { type: Object, default: () => ({}) },
  stats:    { type: Object, default: () => ({}) },
  activity: { type: Array,  default: () => [] },
  pendingLeaves:     { type: Array, default: () => [] },
  pendingTimesheets: { type: Array, default: () => [] },
  leaveBalance:      { type: Object, default: () => ({}) },
  employees:         { type: Array,  default: () => [] },
})

function greet() {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 18) return 'Good afternoon'
  return 'Good evening'
}

function todayPretty() {
  return new Date().toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' })
}

function fmtMoney(n) {
  return '$' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtRange(a, b) {
  if (a === b) return fmtDate(a)
  const da = new Date(a), db = new Date(b)
  if (da.getMonth() === db.getMonth()) {
    return `${da.getDate()} – ${db.getDate()} ${db.toLocaleDateString(undefined, { month: 'short', year: 'numeric' })}`
  }
  return `${fmtShortDate(a)} – ${fmtShortDate(b)}, ${db.getFullYear()}`
}

function fmtShortDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short' })
}

function fmtDate(s) {
  if (!s) return ''
  return new Date(s).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

const lb = computed(() => props.leaveBalance)
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mhr-page-head">
      <div>
        <h1 class="mhr-page-head__title">
          {{ greet() }}, <em>{{ (me.name || '').split(' ')[0] }}</em>
        </h1>
        <p class="mhr-page-head__sub">
          {{ todayPretty() }} ·
          {{ hrRole === 'admin' ? 'HR Administrator' : hrRole === 'manager' ? 'Design team · 6 reports' : 'Design team' }}
        </p>
      </div>
      <div class="mhr-page-head__actions">
        <a :href="route('hr.timesheet')" class="mhr-btn mhr-btn--outline">
          <AppIcon name="clock" /> Log time
        </a>
        <a :href="route('hr.leave')" class="mhr-btn mhr-btn--primary">
          <AppIcon name="plus" /> Request time off
        </a>
      </div>
    </div>

    <!-- Employee view -->
    <template v-if="hrRole === 'employee'">
      <div class="mhr-grid-4" style="margin-bottom:24px;">
        <!-- Leave balance tiles -->
        <div class="mhr-stat" v-for="(bal, type) in { Annual: lb.annual, Sick: lb.sick, Personal: lb.personal }" :key="type">
          <div class="mhr-stat__label">{{ type }} leave</div>
          <div class="mhr-stat__value">
            <em>{{ (bal.total - bal.used - bal.pending) }}</em>
            <span class="mhr-stat__unit"> / {{ bal.total }} days</span>
          </div>
          <div style="margin-top:12px;height:6px;background:var(--mhr-line-2);border-radius:999px;overflow:hidden;display:flex;">
            <div :style="`width:${(bal.used/bal.total)*100}%;background:var(--green-700)`" />
            <div :style="`width:${(bal.pending/bal.total)*100}%;background:var(--green-300)`" />
          </div>
          <div class="mhr-stat__delta">
            {{ bal.used }} used
            <span v-if="bal.pending > 0" style="color:var(--mhr-warn)"> · {{ bal.pending }} pending</span>
          </div>
        </div>
        <!-- Next pay tile -->
        <div class="mhr-stat" style="background:linear-gradient(135deg,var(--green-700),var(--green-800));color:#fff;border:none;">
          <div class="mhr-stat__label" style="color:rgba(255,255,255,0.7);">Next pay</div>
          <div class="mhr-stat__value" style="color:#fff;">
            {{ stats.nextPayFormatted || '$7,312' }}<span class="mhr-stat__unit" style="color:rgba(255,255,255,0.7);">.50</span>
          </div>
          <div class="mhr-stat__delta" style="color:rgba(255,255,255,0.85);">
            <AppIcon name="calendar" :size="12" /> {{ stats.nextPayDate || 'Friday, May 29' }}
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
        <!-- Upcoming leave strip -->
        <div class="mhr-card">
          <div class="mhr-card__hd">
            <div>
              <h3 class="mhr-card__title">Upcoming time off</h3>
              <p class="mhr-card__sub">Your team's next two weeks</p>
            </div>
            <div class="mhr-card__hd-actions">
              <a :href="route('hr.leave')" class="mhr-btn mhr-btn--ghost mhr-btn--sm">View all</a>
            </div>
          </div>
          <div class="mhr-card__body" style="padding:16px 20px;">
            <p style="color:var(--mhr-ink-3);font-size:13px;">No upcoming absences in the next 14 days.</p>
          </div>
        </div>

        <!-- Recent activity -->
        <div class="mhr-card">
          <div class="mhr-card__hd">
            <h3 class="mhr-card__title">Recent activity</h3>
          </div>
          <div style="padding:8px 0;">
            <div v-for="a in activity.slice(0,5)" :key="a.id"
              style="display:flex;gap:12px;padding:10px 20px;align-items:flex-start;">
              <AppAvatar v-if="a.c != null" :name="a.who" :c="a.c" />
              <div v-else style="width:28px;height:28px;border-radius:14px;background:var(--mhr-accent-soft);color:var(--green-700);display:grid;place-items:center;flex-shrink:0;">
                <AppIcon name="zap" :size="14" />
              </div>
              <div style="font-size:13px;color:var(--mhr-ink-2);line-height:1.5;flex:1;">
                <strong style="color:var(--mhr-ink);">{{ a.who }}</strong> {{ a.action }}
                <span style="color:var(--mhr-ink);"> {{ a.target }}</span>
                <div style="font-size:11.5px;color:var(--mhr-ink-3);margin-top:2px;">{{ a.when }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Manager view -->
    <template v-else-if="hrRole === 'manager'">
      <div class="mhr-grid-4" style="margin-bottom:24px;">
        <div class="mhr-stat" style="cursor:pointer;border-color:var(--green-300);" @click="$inertia.get(route('hr.approvals.leave'))">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0;">
            <div class="mhr-stat__label" style="margin:0;">Leave requests</div>
            <div style="width:28px;height:28px;border-radius:8px;background:var(--mhr-accent-soft);color:var(--green-700);display:grid;place-items:center;">
              <AppIcon name="calendar" :size="14" />
            </div>
          </div>
          <div class="mhr-stat__value" style="margin-top:8px;"><em>{{ pendingLeaves.length }}</em></div>
          <div class="mhr-stat__delta">awaiting your review <AppIcon name="chevron" :size="11" style="margin-left:4px;" /></div>
        </div>
        <div class="mhr-stat" style="cursor:pointer;border-color:var(--green-300);" @click="$inertia.get(route('hr.approvals.time'))">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0;">
            <div class="mhr-stat__label" style="margin:0;">Timesheets</div>
            <div style="width:28px;height:28px;border-radius:8px;background:var(--mhr-accent-soft);color:var(--green-700);display:grid;place-items:center;">
              <AppIcon name="clock" :size="14" />
            </div>
          </div>
          <div class="mhr-stat__value" style="margin-top:8px;"><em>{{ pendingTimesheets.length }}</em></div>
          <div class="mhr-stat__delta">for April 2026 <AppIcon name="chevron" :size="11" style="margin-left:4px;" /></div>
        </div>
        <div class="mhr-stat">
          <div class="mhr-stat__label">Team out today</div>
          <div class="mhr-stat__value"><em>1</em></div>
          <div class="mhr-stat__delta">Marcus Chen, returning Mon</div>
        </div>
        <div class="mhr-stat">
          <div class="mhr-stat__label">Team utilization</div>
          <div class="mhr-stat__value">92%</div>
          <div class="mhr-stat__delta mhr-stat__delta--up">↑ 4% vs last month</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
        <div class="mhr-card">
          <div class="mhr-card__hd">
            <div>
              <h3 class="mhr-card__title">Pending leave approvals</h3>
              <p class="mhr-card__sub">Acting on these unblocks your team</p>
            </div>
            <a :href="route('hr.approvals.leave')" class="mhr-btn mhr-btn--ghost mhr-btn--sm">
              Open queue <AppIcon name="chevron" />
            </a>
          </div>
          <div style="padding:4px 0;">
            <div v-for="r in pendingLeaves.slice(0,4)" :key="r.id"
              style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--mhr-line-2);">
              <AppAvatar :name="r.emp" :c="r.c" />
              <div style="flex:1;min-width:0;">
                <div style="font-size:13.5px;font-weight:500;">{{ r.emp }}</div>
                <div style="font-size:12px;color:var(--mhr-ink-3);">{{ r.type }} · {{ fmtRange(r.from, r.to) }} · {{ r.days }}d</div>
              </div>
              <span v-if="r.hasOverlap" class="mhr-pill mhr-pill--warn" style="display:inline-flex;align-items:center;gap:4px;">
                <AppIcon name="alert" :size="11" />Overlap
              </span>
              <button class="mhr-btn mhr-btn--sm mhr-btn--outline">Review</button>
            </div>
          </div>
        </div>
        <div class="mhr-card">
          <div class="mhr-card__hd"><h3 class="mhr-card__title">Team out next 14 days</h3></div>
          <div class="mhr-card__body">
            <p style="color:var(--mhr-ink-3);font-size:13px;">No scheduled absences.</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Admin view -->
    <template v-else>
      <div class="mhr-grid-4" style="margin-bottom:24px;">
        <div class="mhr-stat">
          <div class="mhr-stat__label">Headcount</div>
          <div class="mhr-stat__value"><em>{{ stats.headcount || 264 }}</em></div>
          <div class="mhr-stat__delta mhr-stat__delta--up">↑ 12 this quarter</div>
        </div>
        <div class="mhr-stat">
          <div class="mhr-stat__label">On leave today</div>
          <div class="mhr-stat__value"><em>{{ stats.onLeaveToday || 9 }}</em></div>
          <div class="mhr-stat__delta">3 sick, 6 annual</div>
        </div>
        <div class="mhr-stat" style="cursor:pointer;border-color:var(--green-300);" @click="$inertia.get(route('hr.approvals.leave'))">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0;">
            <div class="mhr-stat__label" style="margin:0;">Pending requests</div>
            <div style="width:28px;height:28px;border-radius:8px;background:var(--mhr-accent-soft);color:var(--green-700);display:grid;place-items:center;">
              <AppIcon name="inbox" :size="14" />
            </div>
          </div>
          <div class="mhr-stat__value" style="margin-top:8px;"><em>{{ stats.pendingRequests || 14 }}</em></div>
          <div class="mhr-stat__delta">across all teams <AppIcon name="chevron" :size="11" style="margin-left:4px;" /></div>
        </div>
        <div class="mhr-stat">
          <div class="mhr-stat__label">Payroll · May 2026</div>
          <div class="mhr-stat__value">$1.84m</div>
          <div class="mhr-stat__delta">Runs in 26 days</div>
        </div>
      </div>

      <!-- Department breakdown + utilization -->
      <div class="mhr-grid-2" style="gap:16px;margin-bottom:16px;">
        <div class="mhr-card">
          <div class="mhr-card__hd">
            <h3 class="mhr-card__title">Headcount by department</h3>
            <p class="mhr-card__sub">Including those out today</p>
          </div>
          <div class="mhr-card__body">
            <div v-for="d in [
              { name:'Engineering', count:84, leave:4,  color:'#3a6c8c' },
              { name:'Product',     count:32, leave:1,  color:'#8a5b9c' },
              { name:'Design',      count:24, leave:2,  color:'#4f8a55' },
              { name:'People',      count:18, leave:0,  color:'#b6772b' },
              { name:'Finance',     count:22, leave:1,  color:'#a8413a' },
              { name:'Operations',  count:84, leave:1,  color:'#5e6b3b' },
            ]" :key="d.name"
              style="display:grid;grid-template-columns:120px 1fr 72px;gap:12px;align-items:center;margin-bottom:10px;">
              <div style="font-size:13px;">{{ d.name }}</div>
              <div style="height:24px;background:var(--mhr-surface-2);border-radius:6px;position:relative;overflow:hidden;">
                <div :style="`width:${(d.count/84)*100}%;background:${d.color};height:100%;border-radius:6px;opacity:0.85`" />
              </div>
              <div style="font-size:12.5px;color:var(--mhr-ink-3);text-align:right;">
                <strong style="color:var(--mhr-ink);">{{ d.count }}</strong>
                <span v-if="d.leave > 0" style="color:var(--mhr-warn);"> · {{ d.leave }} out</span>
              </div>
            </div>
          </div>
        </div>
        <div class="mhr-card">
          <div class="mhr-card__hd">
            <h3 class="mhr-card__title">Org utilization</h3>
            <p class="mhr-card__sub">Hours logged vs available, last 6 months</p>
          </div>
          <div class="mhr-card__body">
            <div style="display:flex;align-items:flex-end;gap:14px;height:140px;">
              <div v-for="(v, i) in [86,79,88,90,89,92]" :key="i"
                style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;">
                <div style="flex:1;width:100%;display:flex;align-items:flex-end;">
                  <div :style="`width:100%;height:${((v-70)/30)*100}%;background:${i===5?'var(--green-700)':'var(--green-300)'};border-radius:6px 6px 0 0;position:relative;`">
                    <span v-if="i===5" style="position:absolute;top:-22px;left:50%;transform:translateX(-50%);font-size:11px;font-weight:600;color:var(--green-700);">{{ v }}%</span>
                  </div>
                </div>
                <div style="font-size:11px;color:var(--mhr-ink-3);">{{ ['Nov','Dec','Jan','Feb','Mar','Apr'][i] }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Activity log -->
      <div class="mhr-card">
        <div class="mhr-card__hd">
          <div>
            <h3 class="mhr-card__title">Recent system activity</h3>
            <p class="mhr-card__sub">Cross-org events from the last 24 hours</p>
          </div>
          <button class="mhr-btn mhr-btn--ghost mhr-btn--sm">Export <AppIcon name="download" /></button>
        </div>
        <div style="padding:4px 0;">
          <div v-for="a in activity" :key="a.id"
            style="display:flex;gap:12px;padding:12px 20px;align-items:center;border-bottom:1px solid var(--mhr-line-2);">
            <AppAvatar v-if="a.c != null" :name="a.who" :c="a.c" />
            <div v-else style="width:28px;height:28px;border-radius:14px;background:var(--mhr-accent-soft);color:var(--green-700);display:grid;place-items:center;flex-shrink:0;">
              <AppIcon name="zap" :size="14" />
            </div>
            <div style="font-size:13px;color:var(--mhr-ink-2);flex:1;">
              <strong style="color:var(--mhr-ink);">{{ a.who }}</strong> {{ a.action }}
              <span style="color:var(--mhr-ink);"> {{ a.target }}</span>
            </div>
            <div style="font-size:12px;color:var(--mhr-ink-3);">{{ a.when }}</div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
