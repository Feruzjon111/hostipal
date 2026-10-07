import { createRouter, createWebHistory } from 'vue-router';
import { auth, initializeAuth } from './lib/state';
import Login from './pages/Login.vue';
import Dashboard from './pages/Dashboard.vue';
import Patients from './pages/Patients.vue';
import PatientForm from './pages/PatientForm.vue';
import PatientDetail from './pages/PatientDetail.vue';
import Staff from './pages/Staff.vue';
import Hospitals from './pages/Hospitals.vue';
import NotFound from './pages/NotFound.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: Login, meta: { guest: true, title: 'Kirish' } },
    { path: '/', component: Dashboard, meta: { title: 'Umumiy ko‘rinish' } },
    { path: '/patients', component: Patients, meta: { title: 'Bemorlar' } },
    {
      path: '/patients/new',
      component: PatientForm,
      meta: { roles: ['admin', 'doctor'], title: 'Yangi bemor' },
    },
    {
      path: '/patients/:id/edit',
      component: PatientForm,
      meta: { roles: ['admin', 'doctor'], title: 'Bemorni tahrirlash' },
    },
    { path: '/patients/:id', component: PatientDetail, meta: { title: 'Bemor kartasi' } },
    {
      path: '/doctors',
      component: Staff,
      props: { type: 'doctors' },
      meta: { roles: ['admin', 'director'], title: 'Shifokorlar' },
    },
    {
      path: '/directors',
      component: Staff,
      props: { type: 'directors' },
      meta: { roles: ['admin'], title: 'Rahbarlar' },
    },
    {
      path: '/hospitals',
      component: Hospitals,
      meta: { roles: ['admin', 'director'], title: 'Shifoxonalar' },
    },
    { path: '/:pathMatch(.*)*', component: NotFound, meta: { title: 'Sahifa topilmadi' } },
  ],
});
router.beforeEach(async (to) => {
  await initializeAuth();
  if (!to.meta.guest && !auth.user) return '/login';
  if (to.meta.guest && auth.user) return '/';
  if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) return '/';
});
router.afterEach((to) => {
  document.title = `${to.meta.title || 'Shifoxona'} — Shifo`;
});
export default router;
