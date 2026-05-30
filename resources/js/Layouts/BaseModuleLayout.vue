<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, usePage, Link, router } from '@inertiajs/vue3'
import { usePermissions } from '@/Composables/usePermissions'
import AppIcon from '@/Components/MeridianHR/AppIcon.vue'
import AppAvatar from '@/Components/MeridianHR/AppAvatar.vue'
import '@/../css/meridian.css'

const props = defineProps({
  /** Module identifier ('hr', 'payroll', 'procurement', 'recruiting') */
  moduleKey: {
    type: String,
    required: true
  },
  /** Display name for the module */
  moduleName: {
    type: String,
    required: true
  },
  /** Icon for the module (used in breadcrumbs) */
  moduleIcon: {
    type: String,
    default: 'home'
  },
  /** Navigation structure: array of { group, items: [{ id, label, icon, badge, route }] } */
  navigation: {
    type: Array,
    required: true
  },
  /** Page title for browser tab */
  pageTitle: {
    type: String,
    default: ''
  },
  /** Breadcrumb group name */
  breadcrumbGroup: {
    type: String,
    default: ''
  },
  /** Current page identifier (for active state) */
  currentPage: {
    type: String,
    default: ''
  },
})

const page = usePage()
const { modules } = usePermissions()

const collapsed = ref(false)
const openNotif = ref(false)
const toast = ref(null)
let toastTimer = null

const auth = computed(() => page.props.auth || {})
const me = computed(() => page.props.me || { 
  name: auth.value.user?.name || 'User', 
  initials: 'U', 
  avatarColor: 0, 
  role: null,
  systemRole: 'Employee',
  systemRoles: ['Employee']
})

// Responsive handling
const handleResize = () => {
  if (window.innerWidth < 1024) {
    collapsed.value = true
  }
}

const closeSidebar = () => {
  if (window.innerWidth < 1024) {
    collapsed.value = true
  }
}

// Navigation
const handleNavigation = (item) => {
  if (window.innerWidth < 1024) {
    collapsed.value = true
  }
  if (item.route) {
    const url = item.query ? route(item.route, item.query) : route(item.route)
    router.visit(url)
  }
}

// Toast system
function showToast(message, type = 'success') {
  clearTimeout(toastTimer)
  toast.value = { message, type }
  toastTimer = setTimeout(() => {
    toast.value = null
  }, 3500)
}

function closeNotif(e) {
  e?.stopPropagation()
  openNotif.value = false
}

function logout() {
  router.post(route('logout'))
}

onMounted(() => {
  document.addEventListener('click', closeNotif)
  window.addEventListener('resize', handleResize)
  handleResize()
})

onUnmounted(() => {
  document.removeEventListener('click', closeNotif)
  window.removeEventListener('resize', handleResize)
})

defineExpose({ showToast })
</script>

<template>
  <Head :title="pageTitle || moduleName" />
  
  <div class="meridian-app" :data-collapsed="collapsed ? '1' : undefined">

    <!-- Mobile overlay backdrop -->
    <div v-if="!collapsed" class="mhr-sidebar-overlay" @click.stop="closeSidebar"></div>

    <!-- Sidebar -->
    <aside class="mhr-sidebar">
      <div class="mhr-sidebar__brand">
        <div class="mhr-sidebar__brand-mark">m</div>
        <span class="mhr-sidebar__brand-name">Meridian<em>·</em>{{ moduleName }}</span>
      </div>

      <div class="mhr-sidebar__content">
        <!-- Module-specific navigation passed via props -->
        <template v-for="group in navigation" :key="group.group">
          <div class="mhr-sidebar__group">{{ group.group }}</div>
          <nav class="mhr-sidebar__nav">
            <button
              v-for="item in group.items"
              :key="item.id"
              class="mhr-sidebar__item"
              :aria-current="currentPage === item.id ? 'page' : undefined"
              :title="collapsed ? item.label : undefined"
              @click="handleNavigation(item)"
            >
              <AppIcon :name="item.icon" :size="17" class="mhr-sidebar__icon" />
              <span>{{ item.label }}</span>
              <span v-if="item.badge != null" class="mhr-sidebar__badge">{{ item.badge }}</span>
            </button>
          </nav>
        </template>
      </div>

      <div class="mhr-sidebar__footer">
        <div class="mhr-sidebar__user" @click="handleNavigation({ id: 'profile', route: 'hr.profile' })">
          <AppAvatar :name="me.name" :c="me.avatarColor" :initials="me.initials" />
          <div class="mhr-sidebar__user-meta">
            <div class="mhr-sidebar__user-name">{{ me.name }}</div>
            <div class="mhr-sidebar__user-role">
              <span>{{ me.systemRole }}<template v-if="me.role"> · {{ me.role }}</template></span>
              <div v-if="me.systemRoles && me.systemRoles.length > 1" class="role-badges">
                <span 
                  v-for="(role, idx) in me.systemRoles.slice(1)" 
                  :key="idx" 
                  class="role-badge"
                >
                  {{ role }}
                </span>
              </div>
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
          <span>{{ breadcrumbGroup || moduleName }}</span>
          <AppIcon name="chevron" :size="12" />
          <strong>{{ pageTitle }}</strong>
        </div>

        <div class="mhr-topbar__spacer" />

        <!-- Module-specific topbar content (slot) -->
        <slot name="topbar" />

        <!-- Module Switcher (if user has multiple modules) -->
        <div v-if="modules.length > 1" class="module-switcher">
          <Link 
            v-for="mod in modules" 
            :key="mod.key"
            :href="mod.url"
            class="module-tab"
            :class="{ 'module-tab--active': moduleKey === mod.key }"
          >
            <AppIcon :name="mod.icon" :size="14" />
            <span>{{ mod.name }}</span>
          </Link>
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
        <div v-if="openNotif" class="mhr-notif-pop" @click.stop>
          <div style="padding:14px 16px;border-bottom:1px solid var(--mhr-line-2);display:flex;align-items:center;">
            <strong style="flex:1;font-size:14px;">Notifications</strong>
            <button class="mhr-btn mhr-btn--ghost" style="padding:4px 8px;">Mark all read</button>
          </div>
          <div style="padding:10px;">
            <div style="padding:20px;text-align:center;color:var(--mhr-ink-3);font-size:13px;">
              No new notifications
            </div>
          </div>
        </div>
      </header>

      <!-- Main content area -->
      <div class="mhr-content">
        <slot />
      </div>
    </div>

    <!-- Toast notification -->
    <Transition name="toast">
      <div v-if="toast" class="mhr-toast" :class="`mhr-toast--${toast.type}`">
        <AppIcon :name="toast.type === 'success' ? 'check' : 'alert'" :size="16" />
        {{ toast.message }}
      </div>
    </Transition>

    <!-- Overlays slot (modals, etc.) -->
    <slot name="overlays" />
  </div>
</template>

<style scoped>
/* Module switcher styling */
.module-switcher {
  display: flex;
  gap: 4px;
  margin-right: 16px;
  padding: 4px;
  background: var(--mhr-surface);
  border-radius: 6px;
  border: 1px solid var(--mhr-line);
}

.module-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 13px;
  font-weight: 500;
  color: var(--mhr-ink-2);
  border-radius: 4px;
  transition: all 0.15s ease;
  text-decoration: none;
  cursor: pointer;
}

.module-tab:hover {
  background: var(--mhr-bg);
  color: var(--mhr-ink);
}

.module-tab--active {
  background: var(--mhr-accent);
  color: white;
}

.module-tab--active:hover {
  background: var(--mhr-accent);
  color: white;
}

@media (max-width: 768px) {
  .module-switcher {
    display: none; /* Hide on mobile, show in separate menu */
  }
}

/* Role badges for multi-role users */
.role-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}

.role-badge {
  display: inline-block;
  padding: 2px 6px;
  font-size: 10px;
  font-weight: 500;
  color: var(--mhr-accent);
  background: var(--mhr-accent-soft);
  border-radius: 3px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
</style>
