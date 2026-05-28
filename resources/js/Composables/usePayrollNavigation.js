import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { usePermissions } from './usePermissions'

/**
 * Payroll Module Navigation Builder
 * 
 * Dynamically builds navigation structure based on payroll permissions.
 * 
 * @returns {Object} Navigation structure and utilities
 */
export function usePayrollNavigation() {
  const page = usePage()
  const { can } = usePermissions()
  
  const payrollPage = computed(() => page.props.payrollPage || 'dashboard')
  const pendingCounts = computed(() => page.props.pendingCounts || { 
    pendingPayrollTimesheets: 0,
    missingTimesheets: 0 
  })
  
  /**
   * Build navigation structure based on permissions
   * Returns array of { group, items: [{ id, label, icon, badge, route }] }
   */
  const navigation = computed(() => {
    const nav = []
    const counts = pendingCounts.value
    
    // ═════════════════════════════════════════════════════════════════
    // Payroll Section (Dashboard)
    // ═════════════════════════════════════════════════════════════════
    const payroll = {
      group: 'Payroll',
      items: []
    }
    
    payroll.items.push({
      id: 'dashboard',
      label: 'Dashboard',
      icon: 'home',
      route: 'payroll.dashboard'
    })
    
    nav.push(payroll)
    
    // ═════════════════════════════════════════════════════════════════
    // Review Section (Timesheet Final Approval)
    // ═════════════════════════════════════════════════════════════════
    if (can.value.payrollReviewTimesheets || can.value.payrollApproveTimesheets) {
      const review = {
        group: 'Review',
        items: []
      }
      
      review.items.push({
        id: 'timesheet-review',
        label: 'Timesheet review',
        icon: 'clock',
        badge: counts.pendingPayrollTimesheets,
        route: 'payroll.timesheets.review'
      })
      
      review.items.push({
        id: 'missing-timesheets',
        label: 'Missing timesheets',
        icon: 'alert',
        badge: counts.missingTimesheets,
        route: 'payroll.timesheets.missing'
      })
      
      review.items.push({
        id: 'all-timesheets',
        label: 'All timesheets',
        icon: 'clock',
        route: 'payroll.timesheets.all'
      })
      
      nav.push(review)
    }
    
    // ═════════════════════════════════════════════════════════════════
    // Payments Section (Payment Processing)
    // ═════════════════════════════════════════════════════════════════
    if (can.value.payrollProcessPayments) {
      const payments = {
        group: 'Payments',
        items: []
      }
      
      payments.items.push({
        id: 'payment-batches',
        label: 'Payment batches',
        icon: 'wallet',
        route: 'payroll.payment-batches.index'
      })
      
      payments.items.push({
        id: 'bank-files',
        label: 'Bank files',
        icon: 'doc',
        route: 'payroll.bank-files.index'
      })
      
      nav.push(payments)
    }
    
    return nav
  })
  
  /**
   * Get page title for current route
   */
  const pageTitle = computed(() => {
    const titleMap = {
      'dashboard': 'Payroll Dashboard',
      'timesheet-review': 'Timesheet Review',
      'missing-timesheets': 'Missing Timesheets',
      'all-timesheets': 'All Timesheets',
      'payment-batches': 'Payment Batches',
      'bank-files': 'Bank Files',
    }
    return titleMap[payrollPage.value] || 'Payroll'
  })
  
  /**
   * Get breadcrumb group for current route
   */
  const breadcrumbGroup = computed(() => {
    const groupMap = {
      'dashboard': 'Payroll',
      'timesheet-review': 'Review',
      'missing-timesheets': 'Review',
      'all-timesheets': 'Review',
      'payment-batches': 'Payments',
      'bank-files': 'Payments',
    }
    return groupMap[payrollPage.value] || null
  })
  
  return {
    navigation,
    pageTitle,
    breadcrumbGroup,
    payrollPage
  }
}
