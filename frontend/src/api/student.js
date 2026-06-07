import { request } from '@/utils/request'

export const studentAuthApi = {
  login(data) {
    return request.post('/auth/student/login', data)
  }
}

export const studentPortalApi = {
  getProfile() {
    return request.get('/student/profile')
  },
  
  getOverview(params) {
    return request.get('/student/overview', { params })
  },
  
  getCourses(params) {
    return request.get('/student/courses', { params })
  },
  
  getCourseDetail(courseId, params) {
    return request.get(`/student/courses/${courseId}`, { params })
  },
  
  getTrend() {
    return request.get('/student/trend')
  }
}
