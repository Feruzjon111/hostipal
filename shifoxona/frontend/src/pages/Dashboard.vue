<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../lib/api';
import { auth } from '../lib/state';
import Icon from '../components/Icon.vue';
import PatientTable from '../components/PatientTable.vue';
import { weekday } from '../lib/format';
const data = ref(null);
const error = ref('');
const busy = ref(true);
const canCreate = computed(() => ['admin', 'doctor'].includes(auth.user?.role));
const max = computed(() => Math.max(1, ...(data.value?.week.map((x) => x.count) || [])));
const weeklyTotal = computed(() => data.value?.week.reduce((n, x) => n + x.count, 0) || 0);
const cards = computed(() =>
  data.value
    ? [
        {
          label: 'Jami bemorlar',
          value: data.value.total,
          icon: 'Users',
          tone: 'teal',
          hint: 'Ro‘yxatga olingan bemorlar',
        },
        {
          label: 'Bugungi qabullar',
          value: data.value.today,
          icon: 'CalendarDays',
          tone: 'blue',
          hint: 'Bugun ro‘yxatga olingan',
        },
        {
          label: 'Qabul kutilmoqda',
          value: data.value.waiting,
          icon: 'Clock3',
          tone: 'amber',
          hint: 'Navbatdagi bemorlar',
        },
        {
          label: 'Yakunlangan',
          value: data.value.completed,
          icon: 'CheckCircle2',
          tone: 'purple',
          hint: 'Qabuli yakunlangan bemorlar',
        },
      ]
    : [],
);
async function load() {
  busy.value = true;
  error.value = '';
  try {
    data.value = await api('/dashboard');
  } catch (e) {
    error.value = e.message;
  } finally {
    busy.value = false;
  }
}
onMounted(load);
</script>
<template>
  <div class="page-heading">
    <div>
      <span class="eyebrow">BOSHQARUV PANELI</span>
      <h1>
        Xayrli kun, {{ auth.user.name.split(' ')[0] }}
        <span class="greeting-dot">✦</span>
      </h1>
      <p>Shifoxonangiz faoliyati haqida eng so‘nggi ma’lumotlar.</p>
    </div>
    <RouterLink v-if="canCreate" to="/patients/new" class="button primary">
      <Icon name="Plus" :size="19" />
      Bemor qo‘shish
    </RouterLink>
  </div>
  <div v-if="error" class="alert error" role="alert">
    {{ error }}
    <button class="button secondary" @click="load">Qayta urinish</button>
  </div>
  <div v-else-if="busy" class="loading-state">
    <Icon name="LoaderCircle" class="spin" />
    Ma’lumotlar yuklanmoqda…
  </div>
  <template v-else-if="data">
    <div class="stats-grid">
      <article v-for="card in cards" :key="card.label" class="stat-card">
        <div class="stat-top">
          <span>{{ card.label }}</span>
          <span :class="['stat-icon', card.tone]"><Icon :name="card.icon" :size="21" /></span>
        </div>
        <strong>{{ card.value.toLocaleString('uz-UZ') }}</strong>
        <small>{{ card.hint }}</small>
      </article>
    </div>
    <div class="dashboard-middle">
      <section class="panel chart-panel">
        <div class="panel-heading">
          <div>
            <h2>Qabullar dinamikasi</h2>
            <p>Oxirgi 7 kundagi ro‘yxatga olishlar</p>
          </div>
          <span class="subtle-pill">
            <Icon name="CalendarDays" :size="15" />
            7 kun
          </span>
        </div>
        <div class="chart-summary">
          <strong>{{ weeklyTotal }}</strong>
          <span>ta qabul shu haftada</span>
          <span class="chart-legend">
            <i />
            Qabullar
          </span>
        </div>
        <div
          class="bar-chart"
          role="img"
          :aria-label="`Oxirgi 7 kunda jami ${weeklyTotal} ta qabul`"
        >
          <div class="chart-lines"><span v-for="n in 4" :key="n" /></div>
          <div v-for="(day, i) in data.week" :key="day.date" class="bar-column">
            <span class="bar-number">{{ day.count }}</span>
            <div
              :class="['bar', { current: i === 6 }]"
              :style="{ height: `${Math.max(day.count ? 6 : 1, (day.count / max) * 118)}px` }"
              :title="`${day.date}: ${day.count} ta qabul`"
            />
            <span class="bar-label">{{ weekday(day.date) }}</span>
          </div>
        </div>
      </section>
      <section class="panel quick-panel">
        <div class="panel-heading">
          <div>
            <h2>Ish maydoningiz</h2>
            <p>Kerakli bo‘limga tezkor o‘tish</p>
          </div>
          <Icon name="Activity" :size="20" />
        </div>
        <RouterLink to="/patients" class="quick-link">
          <span class="quick-icon teal"><Icon name="ClipboardList" :size="22" /></span>
          <div>
            <strong>Bemorlar ro‘yxati</strong>
            <small>{{ data.total }} ta bemor kartasi</small>
          </div>
          <Icon name="ArrowRight" :size="18" />
        </RouterLink>
        <RouterLink v-if="auth.user.role !== 'doctor'" to="/doctors" class="quick-link">
          <span class="quick-icon blue"><Icon name="Stethoscope" :size="22" /></span>
          <div>
            <strong>Shifokorlar jamoasi</strong>
            <small>{{ data.doctors }} ta shifokor</small>
          </div>
          <Icon name="ArrowRight" :size="18" />
        </RouterLink>
        <RouterLink v-else to="/patients?status=in_progress" class="quick-link">
          <span class="quick-icon blue"><Icon name="Stethoscope" :size="22" /></span>
          <div>
            <strong>Davom etayotgan qabullar</strong>
            <small>{{ data.in_progress }} ta bemor qabulda</small>
          </div>
          <Icon name="ArrowRight" :size="18" />
        </RouterLink>
        <div class="workspace-note">
          <Icon name="ShieldCheck" :size="19" />
          <p>
            {{
              auth.user.role === 'admin'
                ? 'Barcha shifoxonalar faoliyatini bir joydan boshqaring.'
                : auth.user.role === 'director'
                  ? 'Shifoxonangizning barcha qabullari nazoratingiz ostida.'
                  : 'Shaxsiy ish maydoningizda o‘zingizga biriktirilgan bemorlarni boshqaring.'
            }}
          </p>
        </div>
      </section>
    </div>
    <section class="panel">
      <div class="panel-heading">
        <div>
          <h2>So‘nggi qabullar</h2>
          <p>Eng oxirgi ro‘yxatga olingan bemorlar</p>
        </div>
        <RouterLink to="/patients" class="text-link">
          Barchasini ko‘rish
          <Icon name="ArrowRight" :size="16" />
        </RouterLink>
      </div>
      <PatientTable v-if="data.recent.length" :patients="data.recent" compact />
      <div v-else class="empty-state">
        <Icon name="Users" :size="34" />
        <h3>Hozircha bemorlar yo‘q</h3>
        <p>Yangi bemor qo‘shilgach, qabullar shu yerda ko‘rinadi.</p>
      </div>
    </section>
  </template>
</template>
