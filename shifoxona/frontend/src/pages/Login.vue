<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { auth } from '../lib/state';
import { api } from '../lib/api';
import Icon from '../components/Icon.vue';
import Field from '../components/Field.vue';
const router = useRouter();
const form = reactive({ email: '', password: '' });
const busy = ref(false);
const error = ref('');
const showPassword = ref(false);
async function submit() {
  busy.value = true;
  error.value = '';
  try {
    const data = await api('/login', { method: 'POST', body: form });
    auth.user = data.user;
    auth.initialized = true;
    await router.push('/');
  } catch (e) {
    error.value = e.errors?.email?.[0] || e.message;
  } finally {
    busy.value = false;
  }
}
</script>
<template>
  <div class="login-page">
    <section class="login-story">
      <a href="/" class="brand">
        <span class="brand-mark"><Icon name="HeartPulse" :size="26" /></span>
        <span>
          shifo
          <span class="brand-dot">.</span>
        </span>
      </a>
      <div class="story-content">
        <span class="eyebrow light">
          <span class="live-dot" />
          RAQAMLI TIBBIYOT
        </span>
        <h1>
          Har bir qabul.
          <br />
          Har bir bemor.
          <br />
          <span>Hammasi bir joyda.</span>
        </h1>
        <p>
          Bemorlarni ro‘yxatga olish, shifokorlar faoliyati va shifoxona boshqaruvi uchun yagona
          tizim.
        </p>
        <div class="hospital-illustration" aria-hidden="true">
          <div class="orbit orbit-one" />
          <div class="orbit orbit-two" />
          <div class="floating-pill pill-one">
            <Icon name="ShieldCheck" />
            Ma’lumotlar himoyalangan
          </div>
          <div class="hospital-building">
            <span class="hospital-cross">+</span>
            <div class="building-windows"><i v-for="n in 8" :key="n" /></div>
            <div class="building-door" />
          </div>
          <div class="floating-pill pill-two">
            <span class="live-dot" />
            Qabullar nazorat ostida
          </div>
        </div>
      </div>
      <div class="story-footer">
        <Icon name="HeartPulse" :size="18" />
        Sog‘lom hayot sari, birgalikda.
      </div>
    </section>
    <section class="login-panel">
      <div class="login-form-wrap">
        <span class="eyebrow">XUSH KELIBSIZ</span>
        <h2>Tizimga kirish</h2>
        <p class="muted">Ishingizni davom ettirish uchun hisobingizga kiring.</p>
        <form @submit.prevent="submit">
          <div v-if="error" class="alert error" role="alert">
            <Icon name="AlertCircle" :size="18" />
            {{ error }}
          </div>
          <Field label="Email manzili" required>
            <div class="input-icon">
              <Icon name="Mail" :size="19" />
              <input
                v-model="form.email"
                type="email"
                placeholder="ism@shifo.uz"
                autocomplete="username"
                maxlength="150"
                required
              />
            </div>
          </Field>
          <Field label="Parol" required>
            <div class="input-icon">
              <Icon name="LockKeyhole" :size="19" />
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Parolingizni kiriting"
                autocomplete="current-password"
                required
              />
              <button
                class="password-toggle"
                type="button"
                :aria-label="showPassword ? 'Parolni yashirish' : 'Parolni ko‘rsatish'"
                @click="showPassword = !showPassword"
              >
                <Icon name="Eye" :size="18" />
              </button>
            </div>
          </Field>
          <button class="button primary login-submit" :disabled="busy">
            <Icon v-if="busy" name="LoaderCircle" class="spin" />
            {{ busy ? 'Kirilmoqda…' : 'Tizimga kirish' }}
            <Icon v-if="!busy" name="ArrowRight" :size="18" />
          </button>
        </form>
        <div class="login-note">
          <Icon name="ShieldCheck" :size="20" />
          <p>
            Hisobingizni shifoxona rahbari yoki administrator yaratadi. Kirish ma’lumotlari uchun
            ularga murojaat qiling.
          </p>
        </div>
      </div>
      <p class="login-copyright">Shifo · Elektron shifoxona tizimi</p>
    </section>
  </div>
</template>
