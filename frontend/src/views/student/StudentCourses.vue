<template>
  <div class="student-courses">
    <div class="page-header">
      <h1>课程成绩</h1>
      <el-select 
        v-model="selectedSemester" 
        placeholder="选择学期" 
        clearable
        style="width: 200px"
        @change="fetchData"
      >
        <el-option
          v-for="semester in semesters"
          :key="semester"
          :label="semester"
          :value="semester"
        />
      </el-select>
    </div>
    
    <el-table
      :data="grades"
      v-loading="loading"
      stripe
      style="width: 100%"
      :empty-text="'暂无成绩数据'"
    >
      <el-table-column
        prop="course_code"
        label="课程代码"
        width="120"
        align="center"
      />
      <el-table-column
        prop="course_name"
        label="课程名称"
        min-width="180"
      />
      <el-table-column
        prop="credits"
        label="学分"
        width="100"
        align="center"
      />
      <el-table-column
        prop="course_type"
        label="课程类型"
        width="100"
        align="center"
      >
        <template #default="{ row }">
          <el-tag :type="row.course_type === '必修' ? 'danger' : 'info'" size="small">
            {{ row.course_type }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column
        prop="score"
        label="成绩"
        width="120"
        align="center"
      >
        <template #default="{ row }">
          <span :class="getScoreClass(row.score)">
            {{ row.score }}
          </span>
        </template>
      </el-table-column>
      <el-table-column
        prop="grade_level"
        label="等级"
        width="100"
        align="center"
      >
        <template #default="{ row }">
          <el-tag :type="getGradeTagType(row.grade_level)" size="small">
            {{ row.grade_level }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column
        label="班级排名"
        width="120"
        align="center"
      >
        <template #default="{ row }">
          <div class="rank-info">
            <span class="rank-number">{{ row.classRank || '-' }}</span>
            <span class="rank-total">/{{ row.classTotal || 0 }}</span>
          </div>
        </template>
      </el-table-column>
      <el-table-column
        label="年级排名"
        width="120"
        align="center"
      >
        <template #default="{ row }">
          <div class="rank-info">
            <span class="rank-number">{{ row.gradeRank || '-' }}</span>
            <span class="rank-total">/{{ row.gradeTotal || 0 }}</span>
          </div>
        </template>
      </el-table-column>
      <el-table-column
        prop="exam_type"
        label="考试类型"
        width="100"
        align="center"
      />
      <el-table-column
        prop="semester"
        label="学期"
        width="160"
        align="center"
      />
    </el-table>
    
    <div class="stats-summary" v-if="grades.length > 0">
      <div class="stat-item">
        <span class="label">课程总数</span>
        <span class="value">{{ grades.length }} 门</span>
      </div>
      <div class="stat-item">
        <span class="label">平均分</span>
        <span class="value">{{ avgScore }} 分</span>
      </div>
      <div class="stat-item">
        <span class="label">最高分</span>
        <span class="value">{{ maxScore }} 分</span>
      </div>
      <div class="stat-item">
        <span class="label">及格率</span>
        <span class="value">{{ passRate }}%</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { studentPortalApi } from '@/api/student'

const loading = ref(false)
const grades = ref([])
const semesters = ref([])
const selectedSemester = ref('')

const avgScore = computed(() => {
  if (grades.value.length === 0) return 0
  const sum = grades.value.reduce((acc, item) => acc + parseFloat(item.score), 0)
  return (sum / grades.value.length).toFixed(2)
})

const maxScore = computed(() => {
  if (grades.value.length === 0) return 0
  return Math.max(...grades.value.map(item => parseFloat(item.score)))
})

const passRate = computed(() => {
  if (grades.value.length === 0) return 0
  const passCount = grades.value.filter(item => parseFloat(item.score) >= 60).length
  return ((passCount / grades.value.length) * 100).toFixed(1)
})

function getScoreClass(score) {
  const s = parseFloat(score)
  if (s >= 90) return 'score-excellent'
  if (s >= 80) return 'score-good'
  if (s >= 60) return 'score-pass'
  return 'score-fail'
}

function getGradeTagType(level) {
  switch (level) {
    case '优秀': return 'success'
    case '良好': return 'primary'
    case '中等': return 'warning'
    case '及格': return 'info'
    case '不及格': return 'danger'
    default: return 'info'
  }
}

async function fetchData() {
  loading.value = true
  try {
    const params = selectedSemester.value ? { semester: selectedSemester.value } : {}
    const res = await studentPortalApi.getCourses(params)
    grades.value = res.data.grades
    semesters.value = res.data.semesters
  } catch (error) {
    // 错误已处理
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="scss" scoped>
.student-courses {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    
    h1 {
      font-size: 22px;
      font-weight: 600;
      color: #303133;
    }
  }
  
  :deep(.el-table) {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    
    th {
      background: #fafafa !important;
      font-weight: 600;
    }
  }
  
  .score-excellent {
    color: #22c55e;
    font-weight: 600;
  }
  
  .score-good {
    color: #3b82f6;
    font-weight: 600;
  }
  
  .score-pass {
    color: #f59e0b;
    font-weight: 600;
  }
  
  .score-fail {
    color: #ef4444;
    font-weight: 600;
  }
  
  .rank-info {
    display: flex;
    align-items: baseline;
    justify-content: center;
    
    .rank-number {
      font-size: 16px;
      font-weight: 600;
      color: #303133;
    }
    
    .rank-total {
      font-size: 12px;
      color: #909399;
    }
  }
  
  .stats-summary {
    display: flex;
    justify-content: center;
    gap: 48px;
    margin-top: 24px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    
    .stat-item {
      text-align: center;
      
      .label {
        display: block;
        font-size: 13px;
        color: #909399;
        margin-bottom: 4px;
      }
      
      .value {
        font-size: 20px;
        font-weight: 700;
        color: #303133;
      }
    }
  }
}
</style>
