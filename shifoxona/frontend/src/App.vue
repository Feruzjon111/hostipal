<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { auth, ui, notify } from './lib/state';
import { api } from './lib/api';
import { initials, roles, fullDate } from './lib/format';
import Icon from './components/Icon.vue';
const route = useRoute();
const router = useRouter();
const mobileNav = ref(false);
const logoutBusy = ref(false);
const nav = computed(() => {
  const items = [
    { path: '/', name: 'Umumiy ko‘rinish', icon: 'LayoutDashboard' },
    { path: '/patients', name: 'Bemorlar', icon: 'Users' },
  ];

  if (auth.user.role !== 'doctor') {
    items.push({ path: '/doctors', name: 'Shifokorlar', icon: 'Stethoscope' });
    items.push({ path: '/hospitals', name: 'Shifoxonalar', icon: 'Building2' });
  }
  if (auth.user.role === 'admin') {
    items.push({ path: '/directors', name: 'Rahbarlar', icon: 'ShieldCheck' });
  }

  return items;
});
const today = fullDate();
async function logout() {
  logoutBusy.value = true;
  try {
    await api('/logout', { method: 'POST' });
    auth.user = null;
    await router.push('/login');
  } catch (e) {
    notify(e.message, 'error');
  } finally {
    logoutBusy.value = false;
  }
}
function unauthorized() {
  auth.user = null;
  router.push('/login');
}
onMounted(() => {
  window.addEventListener('shifo:unauthorized', unauthorized);
});
onBeforeUnmount(() => {
  window.removeEventListener('shifo:unauthorized', unauthorized);
});
</script>
<template>
  <div v-if="auth.user && !route.meta.guest" class="app-shell">
    <button
      v-if="mobileNav"
      class="nav-backdrop"
      aria-label="Menyuni yopish"
      @click="mobileNav = false"
    />
    <aside :class="['sidebar', { open: mobileNav }]">
      <RouterLink to="/" class="brand" @click="mobileNav = false">
        <span class="brand-mark"><Icon name="HeartPulse" :size="26" /></span>
        <span>
          shifo
          <span class="brand-dot">.</span>
          <small>SHIFOXONA BOSHQARUVI</small>
        </span>
      </RouterLink>
      <p class="nav-caption">ASOSIY MENYU</p>
      <nav>
        <RouterLink
          v-for="item in nav"
          :key="item.path"
          :to="item.path"
          :class="{
            active: item.path === '/' ? route.path === '/' : route.path.startsWith(item.path),
          }"
          @click="mobileNav = false"
        >
          <Icon :name="item.icon" />
          <span>{{ item.name }}</span>
        </RouterLink>
      </nav>
      <div class="sidebar-bottom">
        <div class="workspace-label">
          <span class="live-dot" />
          {{ roles[auth.user.role] }}
        </div>
        <p>{{ auth.user.hospital?.name || 'Barcha shifoxonalar' }}</p>
        <div class="sidebar-user">
          <span class="avatar">{{ initials(auth.user.name) }}</span>
          <div>
            <strong>{{ auth.user.name }}</strong>
            <small>{{ auth.user.email }}</small>
          </div>
        </div>
        <button class="logout-button" :disabled="logoutBusy" @click="logout">
          <Icon name="LogOut" :size="18" />
          Tizimdan chiqish
        </button>
      </div>
    </aside>
    <div class="main-shell">
      <header class="topbar">
        <div class="topbar-left">
          <button
            class="icon-button mobile-menu"
            aria-label="Menyuni ochish"
            @click="mobileNav = true"
          >
            <Icon name="Menu" />
          </button>
          <span>Shifoxona boshqaruvi</span>
          <Icon name="ChevronRight" :size="15" />
          <strong>{{ route.meta.title }}</strong>
        </div>
        <div class="topbar-right">
          <span class="today">
            <Icon name="CalendarDays" :size="17" />
            {{ today }}
          </span>
          <span class="topbar-divider" />
          <span class="avatar small">{{ initials(auth.user.name) }}</span>
        </div>
      </header>
      <main><RouterView :key="route.path" /></main>
      <footer class="app-footer">
        <span>© {{ new Date().getFullYear() }} Shifo. Sog‘lom hayot sari.</span>
        <span>
          <span class="live-dot" />
          Elektron ro‘yxatga olish tizimi
        </span>
      </footer>
    </div>
  </div>
  <RouterView v-else />
  <Transition name="toast">
    <div v-if="ui.toast" :class="['toast', ui.toast.type]" role="status">
      <Icon :name="ui.toast.type === 'error' ? 'AlertCircle' : 'CheckCircle2'" />
      <span>{{ ui.toast.message }}</span>
      <button class="icon-button" aria-label="Bildirishnomani yopish" @click="ui.toast = null">
        <Icon name="X" :size="17" />
      </button>
    </div>
  </Transition>
</template>
