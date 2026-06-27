<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import $ from "jquery";
import Swal from "sweetalert2";
import { router } from "@inertiajs/vue3";
import Breadcrumb from "primevue/breadcrumb";
import UserFormModal from "@/Components/user/UserFormModal.vue";

// Props kept for backward compat with the Index page – Bootstrap Table ignores them
defineProps({
  rows: { type: Array, default: () => [] },
  totalRecords: { type: Number, default: 0 },
  lazyParams: { type: Object, default: () => ({}) },
});

// ── Table ref ────────────────────────────────────────────────────
const tableRef = ref(null);
const totalRecordsCount = ref(0);

// ── Modal state ──────────────────────────────────────────────────
const showModal = ref(false);
const modalMode = ref("create");
const selectedUser = ref(null);

// ── Breadcrumb data ─────────────────────────────────────────────
const home = ref({
  icon: "pi pi-home",
  url: route("home"),
});

const items = ref([
  { label: "User Management", url: "/users", icon: "pi pi-users" },
]);

// Returns a fixed height on desktop; undefined on mobile (Bootstrap Table ignores undefined)
function getTableHeight() {
  return window.innerWidth < 768 ? undefined : Math.max(300, window.innerHeight - 210);
}


// ── Formatters ───────────────────────────────────────────────────
function statusFormatter(_value, row) {
  if (!row.status_name) return '<span class="text-muted">—</span>';
  const cls = `us-${row.status_color || "secondary"}`;
  return `<span class="us-badge ${cls}">${row.status_name}</span>`;
}

function rolesFormatter(value) {
  if (!value || value.length === 0)
    return '<span class="text-muted fst-italic small">No roles</span>';
  return value
    .map((r) => `<span class="user-role-badge">${r.name}</span>`)
    .join("");
}

function actionFormatter(_value, row) {
  return `
        <div class="d-flex gap-1 justify-content-center">
            <button class="bt-icon-btn bt-edit-btn"
                    data-id="${row.id}"
                    data-name="${row.name}"
                    data-email="${row.email}"
                    data-status="${row.status_id}"
                    title="Edit">
                <i class="fa fa-pencil-alt"></i>
            </button>
            <button class="bt-icon-btn bt-delete-btn"
                    data-id="${row.id}" data-name="${row.name}" title="Delete">
                <i class="fa fa-trash"></i>
            </button>
        </div>`;
}

// ── Bootstrap Table init ──────────────────────────────────────────
function initTable() {
  if (!tableRef.value) return;

  const $table = $(tableRef.value);
  try {
    $table.bootstrapTable("destroy");
  } catch (_) {}

  $table.bootstrapTable({
    url: route("users.data"),
    classes: "table table-borderless",
    method: "get",
    toolbar: "#users-left-toolbar",
    pagination: true,
    sidePagination: "server",
    search: true,
    checkboxHeader: true,
    height: getTableHeight(),
    clickToSelect: true,
    showRefresh: true,
    showColumns: true,
    showToggle: true,
    sortName: "id",
    sortOrder: "desc",
    pageList: [5, 10, 25, 50],
    pageSize: 10,
    iconsPrefix: "fa",
    icons: { refresh: "fa-sync", columns: "fa-list-ul" },
    queryParams(params) {
      return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
      };
    },
    onLoadSuccess(data) {
      totalRecordsCount.value = data.total || 0;
    },
    loadingTemplate() {
      return `<div class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                    </div>`;
    },
    columns: [
      {
        field: "select",
        checkbox: true,
        align: "center",
        width: "40px",
        switchable: false,
        searchable: false,
        sortable: false,
      },
      {
        field: "id",
        title: "ID",
        sortable: true,
        align: "center",
        width: "80px",
        visible: false,
        switchable: true,
      },
      {
        field: "name",
        title: "Name",
        sortable: true,
        switchable: true,
        formatter: (_v, row) =>
          `<span class="fw-semibold text-dark">${row.name ?? "—"}</span>`,
      },
      {
        field: "email",
        title: "Email",
        sortable: true,
        switchable: true,
        formatter: (_v, row) =>
          `<span class="text-muted">${row.email ?? "—"}</span>`,
      },
      {
        field: "status_name",
        title: "Status",
        sortable: true,
        switchable: true,
        formatter: statusFormatter,
      },
      {
        field: "roles",
        title: "Roles",
        sortable: false,
        searchable: false,
        switchable: true,
        formatter: rolesFormatter,
      },
            {
        field: "created_at",
        title: "Created",
        sortable: true,
        switchable: true,
        visible: false,
        formatter: (v) => v ?? "—",
      },
      {
        field: "updated_at",
        title: "Updated",
        sortable: true,
        switchable: true,
        visible: false,
        formatter: (v) => v ?? "—",
      },
      {
        field: "actions",
        title: "Actions",
        align: "center",
        width: "120px",
        formatter: actionFormatter,
        searchable: false,
        sortable: false,
        switchable: false,
      },
    ],
  });

  // Edit – open modal pre-filled with row data
  $table.on("click", ".bt-edit-btn", function () {
    const $btn = $(this);
    openEditModal({
      id: parseInt($btn.data("id")),
      name: String($btn.data("name")),
      email: String($btn.data("email")),
      status_id: $btn.data("status") ?? "",
    });
  });

  // Delete – confirm then Inertia delete
  $table.on("click", ".bt-delete-btn", function () {
    confirmDelete({
      id: parseInt($(this).data("id")),
      name: String($(this).data("name")),
    });
  });
}

function refreshTable() {
  if (!tableRef.value) return;
  $(tableRef.value).bootstrapTable("refresh");
}

// ── Modal ─────────────────────────────────────────────────────────
function openCreateModal() {
  modalMode.value = "create";
  selectedUser.value = null;
  showModal.value = true;
}

function openEditModal(user) {
  modalMode.value = "edit";
  selectedUser.value = user;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedUser.value = null;
}

function handleSaved() {
  closeModal();
  refreshTable();
}

// ── Delete ────────────────────────────────────────────────────────
async function confirmDelete({ id, name }) {
  const result = await Swal.fire({
    title: "Delete user?",
    text: `Are you sure you want to delete "${name}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    confirmButtonColor: "#dc2626",
  });
  if (!result.isConfirmed) return;

  router.delete(route("users.destroy", id), {
    preserveScroll: true,
    onSuccess: () => {
      window.toastr?.success("User deleted successfully");
      refreshTable();
    },
    onError: () => window.toastr?.error("Failed to delete user."),
  });
}

// ── Lifecycle ─────────────────────────────────────────────────────
onMounted(async () => {
  await nextTick();
  initTable();
});

onBeforeUnmount(() => {
  if (!tableRef.value) return;
  try {
    $(tableRef.value).off();
    $(tableRef.value).bootstrapTable("destroy");
  } catch (_) {}
});
</script>

<template>
  <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
    <Breadcrumb :home="home" :model="items" />
  </div>
  <div class="bt-outer">
    <!-- Left pill toolbar injected by Bootstrap Table -->
    <div id="users-left-toolbar" style="display: block">
      <button class="bt-pill-btn" @click="openCreateModal">
        <i class="fa fa-plus me-1"></i> Add User
      </button>
      <div class="record-count-badge">
        <i class="fa fa-users me-1"></i>
        <span class="fw-semibold">{{ totalRecordsCount }}</span>
        <span class="ms-1">{{ totalRecordsCount === 1 ? 'user' : 'users' }}</span>
      </div>
    </div>

    <table ref="tableRef" class="table table-responsive"></table>
  </div>

  <!-- Re-use the existing Inertia-powered UserFormModal -->
  <UserFormModal
    :show="showModal"
    :mode="modalMode"
    :user="selectedUser"
    @close="closeModal"
    @saved="handleSaved"
  />
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   Record count badge
═══════════════════════════════════════════════════════════════ */
.record-count-badge {
  display: inline-flex;
  align-items: center;
  padding: 0 16px;
  height: 38px;
  background: #dcfce7;
  border-radius: 50px;
  color: #16a34a;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid #bbf7d0;
  margin-left: 8px;
}

.record-count-badge i {
  opacity: 0.8;
  font-size: 12px;
}

/* ═══════════════════════════════════════════════════════════════
   Outer card
═══════════════════════════════════════════════════════════════ */
.bt-outer {
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 4px 28px rgba(60, 80, 200, 0.13);
  border: 1px solid #dde3f0;
}

/* ═══════════════════════════════════════════════════════════════
   Transparent table backgrounds
═══════════════════════════════════════════════════════════════ */
:deep(table),
:deep(.fixed-table-container),
:deep(.fixed-table-body),
:deep(.fixed-table-header),
:deep(thead th),
:deep(tbody td),
:deep(tbody tr) {
  background-color: transparent !important;
  --bs-table-bg: transparent;
}

/* ═══════════════════════════════════════════════════════════════
   Gradient toolbar
═══════════════════════════════════════════════════════════════ */
/* :deep(.fixed-table-toolbar) { */
  /* background: linear-gradient(135deg, #1f326e 0%, #2c69ee 55%, #7ec8f8 100%); */
  /* background: linear-gradient(135deg, #1f326e 0%, #043399 55%, #1f326e 100%);
  padding: 14px 20px;
  display: flex !important;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
} */

:deep(.fixed-table-toolbar .bars) {
  order: 1;
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

:deep(.fixed-table-toolbar .search) {
  order: 2;
  flex: 1 1 auto;
  display: flex;
  justify-content: center;
}

:deep(.fixed-table-toolbar .columns) {
  order: 3;
  flex: 0 0 auto;
  margin-left: auto;
  display: flex;
  gap: 8px;
  align-items: center;
}

:deep(.fixed-table-toolbar .search input.form-control) {
  border-radius: 50px;
  border: 1.5px solid rgba(255, 255, 255, 0.55);
  background: rgba(255, 255, 255, 0.18);
  color: #fff;
  padding: 6px 20px;
  min-width: 220px;
  max-width: 340px;
  backdrop-filter: blur(4px);
  transition: border-color 0.2s, background 0.2s;
}

:deep(.fixed-table-toolbar .search input.form-control::placeholder) {
  color: rgba(255, 255, 255, 0.65);
}

:deep(.fixed-table-toolbar .search input.form-control:focus) {
  background: rgba(255, 255, 255, 0.28);
  border-color: rgba(255, 255, 255, 0.9);
  box-shadow: none;
  color: #fff;
  outline: none;
}

/* Circular icon buttons */
:deep(.fixed-table-toolbar .btn) {
  width: 38px;
  height: 38px;
  padding: 0;
  border-radius: 50% !important;
  border: 1.5px solid rgba(255, 255, 255, 0.55);
  background: transparent;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.18s, border-color 0.18s;
}

:deep(.fixed-table-toolbar .btn:hover),
:deep(.fixed-table-toolbar .btn:focus) {
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.9);
  color: #fff;
  box-shadow: none;
}

:deep(.fixed-table-toolbar .btn.dropdown-toggle::after) {
  display: none;
}

/* "Add User" pill */
:deep(.fixed-table-toolbar .bars .bt-pill-btn),
.bt-pill-btn {
  width: auto !important;
  height: 38px;
  padding: 0 20px !important;
  border-radius: 50px !important;
  border: 1.5px solid rgba(255, 255, 255, 0.7) !important;
  background: transparent !important;
  color: #fff !important;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background 0.18s;
  white-space: nowrap;
}

:deep(.fixed-table-toolbar .bars .bt-pill-btn:hover),
.bt-pill-btn:hover {
  background: rgba(255, 255, 255, 0.18) !important;
}

/* ═══════════════════════════════════════════════════════════════
   Dark column-visibility dropdown
═══════════════════════════════════════════════════════════════ */
:deep(.fixed-table-toolbar .dropdown-menu) {
  background: #2a3250 !important;
  border: none !important;
  border-radius: 14px !important;
  padding: 14px 16px !important;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35) !important;
  min-width: 160px;
}

:deep(.fixed-table-toolbar .dropdown-item),
:deep(.fixed-table-toolbar .dropdown-menu label) {
  color: #e8edf8 !important;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 6px 0;
  background: transparent !important;
}

:deep(.fixed-table-toolbar .dropdown-menu .form-check-input) {
  border-radius: 4px;
  border-color: #5b8df6;
}

:deep(.fixed-table-toolbar .dropdown-menu .form-check-input:checked) {
  background-color: #5b8df6;
  border-color: #5b8df6;
}

/* ═══════════════════════════════════════════════════════════════
   Table head / body
═══════════════════════════════════════════════════════════════ */
:deep(thead th) {
  background: #fff !important;
  color: #1a1a2e;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-bottom: 2px solid #eaecf6;
  padding: 13px 16px;
}

:deep(.sortable .both),
:deep(.sortable .asc),
:deep(.sortable .desc) {
  color: #8899bb;
}

:deep(table) {
  margin-bottom: 0 !important;
}

:deep(tbody td) {
  vertical-align: middle;
  font-size: 14px;
  padding: 12px 16px;
  color: #4a5568;
  border-bottom: 1px solid #f2f4fb;
}

:deep(tbody tr:hover td) {
  background: #f5f7ff !important;
}

/* ═══════════════════════════════════════════════════════════════
   Status badges
═══════════════════════════════════════════════════════════════ */
:deep(.us-badge) {
  display: inline-flex;
  align-items: center;
  padding: 3px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid;
}

:deep(.us-success) {
  background: #dcfce7;
  color: #16a34a;
  border-color: #bbf7d0;
}
:deep(.us-danger) {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}
:deep(.us-warning) {
  background: #fef9c3;
  color: #ca8a04;
  border-color: #fde68a;
}
:deep(.us-info) {
  background: #dbeafe;
  color: #2563eb;
  border-color: #bfdbfe;
}
:deep(.us-secondary) {
  background: #f1f5f9;
  color: #64748b;
  border-color: #e2e8f0;
}
:deep(.us-contrast) {
  background: #1e293b;
  color: #fff;
  border-color: #334155;
}

:deep(.user-role-badge) {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  background: #eef2ff;
  color: #3a5bd9;
  border: 1px solid #c7d2fe;
  margin: 2px 3px 2px 0;
}

/* ═══════════════════════════════════════════════════════════════
   Pagination
═══════════════════════════════════════════════════════════════ */
:deep(.fixed-table-pagination) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  border-top: 1px solid #eaecf6;
}

:deep(.fixed-table-pagination .pagination .page-link) {
  border: none;
  border-radius: 50% !important;
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: #4a5568;
  background: transparent;
  margin: 0 2px;
  transition: background 0.15s;
}

:deep(.fixed-table-pagination .pagination .page-item.active .page-link) {
  /* background: #3a5bd9 !important; */
  background: var(--pagination-active-bg) !important;
  color: #fff !important;
  border-radius: 50% !important;
  border: none;
}

:deep(
    .fixed-table-pagination .pagination .page-item:hover:not(.active) .page-link
  ) {
  background: #eaecf6;
}

:deep(.fixed-table-pagination .page-size) {
  border-radius: 50px;
  border: 1.5px solid #dde3f0;
  padding: 4px 12px;
  font-size: 13px;
  color: #4a5568;
}

/* ═══════════════════════════════════════════════════════════════
   Action icon buttons
═══════════════════════════════════════════════════════════════ */
:deep(.bt-icon-btn) {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  cursor: pointer;
  transition: background 0.15s;
}

:deep(.bt-edit-btn) {
  background: #fff7e6;
  border-color: #fde7b0;
  color: #d97706;
}
:deep(.bt-edit-btn:hover) {
  background: #fef3c7;
}
:deep(.bt-delete-btn) {
  background: #fff1f2;
  border-color: #fecdd3;
  color: #dc2626;
}
:deep(.bt-delete-btn:hover) {
  background: #ffe4e6;
}

/* ═══════════════════════════════════════════════════════════════
   Responsive
═══════════════════════════════════════════════════════════════ */
@media (max-width: 640px) {
  :deep(.fixed-table-toolbar) {
    flex-wrap: wrap;
  }
  :deep(.fixed-table-toolbar .search input.form-control) {
    min-width: 160px;
  }
}
</style>
