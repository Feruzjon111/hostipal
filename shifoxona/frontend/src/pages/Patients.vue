<script setup>
import { reactive, ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { useRoute } from 'vue-router';
import { api, queryString } from '../lib/api';
import { auth, lookups, loadLookups } from '../lib/state';
import { statuses } from '../lib/format';
import Icon from '../components/Icon.vue';
import PatientTable from '../components/PatientTable.vue';
import Pagination from '../components/Pagination.vue';
const route = useRoute();
const filters = reactive({
  search: '',
  status: route.query.status || '',
  disease_id: '',
  doctor_id: '',
  hospital_id: '',
  date: '',
});
const data = ref(null);
const busy = ref(true);
const error = ref('');
const canCreate = computed(() => ['admin', 'doctor'].includes(auth.user?.role));
const doctors = computed(() =>
  lookups.doctors.filter(
    (x) => !filters.hospital_id || x.hospital_id === Number(filters.hospital_id),
  ),
);
let timer;
let requestId = 0;
let ready = false;
async function load(page = 1) {
  const id = ++requestId;
  busy.value = true;
  error.value = '';
  try {
    const result = await api(`/patients?${queryString({ ...filters, page })}`);
    if (id === requestId) {
      data.value = result;
    }
  } catch (e) {
    if (id === requestId) {
      error.value = e.message;
    }
  } finally {
    if (id === requestId) {
      busy.value = false;
    }
  }
}
watch(
  () => filters.hospital_id,
  () => {
    filters.doctor_id = '';
  },
);
watch(filters, () => {
  if (!ready) {
    return;
  }
  clearTimeout(timer);
  timer = setTimeout(() => load(), 250);
});
onMounted(async () => {
  try {
    await loadLookups();
    ready = true;
    await load();
  } catch (e) {
    error.value = e.message;
    busy.value = false;
  }
});
onBeforeUnmount(() => {
  clearTimeout(timer);
  requestId++;
});
function reset() {
  Object.assign(filters, {
    search: '',
    status: '',
    disease_id: '',
    doctor_id: '',
    hospital_id: '',
    date: '',
  });
}
</script>
<template>
  <div class="page-heading">
    <div>
      <span class="eyebrow">ELEKTRON RO‘YXAT</span>
      <h1>Bemorlar</h1>
      <p>Bemor kartalari va qabul ma’lumotlarini boshqaring.</p>
    </div>
    <RouterLink v-if="canCreate" to="/patients/new" class="button primary">
      <Icon name="Plus" :size="19" />
      Bemor qo‘shish
    </RouterLink>
  </div>
  <section class="panel">
    <div class="filters">
      <div class="search-input">
        <Icon name="Search" :size="19" />
        <input
          v-model="filters.search"
          placeholder="Ism, pasport yoki JShShIR bo‘yicha qidirish…"
          aria-label="Bemor qidirish"
        />
      </div>
      <select v-model="filters.status" aria-label="Holat bo‘yicha filtrlash">
        <option value="">Barcha holatlar</option>
        <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
      </select>
      <select v-model="filters.disease_id" aria-label="Kasallik bo‘yicha filtrlash">
        <option value="">Barcha kasalliklar</option>
        <option v-for="d in lookups.diseases" :key="d.id" :value="d.id">{{ d.name }}</option>
      </select>
    </div>
    <div class="filters secondary-filters">
      <select
        v-if="auth.user.role === 'admin'"
        v-model="filters.hospital_id"
        aria-label="Shifoxona bo‘yicha filtrlash"
      >
        <option value="">Barcha shifoxonalar</option>
        <option v-for="h in lookups.hospitals" :key="h.id" :value="h.id">{{ h.name }}</option>
      </select>
      <select
        v-if="auth.user.role !== 'doctor'"
        v-model="filters.doctor_id"
        aria-label="Shifokor bo‘yicha filtrlash"
      >
        <option value="">Barcha shifokorlar</option>
        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name }}</option>
      </select>
      <input v-model="filters.date" type="date" aria-label="Qabul sanasi bo‘yicha filtrlash" />
      <button class="text-link filter-reset" @click="reset">
        <Icon name="X" :size="15" />
        Filtrlarni tozalash
      </button>
    </div>
    <div v-if="error" class="alert error page-alert" role="alert">
      {{ error }}
      <button class="button secondary" @click="load">Qayta urinish</button>
    </div>
    <div v-else-if="busy" class="loading-state">
      <Icon name="LoaderCircle" class="spin" />
      Bemorlar yuklanmoqda…
    </div>
    <template v-else>
      <PatientTable v-if="data?.data.length" :patients="data.data" />
      <div v-else class="empty-state">
        <Icon name="Users" :size="36" />
        <h3>Bemorlar topilmadi</h3>
        <p>Qidiruv yoki filtrlarni o‘zgartiring, yoki yangi bemor qo‘shing.</p>
        <button class="button secondary" @click="reset">Filtrlarni tozalash</button>
      </div>
    </template>
    <Pagination :data="data" :busy="busy" @page="load" />
  </section>
</template>
