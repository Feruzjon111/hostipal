<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../lib/api';
import { auth, notify, confirmDelete } from '../lib/state';
import { fullName, initials, dateTime } from '../lib/format';
import Icon from '../components/Icon.vue';
import StatusBadge from '../components/StatusBadge.vue';
const route = useRoute();
const router = useRouter();
const patient = ref(null);
const busy = ref(true);
const deleting = ref(false);
const error = ref('');
const canEdit = computed(
  () =>
    patient.value &&
    (auth.user.role === 'admin' ||
      (auth.user.role === 'doctor' && auth.user.id === patient.value.doctor_id)),
);
async function load() {
  busy.value = true;
  error.value = '';
  try {
    patient.value = await api(`/patients/${route.params.id}`);
  } catch (e) {
    error.value = e.message;
  } finally {
    busy.value = false;
  }
}
async function remove() {
  const confirmed = confirmDelete(
    'Bemor kartasini o‘chirish',
    `${fullName(patient.value)} kartasi o‘chiriladi. Bu amalni qaytarib bo‘lmaydi.`,
  );
  if (!confirmed) {
    return;
  }
  deleting.value = true;
  try {
    await api(`/patients/${patient.value.id}`, { method: 'DELETE' });
    notify('Bemor kartasi o‘chirildi.');
    await router.push('/patients');
  } catch (e) {
    notify(e.message, 'error');
  } finally {
    deleting.value = false;
  }
}
onMounted(load);
</script>
<template>
  <RouterLink to="/patients" class="back-link">
    <Icon name="ArrowLeft" :size="17" />
    Bemorlar ro‘yxatiga qaytish
  </RouterLink>
  <div v-if="busy" class="loading-state">
    <Icon name="LoaderCircle" class="spin" />
    Bemor kartasi yuklanmoqda…
  </div>
  <div v-else-if="error" class="alert error" role="alert">
    {{ error }}
    <button class="button secondary" @click="load">Qayta urinish</button>
  </div>
  <template v-else-if="patient">
    <div class="page-heading">
      <div>
        <span class="eyebrow">BEMOR KARTASI · #{{ String(patient.id).padStart(4, '0') }}</span>
        <h1>{{ fullName(patient) }}</h1>
        <p>{{ patient.middle_name }}</p>
      </div>
      <div v-if="canEdit" class="heading-actions">
        <button class="button danger-outline" :disabled="deleting" @click="remove">
          <Icon name="Trash2" :size="17" />
          O‘chirish
        </button>
        <RouterLink :to="`/patients/${patient.id}/edit`" class="button primary">
          <Icon name="Pencil" :size="17" />
          Tahrirlash
        </RouterLink>
      </div>
    </div>
    <section class="panel patient-summary">
      <span class="avatar large" :class="`tone-${patient.id % 4}`">
        {{ initials(fullName(patient)) }}
      </span>
      <div>
        <h2>{{ fullName(patient) }}</h2>
        <p>{{ patient.disease?.name }} · {{ patient.hospital?.name }}</p>
      </div>
      <StatusBadge :status="patient.status" />
    </section>
    <div class="detail-grid">
      <section class="panel detail-panel">
        <div class="section-title">
          <span class="section-icon"><Icon name="FileText" :size="21" /></span>
          <h2>Shaxsiy ma’lumotlar</h2>
        </div>
        <dl class="detail-list">
          <div>
            <dt>Ism</dt>
            <dd>{{ patient.first_name }}</dd>
          </div>
          <div>
            <dt>Familiya</dt>
            <dd>{{ patient.last_name }}</dd>
          </div>
          <div>
            <dt>Otasining ismi</dt>
            <dd>{{ patient.middle_name }}</dd>
          </div>
          <div>
            <dt>Pasport</dt>
            <dd class="mono">{{ patient.passport }}</dd>
          </div>
          <div>
            <dt>JShShIR (PIN)</dt>
            <dd class="mono">{{ patient.pin }}</dd>
          </div>
        </dl>
      </section>
      <section class="panel detail-panel">
        <div class="section-title">
          <span class="section-icon"><Icon name="Stethoscope" :size="21" /></span>
          <h2>Qabul tafsilotlari</h2>
        </div>
        <dl class="detail-list">
          <div>
            <dt>Kasallik turi</dt>
            <dd>{{ patient.disease?.name }}</dd>
          </div>
          <div>
            <dt>Shifokor</dt>
            <dd>
              {{ patient.doctor?.name }}
              <small>{{ patient.doctor?.specialization }}</small>
            </dd>
          </div>
          <div>
            <dt>Shifoxona</dt>
            <dd>{{ patient.hospital?.name }}</dd>
          </div>
          <div>
            <dt>Qabul sanasi</dt>
            <dd>{{ dateTime(patient.registered_at) }}</dd>
          </div>
          <div>
            <dt>Holat</dt>
            <dd><StatusBadge :status="patient.status" /></dd>
          </div>
        </dl>
      </section>
      <section class="panel detail-panel">
        <div class="section-title">
          <span class="section-icon"><Icon name="MapPin" :size="21" /></span>
          <h2>Yashash manzili</h2>
        </div>
        <dl class="detail-list">
          <div>
            <dt>Viloyat</dt>
            <dd>{{ patient.region?.name }}</dd>
          </div>
          <div>
            <dt>Tuman / shahar</dt>
            <dd>{{ patient.district?.name }}</dd>
          </div>
          <div>
            <dt>Manzil</dt>
            <dd>{{ patient.address }}</dd>
          </div>
        </dl>
      </section>
      <section class="panel detail-panel">
        <div class="section-title">
          <span class="section-icon"><Icon name="ClipboardList" :size="21" /></span>
          <h2>Qo‘shimcha ma’lumot</h2>
        </div>
        <p class="patient-notes">{{ patient.notes || 'Qo‘shimcha izoh kiritilmagan.' }}</p>
        <dl class="detail-list">
          <div>
            <dt>Yaratilgan</dt>
            <dd>{{ dateTime(patient.created_at) }}</dd>
          </div>
          <div>
            <dt>Oxirgi yangilanish</dt>
            <dd>{{ dateTime(patient.updated_at) }}</dd>
          </div>
        </dl>
      </section>
    </div>
  </template>
</template>
