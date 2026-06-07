<template>
  <div class="student-dashboard">
    <div class="page-header">
      <h1>成绩总览</h1>
      <span class="date">{{ currentDate }}</span>
    </div>
    
    <div class="student-info-card">
      <div class="student-avatar">
        <el-avatar :size="64">
          {{ overview.student?.name?.charAt(0) || 'S' }}
        </el-avatar>
      </div>
      <div class="student-details">
        <h2>{{ overview.student?.name || '加载中...' }}</h2>
        <p>学号：{{ overview.student?.studentNo || '-' }}</p>
        <p>班级：{{ overview.class?.name || '-' }} · {{ overview.class?.grade || '-' }}</p>
      </div>
    </div>
    
    <div class="stats-grid">
      <div class="stat-card primary">
        <div class="stat-icon">
          <el-icon><DataAnalysis /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">平均分</div>
          <div class="stat-value">{{ overview.summary?.avgScore || 0 }}</div>
        </div>
      </div>
      
      <div class="stat-card success">
        <div class="stat-icon">
          <el-icon><Trophy /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">最高分</div>
          <div class="stat-value">{{ overview.summary?.maxScore || 0 }}</div>
        </div>
      </div>
      
      <div class="stat-card warning">
        <div class="stat-icon">
          <el-icon><Tickets /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">及格率</div>
          <div class="stat-value">{{ overview.summary?.passRate || 0 }}%</div>
        </div>
      </div>
      
      <div class="stat-card info">
        <div class="stat-icon">
          <el-icon><Reading /></el-icon>
        </div>
        <div class="stat-content">
          <div class="stat-label">已修课程</div>
          <div class="stat-value">{{ overview.summary?.totalCourses || 0 }}门</div>
        </div>
      </div>
    </div>
    
    <el-row :gutter="20">
      <el-col :xs="24" :lg="12">
        <div class="card">
          <div class="card-header">
            <h3>班级排名</h3>
          </div>
          <div class="card-body">
            <div class="ranking-display">
              <div class="rank-number">
                {{ overview.ranking?.classRank || '-' }}
                <span class="rank-total"> / {{ overview.ranking?.totalStudentsInClass || 0 }}</span>
              </div>
              <div class="rank-label">班级排名</div>
              <el-progress 
                v-if="overview.ranking?.totalStudentsInClass"
                :percentage="getRankPercentage(overview.ranking.classRank, overview.ranking.totalStudentsInClass)" 
                :color="'#22c55e'"
                :stroke-width="16"
              />
            </div>
          </div>
        </div>
      </el-col>
      
      <el-col :xs="24" :lg="12">
        <div class="card">
          <div class="card-header">
            <h3>年级排名</h3>
          </div>
          <div class="card-body">
            <div class="ranking-display">
              <div class="rank-number">
                {{ overview.ranking?.gradeRank || '-' }}
                <span class="rank-total"> / {{ overview.ranking?.totalStudentsInGrade || 0 }}</span>
              </div>
              <div class="rank-label">年级排名</div>
              <el-progress 
                v-if="overview.ranking?.totalStudentsInGrade"
                :percentage="getRankPercentage(overview.ranking.gradeRank, overview.ranking.totalStudentsInGrade)"
                :color="'#667eea'"
                :stroke-width="16"
              />
            </div>
          </div>
        </div>
      </el-col>
    </el-row>
    
    <div class="card" style="margin-top: 20px">
      <div class="card-header">
        <h3>成绩趋势</h3>
      </div>
      <div class="card-body">
        <v-chart :option="trendOption" autoresize style="height: 300px" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart } from 'echarts/charts'
import { GridComponent, TooltipComponent } from 'echarts/components'
import VChart from 'vue-echarts'
import { studentPortalApi } from '@/api/student'
import dayjs from 'dayjs'

use([CanvasRenderer, LineChart, GridComponent, TooltipComponent])

const overview = ref({})
const trendData = ref([])
const currentDate = dayjs().format('YYYY年MM月DD日 dddd')

const trendOption = computed(() => {
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { 
      type: 'category', 
      data: trendData.value.map(item => item.semester),
      boundaryGap: false
    },
    yAxis: { type: 'value', min: 0, max: 100 },
    series: [{
      type: 'line',
      data: trendData.value.map(item => parseFloat(item.avg_score).toFixed(1)),
      smooth: true,
      areaStyle: {
        color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [
          { offset: 0, color: 'rgba(34, 197, 94, 0.4)' },
          { offset: 1, color: 'rgba(34, 197, 94, 0.05)' }
        ]}
      },
      lineStyle: { color: '#22c55e', width: 3 },
      itemStyle: { color: '#22c55e' },
      symbol: 'circle',
      symbolSize: 8
    }]
  }
})

function getRankPercentage(rank, total) {
  if (!rank || !total) return 0
  return Math.round(((total - rank + 1) / total) * 100)
}

async function fetchData() {
  try {
    const [overviewRes, trendRes] = await Promise.all([
      studentPortalApi.getOverview(),
      studentPortalApi.getTrend()
    ])
    overview.value = overviewRes.data
    trendData.value = trendRes.data.trend || []
  } catch (error) {
    // 错误已处理
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="scss" scoped>
.student-dashboard {
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
    
    .date {
      color: #909399;
      font-size: 14px;
    }
  }
  
  .student-info-card {
    display: flex;
    align-items: center;
    gap: 20px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    color: #fff;
    
    .student-avatar {
      :deep(.el-avatar) {
        background: rgba(255, 255, 255, 0.2);
        font-size: 28px;
        font-weight: 600;
      }
    }
    
    .student-details {
      h2 {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 8px;
      }
      
      p {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 4px;
        
        &:last-child {
          margin-bottom: 0;
        }
      }
    }
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
    
    .stat-card {
      display: flex;
      align-items: center;
      gap: 16px;
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
      transition: transform 0.3s, box-shadow 0.3s;
      
      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      }
      
      .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #fff;
        
        &.primary {
          background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        &.success {
          background: linear-gradient(135deg, #22c55e, #16a34a);
        }
        
        &.warning {
          background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        &.info {
          background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
      }
      
      .stat-content {
        .stat-label {
          font-size: 13px;
          color: #909399;
          margin-bottom: 4px;
        }
        
        .stat-value {
          font-size: 24px;
          font-weight: 700;
          color: #303133;
        }
      }
    }
  }
  
  .card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    
    .card-header {
      padding: 16px 20px;
      border-bottom: 1px solid #f0f0f0;
      
      h3 {
        font-size: 16px;
        font-weight: 600;
        color: #303133;
      }
    }
    
    .card-body {
      padding: 20px;
    }
  }
  
  .ranking-display {
    text-align: center;
    
    .rank-number {
      font-size: 48px;
      font-weight: 700;
      color: #303133;
      margin-bottom: 8px;
      
      .rank-total {
        font-size: 20px;
        color: #909399;
        font-weight: 400;
      }
    }
    
    .rank-label {
      font-size: 14px;
      color: #909399;
      margin-bottom: 20px;
    }
    
    :deep(.el-progress) {
      max-width: 200px;
      margin: 0 auto;
    }
  }
}
</style>
