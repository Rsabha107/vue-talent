<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import '@/../css/meridian.css'

const page = usePage()
const collapsed = ref(false)
const openNotif = ref(false)
const toast = ref(null)
let toastTimer = null
const eventSelectorOpen = ref(false)
const eventSelectorRef = ref(null)

const auth    = computed(() => page.props.auth || {})
const hrRole  = computed(() => page.props.hrRole || 'employee')
const hrPage  = computed(() => page.props.hrPage || 'dashboard')
const me      = computed(() => page.props.me || { name: 'User', initials: 'U', avatarColor: 0, role: 'Employee' })
const availableEvents = computed(() => page.props.availableEvents || [])
const selectedEvent = computed(() => page.props.selectedEvent || null)
const pendingCounts = computed(() => page.props.pendingCounts || { pendingLeaves: 0, pendingTimesheets: 0 })

const selectedEventData = computed(() => {
  if (!selectedEvent.value) return null
  return availableEvents.value.find(e => e.id === selectedEvent.value) || null
})

const isEmployee = computed(() => !['admin', 'manager'].includes(hrRole.value))
const isAdmin = computed(() => hrRole.value === 'admin')
const isManager = computed(() => hrRole.value === 'manager')

const activeEventLabel = computed(() => {
  if (!selectedEvent.value) {
    if (isAdmin.value) return 'All Events'
    if (isManager.value) return 'All My Events'
    return 'Select Event'
  }
  const ev = availableEvents.value.find(e => e.id === selectedEvent.value)
  if (ev) return ev.name
  if (isAdmin.value) return 'All Events'
  if (isManager.value) return 'All My Events'
  return 'Select Event'
})

// Navigation structure - now a function to use dynamic badge counts
const getNavStructure = (counts) => ({
  'employee-basic': [
    { group: 'Workspace', items: [
      { id: 'dashboard',      label: 'Home',            icon: 'home' },
      { id: 'leave-requests', label: 'My leaves',  icon: 'inbox' },
      { id: 'my-timesheets',  label: 'My timesheets',   icon: 'inbox' },
    ]},
    { group: 'Personal', items: [
      { id: 'documents', label: 'Documents',        icon: 'doc' },
      { id: 'emergency', label: 'Emergency contact', icon: 'user' },
      { id: 'profile',   label: 'My profile', icon: 'user' },
    ]},
  ],
  'employee-full': [
    { group: 'Workspace', items: [
      { id: 'dashboard',      label: 'Home',            icon: 'home' },
      { id: 'leave-requests', label: 'My leaves',  icon: 'inbox' },
      { id: 'my-timesheets',  label: 'My timesheets',   icon: 'inbox' },
    ]},
    { group: 'Personal', items: [
      { id: 'addresses', label: 'Addresses',        icon: 'pin' },
      { id: 'emergency', label: 'Emergency contact', icon: 'user' },
    ]},
    { group: 'Records', items: [
      { id: 'documents', label: 'Documents',  icon: 'doc' },
      { id: 'payslips',  label: 'Payslips',   icon: 'wallet' },
      { id: 'profile',   label: 'My profile', icon: 'user' },
    ]},
  ],
  employee: [
    { group: 'Workspace', items: [
      { id: 'dashboard',      label: 'Home',            icon: 'home' },
      { id: 'leave-requests', label: 'My Leaves',  icon: 'inbox' },
      { id: 'my-timesheets',  label: 'My timesheets',   icon: 'inbox' },
    ]},
    { group: 'Personal', items: [
      { id: 'addresses', label: 'Addresses',        icon: 'pin' },
      { id: 'banks',     label: 'Banks',            icon: 'wallet' },
      { id: 'salary',    label: 'Salary',           icon: 'wallet' },
      { id: 'emergency', label: 'Emergency contact', icon: 'user' },
    ]},
    { group: 'Records', items: [
      { id: 'documents', label: 'Documents',  icon: 'doc' },
      { id: 'payslips',  label: 'Payslips',   icon: 'wallet' },
      { id: 'employee', label: 'Employee',  icon: 'users' },
      { id: 'profile',   label: 'My profile', icon: 'user' },
    ]},
  ],
  manager: [
    { group: 'Workspace', items: [
      { id: 'dashboard', label: 'Home',       icon: 'home' },
      { id: 'leave-requests', label: 'My leaves', icon: 'inbox' },
      { id: 'my-timesheets', label: 'My timesheets',  icon: 'inbox' },
    ]},
    { group: 'Approvals', items: [
      { id: 'approve-leave', label: 'Leaves', icon: 'inbox', badge: counts.pendingLeaves },
      { id: 'approve-time',  label: 'Timesheets',     icon: 'inbox', badge: counts.pendingTimesheets },
    ]},
    { group: 'Team', items: [
      { id: 'employee',  label: 'Team members',      icon: 'users' },
      { id: 'team-leave-requests', label: 'All leaves', icon: 'calendar' },
      { id: 'team-timesheets', label: 'All timesheets', icon: 'clock' },
    ]},
    { group: 'Personal', items: [
      { id: 'documents', label: 'Documents',  icon: 'doc' },
      { id: 'emergency', label: 'Emergency contact', icon: 'user' },
      { id: 'profile',   label: 'My profile', icon: 'user' },
    ]},
  ],
  admin: [
    { group: 'Workspace', items: [
      { id: 'dashboard', label: 'Home', icon: 'home' },
      { id: 'master-employee', label: 'Employee Master', icon: 'users' },
    ]},
    { group: 'People', items: [
      { id: 'employee', label: 'Employees',      icon: 'users' },
      { id: 'leave-requests', label: 'Leaves', icon: 'calendar' },
      // { id: 'leave',     label: 'All leave',      icon: 'calendar' },
      // { id: 'timesheet',        label: 'All timesheets',  icon: 'clock' },
      { id: 'timesheet-talent', label: 'Timesheets', icon: 'clock' },
    ]},
    { group: 'Approvals', items: [
      { id: 'approve-leave', label: 'Leave queue',      icon: 'inbox', badge: counts.pendingLeaves },
      { id: 'approve-time',  label: 'Timesheet queue',  icon: 'inbox', badge: counts.pendingTimesheets },
    ]},
    { group: 'Personal', items: [
      { id: 'addresses', label: 'Addresses',        icon: 'pin' },
      { id: 'banks',     label: 'Banks',            icon: 'wallet' },
      { id: 'salary',    label: 'Salary',           icon: 'wallet' },
      { id: 'emergency', label: 'Emergency contact', icon: 'user' },
    ]},
    { group: 'Records', items: [
      { id: 'documents', label: 'Documents',  icon: 'doc' },
      { id: 'payslips',  label: 'Payroll',    icon: 'wallet' },
      { id: 'profile',   label: 'My profile', icon: 'user' },
    ]},
    { group: 'Settings', items: [
      { id: 'application-settings', label: 'Application settings', icon: 'settings' },
      { id: 'leave-types', label: 'Leave types', icon: 'settings' },
      { id: 'events', label: 'Events', icon: 'calendar' },
      { id: 'event-templates', label: 'Event templates', icon: 'users' },
      { id: 'venues', label: 'Venues', icon: 'pin' },
    ]},
    { group: 'Security/Privacy', items: [
      { id: 'manager-users',      label: 'User management',     icon: 'users' },
      { id: 'roles-permissions',  label: 'Roles & Permissions', icon: 'shield' },
    ]},
  ],
})

const PAGE_TITLES = {
  dashboard:      'Home',
  leave:          'Time off',
  timesheet:      'Timesheet',
  'approve-leave':'Leave approvals',
  'approve-time': 'Timesheet approvals',
  documents:      'Documents',
  payslips:       'Payslips',
  employee:       'Employee',
  'master-employee': 'Employee Master List',
  profile:        'My profile',
  'application-settings': 'Application Settings',
  'leave-types':  'Leave Types',
  'leave-requests':    'Leaves',
  'my-timesheets':     'My Timesheets',
  'timesheet-talent':  'Timesheet Talent',
  'team-leave-requests': 'Team Leaves',
  'team-timesheets':   'Team Timesheets',
  events:         'Events',
  'event-templates': 'Event Templates',
  venues:         'Venues',
  addresses:      'Addresses',
  banks:          'Bank Details',
  salary:         'Salary Information',
  emergency:      'Emergency Contact',
  'manager-users':      'User Management',
  'roles-permissions':  'Roles & Permissions',
}

const navGroups = computed(() => {
  const nav = getNavStructure(pendingCounts.value)
  // Fallback to employee-basic for unknown roles
  return nav[hrRole.value] || nav['employee-basic'] || nav.employee
})
const pageTitle = computed(() => PAGE_TITLES[hrPage.value] || 'Home')
const browserTitle = computed(() => {
  const base = pageTitle.value
  return base === 'Home' ? 'Meridian HR' : `${base} · Meridian HR`
})
const breadcrumbGroup = computed(() => {
  if (hrRole.value === 'admin') return 'HR Admin'
  if (hrRole.value === 'manager') return 'Manager'
  return 'Workspace'
})

const ROUTE_MAP = {
  dashboard:      'hr.dashboard',
  leave:          'hr.leave',
  timesheet:      'hr.timesheet',
  'approve-leave':'hr.approvals.leave',
  'approve-time': 'hr.approvals.time',
  'manager-users':     'hr.manager-users',
  'roles-permissions': 'hr.roles-permissions',
  documents:      'hr.documents',
  payslips:       'hr.payslips',
  employee:       'hr.employee',
  'master-employee': 'hr.master-employee',
  profile:        'hr.profile',
  'application-settings': 'hr.settings',
  'leave-types':  'hr.leave-types',
  'leave-requests':    'hr.leave-requests',
  'my-timesheets':     'hr.my-timesheets',
  'timesheet-talent':  'hr.timesheet-talent',
  'team-leave-requests': 'hr.leave-requests',
  'team-timesheets':   'hr.timesheet-talent',
  'event-templates': 'hr.event-templates',
  events:         'hr.events',
  venues:         'hr.venues',
  addresses:      'hr.addresses',
  banks:          'hr.banks',
  salary:         'hr.salary',
  emergency:      'hr.emergency',
}

const NOTIFICATIONS = [
  { id: 'n-1', icon: 'check',    title: 'Timesheet for April 2026 was approved', time: '2 hours ago', color: 'ok' },
  { id: 'n-2', icon: 'calendar', title: 'Marcus Chen requested 3 days of leave',  time: 'Yesterday',   color: 'info' },
  { id: 'n-3', icon: 'wallet',   title: 'Payslip for April 2026 is now available', time: 'Apr 30',     color: 'info' },
  { id: 'n-4', icon: 'doc',      title: 'Sign your updated benefits enrolment',    time: 'Apr 28',     color: 'warn' },
]

function navigate(id) {
  const routeName = ROUTE_MAP[id]
  if (routeName) {
    // Add scope=team query parameter for team views
    if (id === 'team-leave-requests' || id === 'team-timesheets') {
      router.get(route(routeName, { scope: 'team' }))
    } else {
      router.get(route(routeName))
    }
  }
}

function logout() {
  router.post(route('logout'))
}

function selectEvent(eventId) {
  eventSelectorOpen.value = false
  if (!eventId) {
    // Admin and manager can clear event selection
    if (isEmployee.value) return
    const message = isAdmin.value ? 'Showing all events' : 'Showing all your events'
    router.post(route('event.clear'), {}, {
      preserveScroll: true,
      onSuccess: () => showToast(message),
    })
  } else {
    router.post(route('event.select'), { event_id: eventId }, {
      preserveScroll: true,
      onSuccess: () => showToast('Event selected'),
    })
  }
}

function showToast(msg) {
  toast.value = msg
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = null }, 3000)
}

function closeNotif(e) {
  if (!e.target.closest?.('.mhr-notif-pop') && !e.target.closest?.('.mhr-notif-trigger')) {
    openNotif.value = false
  }
  if (eventSelectorRef.value && !eventSelectorRef.value.contains(e.target)) {
    eventSelectorOpen.value = false
  }
}

// Auto-hide sidebar on mobile
function handleResize() {
  const isMobile = window.innerWidth <= 768
  if (isMobile) {
    collapsed.value = true
  }
}

// Close sidebar on navigation when on mobile
function handleNavigation(id) {
  navigate(id)
  if (window.innerWidth <= 768) {
    collapsed.value = true
  }
}

// Close sidebar (used by overlay click)
function closeSidebar(e) {
  e?.stopPropagation()
  collapsed.value = true
}

onMounted(() => {
  document.addEventListener('click', closeNotif)
  window.addEventListener('resize', handleResize)
  // Auto-collapse on initial mount if mobile
  handleResize()
  
  // Auto-select first event for employees and managers if no event selected and they have only one event
  if ((isEmployee.value || isManager.value) && !selectedEvent.value && availableEvents.value.length === 1) {
    selectEvent(availableEvents.value[0].id)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', closeNotif)
  window.removeEventListener('resize', handleResize)
})

defineExpose({ showToast })
</script>

<template>
  <Head :title="browserTitle" />
  
  <div class="meridian-app" :data-collapsed="collapsed ? '1' : undefined">

    <!-- Mobile overlay backdrop -->
    <div v-if="!collapsed" class="mhr-sidebar-overlay" @click.stop="closeSidebar"></div>

    <!-- Sidebar -->
    <aside class="mhr-sidebar">
      <div class="mhr-sidebar__brand">
        <div class="mhr-sidebar__brand-mark">m</div>
        <span class="mhr-sidebar__brand-name">Meridian<em>·</em>HR</span>
      </div>

      <div class="mhr-sidebar__content">
        <template v-for="group in navGroups" :key="group.group">
          <div class="mhr-sidebar__group">{{ group.group }}</div>
          <nav class="mhr-sidebar__nav">
            <button
              v-for="item in group.items"
              :key="item.id"
              class="mhr-sidebar__item"
              :aria-current="hrPage === item.id ? 'page' : undefined"
              :title="collapsed ? item.label : undefined"
              @click="handleNavigation(item.id)"
            >
              <AppIcon :name="item.icon" :size="17" class="mhr-sidebar__icon" />
              <span>{{ item.label }}</span>
              <span v-if="item.badge != null" class="mhr-sidebar__badge">{{ item.badge }}</span>
            </button>
          </nav>
        </template>
      </div>

      <div class="mhr-sidebar__footer">
        <div class="mhr-sidebar__user" @click="handleNavigation('profile')">
          <AppAvatar :name="me.name" :c="me.avatarColor" :initials="me.initials" />
          <div class="mhr-sidebar__user-meta">
            <div class="mhr-sidebar__user-name">{{ me.name }}</div>
            <div class="mhr-sidebar__user-role">
              {{ isEmployee ? me.role : hrRole === 'manager' ? 'Manager · ' + me.role : 'Administrator' }}
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main area -->
    <div class="mhr-main">
      <!-- Topbar -->
      <header class="mhr-topbar">
        <button class="mhr-icon-btn" @click="collapsed = !collapsed" :title="collapsed ? 'Expand' : 'Collapse'">
          <AppIcon :name="collapsed ? 'expand' : 'collapse'" />
        </button>

        <div class="mhr-topbar__crumbs">
          <span>{{ breadcrumbGroup }}</span>
          <AppIcon name="chevron" :size="12" />
          <strong>{{ pageTitle }}</strong>
        </div>

        <div class="mhr-topbar__spacer" />

        <!-- Event Selector (lmsx style) -->
        <div v-if="availableEvents.length > 0" class="event-selector" ref="eventSelectorRef">
          <button class="event-selector-btn" @click="eventSelectorOpen = !eventSelectorOpen">
            <AppIcon name="award" :size="14" />
            <span class="event-selector-label">{{ activeEventLabel }}</span>
            <svg class="event-selector-chevron" :class="{ 'rotated': eventSelectorOpen }" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
          </button>

          <transition name="dropdown">
            <div v-if="eventSelectorOpen" class="event-dropdown">
              <div class="event-dropdown-header">Switch Event</div>
              <button
                v-if="!isEmployee"
                class="event-dropdown-item"
                :class="{ 'event-dropdown-item--active': !selectedEvent }"
                @click="selectEvent(null)"
              >
                <span class="event-dropdown-name">{{ isAdmin ? 'All Events' : 'All My Events' }}</span>
                <svg v-if="!selectedEvent" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              </button>
              <button
                v-for="ev in availableEvents"
                :key="ev.id"
                class="event-dropdown-item"
                :class="{ 'event-dropdown-item--active': ev.id === selectedEvent }"
                @click="selectEvent(ev.id)"
              >
                <span class="event-dropdown-name">{{ ev.name }}</span>
                <svg v-if="ev.id === selectedEvent" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              </button>
              <div v-if="!availableEvents.length" class="event-dropdown-empty">No events</div>
            </div>
          </transition>
        </div>

        <div class="mhr-topbar__search">
          <AppIcon name="search" />
          <input
            :placeholder="hrRole === 'admin' ? 'Search people, requests, docs…' : 'Search your records…'"
          />
        </div>

        <div class="mhr-topbar__actions">
          <button class="mhr-icon-btn mhr-notif-trigger" title="Notifications" @click.stop="openNotif = !openNotif">
            <AppIcon name="bell" />
            <span class="mhr-icon-btn__dot" />
          </button>
          <button class="mhr-icon-btn" title="Settings">
            <AppIcon name="cog" />
          </button>
          <button class="mhr-icon-btn" title="Sign out" @click="logout">
            <AppIcon name="logout" :size="16" />
          </button>
        </div>

        <!-- Notifications popover -->
        <div v-if="openNotif" class="mhr-notif-pop">
          <div style="padding:14px 16px;border-bottom:1px solid var(--mhr-line-2);display:flex;align-items:center;">
            <strong style="font-size:13.5px;">Notifications</strong>
            <button class="mhr-btn mhr-btn--ghost mhr-btn--sm" style="margin-left:auto;">Mark all read</button>
          </div>
          <div style="max-height:360px;overflow-y:auto;">
            <div
              v-for="n in NOTIFICATIONS"
              :key="n.id"
              style="display:flex;gap:12px;padding:12px 16px;border-bottom:1px solid var(--mhr-line-2);cursor:pointer;"
            >
              <div style="width:32px;height:32px;border-radius:8px;flex-shrink:0;background:var(--mhr-accent-soft);color:var(--green-700);display:grid;place-items:center;">
                <AppIcon :name="n.icon" :size="15" />
              </div>
              <div style="min-width:0;">
                <div style="font-size:13px;color:var(--mhr-ink);margin-bottom:2px;">{{ n.title }}</div>
                <div style="font-size:11.5px;color:var(--mhr-ink-3);">{{ n.time }}</div>
              </div>
            </div>
          </div>
          <div style="padding:10px 16px;text-align:center;border-top:1px solid var(--mhr-line-2);">
            <a style="font-size:12.5px;color:var(--green-700);font-weight:500;cursor:pointer;">View all activity</a>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="mhr-content">
        <slot :showToast="showToast" />
      </main>
    </div>

    <!-- Toast -->
    <Transition name="mhr-toast-anim">
      <div v-if="toast" class="mhr-toast">
        <AppIcon name="check" />
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<style>
.mhr-toast-anim-enter-active, .mhr-toast-anim-leave-active { transition: opacity 0.2s, transform 0.2s; }
.mhr-toast-anim-enter-from, .mhr-toast-anim-leave-to { opacity: 0; transform: translate(-50%, 8px); }

/* Event Context Banner */
.mhr-event-banner {
  position: sticky;
  top: 0;
  z-index: 90;
  background: var(--mhr-accent);
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--mhr-pad);
  height: 40px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.mhr-event-banner__content {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.mhr-event-banner__label {
  opacity: 0.9;
  font-weight: 400;
}

.mhr-event-banner__name {
  font-weight: 600;
}

.mhr-event-banner__change {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255,255,255,0.15);
  border: none;
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  transition: background 0.15s;
}

.mhr-event-banner__change:hover {
  background: rgba(255,255,255,0.25);
}
</style>
