import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { studentAuthApi, studentPortalApi } from '@/api/student'
import router from '@/router'

export const useStudentStore = defineStore('student', () => {
  const token = ref(localStorage.getItem('studentToken') || '')
  const student = ref(JSON.parse(localStorage.getItem('student') || 'null'))
  
  const isLoggedIn = computed(() => !!token.value)
  
  function setToken(newToken) {
    token.value = newToken
    localStorage.setItem('studentToken', newToken)
  }
  
  function setStudent(newStudent) {
    student.value = newStudent
    localStorage.setItem('student', JSON.stringify(newStudent))
  }
  
  async function login(credentials) {
    const res = await studentAuthApi.login(credentials)
    setToken(res.data.token)
    setStudent(res.data.student)
    return res
  }
  
  async function fetchProfile() {
    const res = await studentPortalApi.getProfile()
    setStudent({ ...student.value, ...res.data.student, classInfo: res.data.class })
    return res
  }
  
  function logout() {
    token.value = ''
    student.value = null
    localStorage.removeItem('studentToken')
    localStorage.removeItem('student')
    router.push('/student/login')
  }
  
  async function checkAuth() {
    if (token.value) {
      try {
        await fetchProfile()
      } catch (error) {
        logout()
      }
    }
  }
  
  return {
    token,
    student,
    isLoggedIn,
    login,
    logout,
    fetchProfile,
    checkAuth
  }
})
