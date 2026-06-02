<script setup>
console.log("LOGIN FILE LOADED");
import { onMounted, onUnmounted } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AuthFooter from "@/Components/AuthFooter.vue";
import PasswordInput from "@/Components/PasswordInput.vue";

// onMounted(() => {
//   console.log("Login Page Mounted");
//   document.body.classList.add("login-bg");
// });

// onUnmounted(() => {
//   document.body.classList.remove("login-bg");
// });

const validated = ref(false);

const props = defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
  remember: {
    type: Boolean,
  },
});

// local copy
const canReset = ref(props.canResetPassword);
const remember = ref(props.remember);

// now you can change it
canReset.value = true;
remember.value = false;

const showPassword = ref(false);

const form = useForm({
  email: "",
  password: "",
  remember: props.remember || false,
});

function submit(e) {
  const htmlForm = e.currentTarget;

  if (!htmlForm.checkValidity()) {
    e.preventDefault();
    e.stopPropagation();
    validated.value = true;
    return;
  }

  validated.value = true;

  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
}

// give me the current year
const currentYear = new Date().getFullYear();
</script>
<template>
  <div class="account-pages my-5 pt-sm-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
          <div class="card overflow-hidden">
            <div class="bg-primary-subtle">
              <div class="row">
                <div class="col-7">
                  <div class="text-primary p-4">
                    <h5 class="text-primary">Welcome Back !</h5>
                    <p>Sign in to continue.</p>
                  </div>
                </div>
                <div class="col-5 align-self-end">
                  <img
                    alt=""
                    class="img-fluid"
                    src="/assets/images/profile-img.png"
                  />
                </div>
              </div>
            </div>
            <div class="card-body pt-0">
              <div class="auth-logo">
                <!-- <a class="auth-logo-light" href="index.html">
                  <div class="avatar-md profile-user-wid mb-4">
                    <span class="avatar-title rounded-circle bg-light">
                      <img
                        alt=""
                        class="rounded-circle"
                        height="34"
                        src="/assets/images/logo-light.svg"
                      />
                    </span>
                  </div>
                </a> -->
                <Link class="auth-logo-dark" :href="route('mypage')">
                  <div class="avatar-md profile-user-wid mb-4">
                    <span class="avatar-title rounded-circle bg-light">
                      <img
                        alt=""
                        class="rounded-circle"
                        height="34"
                        src="/assets/images/logo.svg"
                      />
                    </span>
                  </div>
                </Link>
              </div>
              <div class="p-2">
                <!-- <form
                  @submit.prevent="submit"
                  class="form-horizontal"
                  :class="{ 'was-validated': validated }"
                > -->
                <form
                  @submit.prevent="submit"
                  class="form-horizontal needs-validation"
                  :class="{ 'was-validated': validated }"
                  novalidate
                >
                  <div
                    v-if="status"
                    class="alert alert-success"
                    role="alert"
                  >
                    {{ status }}
                  </div>

                  <div
                    v-if="$page.props.flash?.error"
                    class="alert alert-danger"
                    role="alert"
                  >
                    {{ $page.props.flash.error }}
                  </div>

                  <div
                    v-if="form.errors.email"
                    class="alert alert-danger"
                    role="alert"
                  >
                    {{ form.errors.email }}
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="email"> Email </label>
                    <input
                      class="form-control"
                      id="email"
                      placeholder="Enter email"
                      type="email"
                      v-model="form.email"
                      required
                      :class="{ 'is-invalid': form.errors.email }"
                    />
                    <div class="invalid-feedback" v-if="form.errors.email">
                    </div>
                    <div class="invalid-feedback" v-else>
                      Please enter a valid email.
                    </div>
                  </div>

                  <PasswordInput
                    v-model="form.password"
                    :error="form.errors.password"
                    label="Password"
                    placeholder="Enter password"
                    id="password"
                  />

                  <div class="form-check" v-if="remember">
                    <input
                      class="form-check-input"
                      id="remember-check"
                      type="checkbox"
                      v-model="form.remember"
                    />
                    <label class="form-check-label" for="remember-check">
                      Remember me
                    </label>
                  </div>
                  <div class="mt-3 d-grid">
                    <button
                      type="submit"
                      class="btn btn-primary"
                      :disabled="form.processing"
                    >
                      {{ form.processing ? "Signing in..." : "Login" }}
                    </button>
                  </div>

                  <!-- Microsoft SSO Button -->
                  <div class="mt-3">
                    <div class="position-relative">
                      <div class="text-center" style="position: relative;">
                        <div style="position: absolute; top: 50%; left: 0; right: 0; border-top: 1px solid #e0e0e0; z-index: 0;"></div>
                        <span style="background: white; padding: 0 12px; position: relative; z-index: 1; color: #6c757d; font-size: 13px;">OR</span>
                      </div>
                    </div>
                  </div>

                  <div class="mt-3 d-grid">
                    <a
                      :href="route('auth.microsoft')"
                      class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
                      style="padding: 10px;"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 23 23">
                        <path fill="#f25022" d="M0 0h11v11H0z"/>
                        <path fill="#00a4ef" d="M12 0h11v11H12z"/>
                        <path fill="#7fba00" d="M0 12h11v11H0z"/>
                        <path fill="#ffb900" d="M12 12h11v11H12z"/>
                      </svg>
                      <span>Sign in with Microsoft</span>
                    </a>
                  </div>

                  <div class="mt-4 text-center" style="display: none;">
                    <h5 class="font-size-14 mb-3">Sign in with</h5>
                    <ul class="list-inline">
                      <li class="list-inline-item">
                        <a
                          class="social-list-item bg-primary text-white border-primary"
                          href="javascript::void()"
                        >
                          <i class="mdi mdi-facebook"> </i>
                        </a>
                      </li>
                      <li class="list-inline-item">
                        <a
                          class="social-list-item bg-info text-white border-info"
                          href="javascript::void()"
                        >
                          <i class="mdi mdi-twitter"> </i>
                        </a>
                      </li>
                      <li class="list-inline-item">
                        <a
                          class="social-list-item bg-danger text-white border-danger"
                          href="javascript::void()"
                        >
                          <i class="mdi mdi-google"> </i>
                        </a>
                      </li>
                      <li class="list-inline-item">
                        <a
                          class="social-list-item bg-primary text-white border-primary"
                          href="/auth/microsoft/redirect"
                          title="Sign in with Microsoft"
                         
                        >
                          <i class="mdi mdi-microsoft-windows"></i>
                        </a>
                      </li>
                    </ul>
                  </div>

                  <div class="mt-4 text-center">
                    <Link
                      v-if="canReset"
                      class="text-muted"
                      :href="route('password.request')"
                    >
                      <i class="mdi mdi-lock me-1"> </i>
                      Forgot your password?
                    </Link>
                  </div>
                  <div class="mt-4 text-center">
                    Don't have an account ?
                <Link
                  class="fw-medium text-primary"
                  :href="route('register')"
                >
                  Signup now
                </Link>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <!-- <div class="mt-5 text-center">
            <div>
              <p>
                Don't have an account ?
                <Link
                  class="fw-medium text-primary"
                  :href="route('register')"
                >
                  Signup now
                </Link>
              </p>
              <AuthFooter />
            </div>
          </div> -->
        </div>
      </div>
    </div>
  </div>
</template>