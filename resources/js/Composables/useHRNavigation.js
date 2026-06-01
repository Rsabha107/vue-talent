import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { usePermissions } from './usePermissions'

/**
 * HR Module Navigation Builder
 * 
 * Dynamically builds navigation structure based on user permissions.
 * Replaces hardcoded hrRole-based navigation with permission-driven approach.
 * 
 * @returns {Object} Navigation structure and utilities
 */
export function useHRNavigation() {
  const page = usePage()
  const { can, hasRole } = usePermissions()
  
  const hrPage = computed(() => page.props.hrPage || 'dashboard')
  const pendingCounts = computed(() => page.props.pendingCounts || { pendingLeaves: 0, pendingTimesheets: 0 })
  const availableEvents = computed(() => page.props.availableEvents || [])
  
  // Legacy hrRole fallback during transition period
  const hrRole = computed(() => page.props.hrRole || 'employee')
  const isAdmin = computed(() => hrRole.value === 'admin' || hasRole('admin'))
  const isManager = computed(() => hrRole.value === 'manager' || hasRole('manager'))
  
  // Check if manager has no events - they should not see team/approval sections
  const isManagerWithoutEvents = computed(() => isManager.value && !isAdmin.value && availableEvents.value.length === 0)
  
  /**
   * Build navigation structure based on permissions
   * Returns array of { group, items: [{ id, label, icon, badge, route }] }
   */
  const navigation = computed(() => {
    const nav = []
    const counts = pendingCounts.value
    
    // ═════════════════════════════════════════════════════════════════
    // Workspace Section (Personal Self-Service - Everyone)
    // ═════════════════════════════════════════════════════════════════
    const workspace = {
      group: 'Workspace',
      items: []
    }
    
    workspace.items.push({
      id: 'dashboard',
      label: 'Home',
      icon: 'home',
      route: 'hr.dashboard'
    })
    
    // Hide personal leaves/timesheets for managers without events
    if (can.value.viewOwnLeaves && !isManagerWithoutEvents.value) {
      workspace.items.push({
        id: 'my-leaves',
        label: 'My leaves',
        icon: 'inbox',
        route: 'hr.my-leaves'
      })
    }
    
    if (can.value.viewOwnTimesheets && !isManagerWithoutEvents.value) {
      workspace.items.push({
        id: 'my-timesheets',
        label: 'My timesheets',
        icon: 'inbox',
        route: 'hr.my-timesheets-view'
      })
    }
    
    // Managers see Emergency contact and Addresses in Workspace
    if (isManager.value && !isAdmin.value) {
      workspace.items.push({
        id: 'emergency',
        label: 'Emergency contact',
        icon: 'user',
        route: 'hr.emergency'
      })
      
      workspace.items.push({
        id: 'addresses',
        label: 'My address',
        icon: 'pin',
        route: 'hr.addresses'
      })
    }
    
    nav.push(workspace)
    
    // ═════════════════════════════════════════════════════════════════
    // Approvals Section (Manager Only - Hidden if no events assigned)
    // ═════════════════════════════════════════════════════════════════
    if ((can.value.approveLeaves || can.value.approveTimesheets) && !isManagerWithoutEvents.value) {
      const approvals = {
        group: 'Approvals',
        items: []
      }
      
      if (can.value.approveLeaves) {
        approvals.items.push({
          id: 'approve-leave',
          label: 'Leaves',
          icon: 'inbox',
          badge: counts.pendingLeaves,
          route: 'hr.approvals.leave'
        })
      }
      
      if (can.value.approveTimesheets) {
        approvals.items.push({
          id: 'approve-time',
          label: 'Timesheets',
          icon: 'inbox',
          badge: counts.pendingTimesheets,
          route: 'hr.approvals.time'
        })
      }
      
      nav.push(approvals)
    }
    
    // ═════════════════════════════════════════════════════════════════
    // Team Section (Manager Read-Only Views - Hidden if no events assigned)
    // ═════════════════════════════════════════════════════════════════
    if ((can.value.viewTeamLeaves || can.value.viewTeamTimesheets) && !isAdmin.value && !isManagerWithoutEvents.value) {
      const team = {
        group: 'Team',
        items: []
      }
      
      if (can.value.viewTeamLeaves) {
        team.items.push({
          id: 'team-leaves',
          label: 'Team leaves',
          icon: 'calendar',
          route: 'hr.team-leaves'
        })
      }
      
      if (can.value.viewTeamTimesheets) {
        team.items.push({
          id: 'team-timesheets',
          label: 'Team timesheets',
          icon: 'clock',
          route: 'hr.team-timesheets'
        })
      }
      
      nav.push(team)
    }
    
    // ═════════════════════════════════════════════════════════════════
    // People Section (Admin Only)
    // ═════════════════════════════════════════════════════════════════
    if (can.value.manageEmployees || can.value.viewAllData) {
      const people = {
        group: 'People',
        items: []
      }
      
      if (can.value.manageEmployees) {
        people.items.push({
          id: 'employee',
          label: 'Employees',
          icon: 'users',
          route: 'hr.employee'
        })
      }
      
      if (can.value.viewAllData) {
        people.items.push({
          id: 'all-leaves',
          label: 'All leaves',
          icon: 'calendar',
          route: 'hr.all-leaves'
        })
        
        people.items.push({
          id: 'all-timesheets',
          label: 'All timesheets',
          icon: 'clock',
          route: 'hr.all-timesheets'
        })
      }
      
      nav.push(people)
    }
    
    // ═════════════════════════════════════════════════════════════════
    // Personal Section (Everyone except Managers - Own Data)
    // ═════════════════════════════════════════════════════════════════
    // Extended access (employee-full, manager, admin)
    const hasExtendedAccess = hrRole.value === 'employee-full' || isManager.value || isAdmin.value
    
    // Managers don't see Personal section - their items are in Workspace
    if (!isManager.value || isAdmin.value) {
      const personal = {
        group: 'Personal',
        items: []
      }
      
      // Extended access (employee-full, admin)
      if (hasExtendedAccess) {
        personal.items.push({
          id: 'addresses',
          label: 'Addresses',
          icon: 'pin',
          route: 'hr.addresses'
        })
        
        personal.items.push({
          id: 'banks',
          label: 'Banks',
          icon: 'wallet',
          route: 'hr.banks'
        })
        
        personal.items.push({
          id: 'salary',
          label: 'Salary',
          icon: 'wallet',
          route: 'hr.salary'
        })
      }
      
      personal.items.push({
        id: 'emergency',
        label: 'Emergency contacts',
        icon: 'user',
        route: 'hr.emergency'
      })
      
      nav.push(personal)
    }
    
    // ═════════════════════════════════════════════════════════════════
    // Records Section (Employee-Full and Above)
    // ═════════════════════════════════════════════════════════════════
    if (hasExtendedAccess) {
      const records = {
        group: 'Records',
        items: []
      }
      
      records.items.push({
        id: 'documents',
        label: isManager.value && !isAdmin.value ? 'My documents' : 'Documents',
        icon: 'doc',
        route: 'hr.documents'
      })
      
      // Show payslips if user has permission
      if (can.value.viewPayslips) {
        records.items.push({
          id: 'payslips',
          label: 'My payslip',
          icon: 'wallet',
          route: 'hr.payslips'
        })
      }
      
      records.items.push({
        id: 'profile',
        label: 'My profile',
        icon: 'user',
        route: 'hr.profile'
      })
      
      nav.push(records)
    } else {
      // Basic employees get documents, payslips (if permission), and profile in Personal section
      const personalSection = nav.find(section => section.group === 'Personal')
      if (personalSection) {
        personalSection.items.push({
          id: 'documents',
          label: 'My documents',
          icon: 'doc',
          route: 'hr.documents'
        })
        
        // Show payslips if user has permission
        if (can.value.viewPayslips) {
          personalSection.items.push({
            id: 'payslips',
            label: 'My payslip',
            icon: 'wallet',
            route: 'hr.payslips'
          })
        }
        
        personalSection.items.push({
          id: 'profile',
          label: 'My profile',
          icon: 'user',
          route: 'hr.profile'
        })
      }
    }
    
    // ═════════════════════════════════════════════════════════════════
    // Settings Section (Admin Only)
    // ═════════════════════════════════════════════════════════════════
    if (can.value.manageLeaveTypes || isAdmin.value) {
      const settings = {
        group: 'Settings',
        items: []
      }
      
      // Setup - Events & Venues management
      if (isAdmin.value) {
        settings.items.push({
          id: 'setup',
          label: 'Setup',
          icon: 'settings',
          route: 'hr.setup'
        })
      }
      
      settings.items.push({
        id: 'application-settings',
        label: 'Application settings',
        icon: 'settings',
        route: 'hr.settings'
      })
      
      if (can.value.manageLeaveTypes) {
        settings.items.push({
          id: 'leave-types',
          label: 'Leave types',
          icon: 'settings',
          route: 'hr.leave-types'
        })
      }
      
      settings.items.push({
        id: 'event-templates',
        label: 'Event templates',
        icon: 'users',
        route: 'hr.event-templates'
      })
      
      nav.push(settings)
    }
    
    // ═════════════════════════════════════════════════════════════════
    // Security Section (Admin Only)
    // ═════════════════════════════════════════════════════════════════
    if (isAdmin.value) {
      const security = {
        group: 'Security/Privacy',
        items: [
          {
            id: 'manager-users',
            label: 'User management',
            icon: 'users',
            route: 'hr.manager-users'
          },
          {
            id: 'roles-permissions',
            label: 'Roles & Permissions',
            icon: 'shield',
            route: 'hr.roles-permissions'
          }
        ]
      }
      
      nav.push(security)
    }
    
    return nav
  })
  
  /**
   * Page title mapping
   */
  const PAGE_TITLES = {
    dashboard: 'Home',
    leave: 'Time off',
    timesheet: 'Timesheet',
    'approve-leave': 'Leave approvals',
    'approve-time': 'Timesheet approvals',
    documents: 'Documents',
    payslips: 'My Payslip',
    employee: 'Employees',
    profile: 'My profile',
    settings: 'Application Settings',
    'application-settings': 'Application Settings',
    'leave-types': 'Leave Types',
    'leave-requests': 'Leaves',
    'my-leaves': 'My Leaves',
    'my-timesheets': 'My Timesheets',
    'my-timesheets-view': 'My Timesheets',
    'team-leaves': 'Team Leaves',
    'team-timesheets': 'Team Timesheets',
    'all-leaves': 'All Leaves',
    'all-timesheets': 'All Timesheets',
    'timesheet-talent': 'Timesheet Talent',
    'team-leave-requests': 'Team Leaves',
    'team-timesheets': 'Team Timesheets',
    setup: 'Setup',
    events: 'Events',
    'event-templates': 'Event Templates',
    venues: 'Venues',
    addresses: 'Addresses',
    banks: 'Bank Details',
    salary: 'Salary Information',
    emergency: 'Emergency Contact',
    'manager-users': 'User Management',
    'roles-permissions': 'Roles & Permissions',
  }
  
  /**
   * Get page title for current page
   */
  const pageTitle = computed(() => PAGE_TITLES[hrPage.value] || 'Meridian HR')
  
  /**
   * Get breadcrumb group for current page
   */
  const breadcrumbGroup = computed(() => {
    const currentPage = hrPage.value
    for (const section of navigation.value) {
      if (section.items.some(item => item.id === currentPage)) {
        return section.group
      }
    }
    return 'Meridian HR'
  })
  
  return {
    navigation,
    pageTitle,
    breadcrumbGroup,
    hrPage,
  }
}
