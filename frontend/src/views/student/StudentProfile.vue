<template>
  <div class="student-profile">
    <div class="page-header">
      <h1>个人信息</h1>
    </div>
    
    <div class="profile-card">
      <div class="avatar-section">
        <el-avatar :size="100" class="avatar">
          {{ profile.student?.name?.charAt(0) || 'S' }}
        </el-avatar>
        <h2>{{ profile.student?.name || '加载中...' }}</h2>
        <p class="student-no">学号：{{ profile.student?.student_no || '-' }}</p>
      </div>
      
      <el-divider />
      
      <div class="info-section">
        <div class="info-grid">
          <div class="info-item">
            <span class="label">性别</span>
            <span class="value">{{ profile.student?.gender || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">班级</span>
            <span class="value">{{ profile.class?.name || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">年级</span>
            <span class="value">{{ profile.class?.grade || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">专业</span>
            <span class="value">{{ profile.class?.major || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">联系电话</span>
            <span class="value">{{ profile.student?.phone || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">邮箱</span>
            <span class="value">{{ profile.student?.email || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">出生日期</span>
            <span class="value">{{ profile.student?.birth_date || '-' }}</span>
          </div>
          <div class="info-item">
            <span class="label">入学日期</span>
            <span class="value">{{ profile.student?.admission_date || '-' }}</span>
          </div>
          <div class="info-item full-width">
            <span class="label">家庭地址</span>
            <span class="value">{{ profile.student?.address || '-' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { studentPortalApi } from '@/api/student'

const profile = ref({})

async function fetchData() {
  try {
    const res = await studentPortalApi.getProfile()
    profile.value = res.data
  } catch (error) {
    // 错误已处理
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="scss" scoped>
.student-profile {
  .page-header {
    margin-bottom: 24px;
    
    h1 {
      font-size: 22px;
      font-weight: 600;
      color: #303133;
    }
  }
  
  .profile-card {
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    
    .avatar-section {
      text-align: center;
      
      .avatar {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        font-size: 42px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 16px;
      }
      
      h2 {
        font-size: 24px;
        font-weight: 600;
        color: #303133;
        margin-bottom: 8px;
      }
      
      .student-no {
        font-size: 14px;
        color: #909399;
      }
    }
    
    .info-section {
      margin-top: 24px;
      
      .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        
        .info-item {
          display: flex;
          flex-direction: column;
          gap: 4px;
          
          &.full-width {
            grid-column: span 2;
          }
          
          .label {
            font-size: 13px;
            color: #909399;
          }
          
          .value {
            font-size: 15px;
            color: #303133;
            font-weight: 500;
          }
        }
      }
    }
  }
}
</style>
