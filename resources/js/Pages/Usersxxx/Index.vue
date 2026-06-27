<script setup>
import { Head } from "@inertiajs/vue3";
import { onMounted, onUnmounted } from "vue";
import HeaderNavbar from "@/Components/HeaderNavbar.vue";
import VerticalMenu from "@/Components/VerticalMenu.vue";
import UsersBtTable from "@/Components/user/UsersBtTable.vue";

// onMounted(() => {
//   console.log("Users Index Page Mounted");
//   document.body.classList.add("none-login-bg");
// });

// onUnmounted(() => {
//   document.body.classList.remove("none-login-bg");
// });

defineProps({
  users: Array,
  totalRecords: Number,
  lazyParams: Object,
});
</script>

<template>
  <Head title="Users - Vue" />

  <div id="layout-wrapper">
    <header id="page-topbar">
      <HeaderNavbar />
    </header>

    <div class="vertical-menu">
      <VerticalMenu />
    </div>

    <div class="main-content">
      <div class="page-content">
        <div class="container-fluid">
          <UsersBtTable
            :rows="users"
            :totalRecords="totalRecords"
            :lazyParams="lazyParams"
          />
        </div>
      </div>
    </div>
  </div>
</template>