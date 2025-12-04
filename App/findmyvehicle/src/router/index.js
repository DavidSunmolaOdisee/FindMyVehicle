import { createRouter, createWebHistory } from '@ionic/vue-router';
import TabsPage from '../views/TabsPage.vue'
import LoginPage from '../views/LoginPage.vue'

const routes = [
  {
    path: '',
    redirect: '/login'
  },
  {
    path: '/login',
    component: LoginPage,
    meta: { requiresAuth: false }
  },
  {
    path: '/tabs/',
    component: TabsPage,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/tabs/tab1'
      },
      {
        path: 'tab1',
        component: () => import('@/views/DashboardPage.vue')
      },
      {
        path: 'tab2',
        component: () => import('@/views/VehiclesPage.vue')
      },
      {
        path: 'tab3',
        component: () => import('@/views/ParkingsPage.vue')
      },
      {
        path: 'tab4',
        component: () => import('@/views/HistoryPage.vue')
      },
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
