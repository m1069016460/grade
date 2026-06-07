import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useStudentStore } from '@/stores/student'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { title: '登录', requiresAuth: false }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { title: '注册', requiresAuth: false }
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue'),
        meta: { title: '仪表盘' }
      },
      {
        path: 'students',
        name: 'Students',
        component: () => import('@/views/Students.vue'),
        meta: { title: '学生管理' }
      },
      {
        path: 'classes',
        name: 'Classes',
        component: () => import('@/views/Classes.vue'),
        meta: { title: '班级管理' }
      },
      {
        path: 'courses',
        name: 'Courses',
        component: () => import('@/views/Courses.vue'),
        meta: { title: '课程管理' }
      },
      {
        path: 'grades',
        name: 'Grades',
        component: () => import('@/views/Grades.vue'),
        meta: { title: '成绩管理' }
      },
      {
        path: 'statistics',
        name: 'Statistics',
        component: () => import('@/views/Statistics.vue'),
        meta: { title: '统计分析' }
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/views/Users.vue'),
        meta: { title: '用户管理', requiresAdmin: true }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('@/views/Profile.vue'),
        meta: { title: '个人中心' }
      }
    ]
  },
  {
    path: '/student/login',
    name: 'StudentLogin',
    component: () => import('@/views/student/StudentLogin.vue'),
    meta: { title: '学生登录', requiresStudentAuth: false }
  },
  {
    path: '/student',
    component: () => import('@/layouts/StudentLayout.vue'),
    meta: { requiresStudentAuth: true },
    children: [
      {
        path: '',
        name: 'StudentDashboard',
        component: () => import('@/views/student/StudentDashboard.vue'),
        meta: { title: '成绩总览' }
      },
      {
        path: 'courses',
        name: 'StudentCourses',
        component: () => import('@/views/student/StudentCourses.vue'),
        meta: { title: '课程成绩' }
      },
      {
        path: 'profile',
        name: 'StudentProfile',
        component: () => import('@/views/student/StudentProfile.vue'),
        meta: { title: '个人信息' }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue'),
    meta: { title: '页面不存在' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  document.title = to.meta.title ? `${to.meta.title} - 学生成绩管理系统` : '学生成绩管理系统'
  
  const userStore = useUserStore()
  const studentStore = useStudentStore()
  
  const isUserLoggedIn = userStore.isLoggedIn
  const isStudentLoggedIn = studentStore.isLoggedIn
  const isAdmin = userStore.isAdmin
  
  const isStudentPath = to.path.startsWith('/student')
  
  if (isStudentPath) {
    if (to.meta.requiresStudentAuth && !isStudentLoggedIn) {
      next({ name: 'StudentLogin', query: { redirect: to.fullPath } })
      return
    }
    
    if (to.name === 'StudentLogin' && isStudentLoggedIn) {
      next({ name: 'StudentDashboard' })
      return
    }
  } else {
    if (to.meta.requiresAuth && !isUserLoggedIn) {
      next({ name: 'Login', query: { redirect: to.fullPath } })
      return
    }
    
    if (to.meta.requiresAdmin && !isAdmin) {
      next({ name: 'Dashboard' })
      return
    }
    
    if ((to.name === 'Login' || to.name === 'Register') && isUserLoggedIn) {
      next({ name: 'Dashboard' })
      return
    }
  }
  
  next()
})

export default router
