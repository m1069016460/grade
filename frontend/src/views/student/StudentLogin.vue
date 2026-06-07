<template>
  <div class="student-login-page">
    <div class="login-bg">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
    </div>
    
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <img src="/favicon.svg" alt="Logo" class="logo" />
          <h1>学生成绩查询系统</h1>
          <p>查看个人成绩与排名信息</p>
        </div>
        
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          class="login-form"
          @keyup.enter="handleSubmit"
        >
          <el-form-item prop="studentNo">
            <el-input
              v-model="form.studentNo"
              placeholder="请输入学号"
              size="large"
              :prefix-icon="User"
            />
          </el-form-item>
          
          <el-form-item prop="password">
            <el-input
              v-model="form.password"
              type="password"
              placeholder="请输入密码"
              size="large"
              :prefix-icon="Lock"
              show-password
            />
          </el-form-item>
          
          <el-form-item>
            <el-button
              type="primary"
              size="large"
              class="login-btn"
              :loading="loading"
              @click="handleSubmit"
            >
              登 录
            </el-button>
          </el-form-item>
        </el-form>
        
        <div class="login-footer">
          <router-link to="/login">返回管理系统登录</router-link>
        </div>
      </div>
      
      <div class="demo-account">
        <el-divider>测试账号</el-divider>
        <div class="account" @click="fillDemo('2024001001', '123456')">
          <el-tag type="success">学生</el-tag>
          <span>学号：2024001001 / 密码：123456</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStudentStore } from '@/stores/student'
import { showSuccess } from '@/utils/request'
import { User, Lock } from '@element-plus/icons-vue'

const router = useRouter()
const route = useRoute()
const studentStore = useStudentStore()

const formRef = ref(null)
const loading = ref(false)

const form = reactive({
  studentNo: '',
  password: ''
})

const rules = {
  studentNo: [
    { required: true, message: '请输入学号', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' }
  ]
}

function fillDemo(studentNo, password) {
  form.studentNo = studentNo
  form.password = password
}

async function handleSubmit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return
  
  loading.value = true
  try {
    await studentStore.login(form)
    showSuccess('登录成功')
    const redirect = route.query.redirect || '/student'
    router.push(redirect)
  } catch (error) {
    // 错误已在拦截器中处理
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.student-login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  position: relative;
  overflow: hidden;
}

.login-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  
  .shape {
    position: absolute;
    border-radius: 50%;
    opacity: 0.3;
    
    &.shape-1 {
      width: 400px;
      height: 400px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      top: -100px;
      left: -100px;
      animation: float 8s ease-in-out infinite;
    }
    
    &.shape-2 {
      width: 300px;
      height: 300px;
      background: linear-gradient(135deg, #16a34a, #22c55e);
      bottom: -50px;
      right: -50px;
      animation: float 6s ease-in-out infinite reverse;
    }
    
    &.shape-3 {
      width: 200px;
      height: 200px;
      background: linear-gradient(135deg, #22c55e, #4ade80);
      top: 50%;
      left: 20%;
      animation: float 7s ease-in-out infinite;
    }
  }
}

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
}

.login-container {
  position: relative;
  z-index: 1;
}

.login-card {
  width: 420px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  padding: 40px;
  
  .login-header {
    text-align: center;
    margin-bottom: 32px;
    
    .logo {
      width: 64px;
      height: 64px;
      margin-bottom: 16px;
    }
    
    h1 {
      font-size: 24px;
      font-weight: 600;
      color: #303133;
      margin-bottom: 8px;
    }
    
    p {
      font-size: 14px;
      color: #909399;
    }
  }
  
  .login-form {
    :deep(.el-input__wrapper) {
      border-radius: 10px;
      box-shadow: 0 0 0 1px #e4e7ed inset;
      padding: 4px 15px;
      
      &:hover {
        box-shadow: 0 0 0 1px #c0c4cc inset;
      }
      
      &.is-focus {
        box-shadow: 0 0 0 1px #22c55e inset;
      }
    }
    
    .el-form-item {
      margin-bottom: 20px;
    }
  }
  
  .login-btn {
    width: 100%;
    height: 48px;
    font-size: 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border: none;
    
    &:hover {
      opacity: 0.9;
    }
  }
  
  .login-footer {
    text-align: center;
    color: #909399;
    font-size: 14px;
    
    a {
      color: #22c55e;
      font-weight: 500;
      
      &:hover {
        text-decoration: underline;
      }
    }
  }
}

.demo-account {
  margin-top: 24px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  
  :deep(.el-divider__text) {
    background: transparent;
    color: #909399;
    font-size: 12px;
  }
  
  .account {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #f0fdf4;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    
    &:hover {
      background: #dcfce7;
    }
    
    span {
      font-size: 13px;
      color: #606266;
      font-family: monospace;
    }
  }
}

@media (max-width: 480px) {
  .login-card {
    width: 90vw;
    padding: 30px 20px;
  }
}
</style>
