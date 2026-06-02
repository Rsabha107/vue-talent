<script setup>
import { Head } from '@inertiajs/vue3'
import ConfirmationCard from '@/Components/ConfirmationCard.vue'

defineProps({
  icon: { type: String, default: 'bx bx-mail-send' },
  iconBg: { type: String, default: 'bg-light' },
  iconColor: { type: String, default: 'text-primary' },
  title: { type: String, default: 'Success!' },
  message: { type: String, default: '' },
  buttonText: { type: String, default: 'Back to Home' },
  buttonHref: { type: String, default: '/' },
  buttonVariant: { type: String, default: 'success' },
})
</script>

<template>
  <Head title="Confirmation" />
  <div class="account-pages my-5 pt-sm-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
          <ConfirmationCard
            :icon="icon"
            :icon-bg="iconBg"
            :icon-color="iconColor"
            :title="title"
            :message="message"
            :button-text="buttonText"
            :button-href="buttonHref"
            :button-variant="buttonVariant"
          />
        </div>
      </div>
    </div>
  </div>
</template>
