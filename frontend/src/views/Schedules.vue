<template>
  <div class="schedules-page">
    <div class="page-header">
      <h1>课程表管理</h1>
      <el-button type="primary" @click="handleAddTimetable">
        <el-icon><Plus /></el-icon>创建课程表
      </el-button>
    </div>
    
    <div class="card">
      <div class="search-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索课程表名称" clearable @keyup.enter="fetchData">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-select v-model="searchParams.semester" placeholder="选择学期" clearable @change="fetchData">
          <el-option v-for="s in semesters" :key="s" :label="s" :value="s" />
        </el-select>
        <el-button type="primary" @click="fetchData">搜索</el-button>
        <el-button @click="resetSearch">重置</el-button>
      </div>
      
      <el-table :data="tableData" v-loading="loading" stripe>
        <el-table-column prop="name" label="课程表名称" min-width="180">
          <template #default="{ row }">
            <el-link type="primary" @click="viewTimetable(row)">{{ row.name }}</el-link>
          </template>
        </el-table-column>
        <el-table-column prop="semester" label="学期" width="140" />
        <el-table-column label="日期范围" width="240">
          <template #default="{ row }">
            {{ row.week_start }} ~ {{ row.week_end }}
          </template>
        </el-table-column>
        <el-table-column prop="teacher_name" label="创建教师" width="120" />
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="viewTimetable(row)">查看</el-button>
            <el-button type="primary" link size="small" @click="handleEditTimetable(row)">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDeleteTimetable(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <div class="pagination-wrapper">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchData"
          @current-change="fetchData"
        />
      </div>
    </div>
    
    <el-dialog v-model="timetableDialogVisible" :title="isEditTimetable ? '编辑课程表' : '创建课程表'" width="500px" destroy-on-close>
      <el-form ref="timetableFormRef" :model="timetableForm" :rules="timetableRules" label-width="100px">
        <el-form-item label="课程表名称" prop="name">
          <el-input v-model="timetableForm.name" placeholder="请输入课程表名称" />
        </el-form-item>
        <el-form-item label="学期">
          <el-input v-model="timetableForm.semester" placeholder="如：2024-2025-1" />
        </el-form-item>
        <el-form-item label="周开始日期" prop="weekStart">
          <el-date-picker v-model="timetableForm.weekStart" type="date" placeholder="选择开始日期" value-format="YYYY-MM-DD" style="width: 100%" />
        </el-form-item>
        <el-form-item label="周结束日期" prop="weekEnd">
          <el-date-picker v-model="timetableForm.weekEnd" type="date" placeholder="选择结束日期" value-format="YYYY-MM-DD" style="width: 100%" />
        </el-form-item>
        <el-form-item label="创建教师" prop="teacherId">
          <el-select v-model="timetableForm.teacherId" placeholder="选择教师" style="width: 100%">
            <el-option v-for="t in teachers" :key="t.id" :label="t.real_name" :value="t.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="timetableForm.description" type="textarea" rows="2" placeholder="备注信息" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="timetableDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSaveTimetable">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import { scheduleApi } from '@/api'
import { showSuccess } from '@/utils/request'

const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const tableData = ref([])
const semesters = ref([])
const teachers = ref([])
const timetableDialogVisible = ref(false)
const isEditTimetable = ref(false)
const timetableFormRef = ref(null)

const searchParams = reactive({ keyword: '', semester: null })
const pagination = reactive({ page: 1, pageSize: 20, total: 0 })
const timetableForm = reactive({
  id: null,
  name: '',
  semester: '',
  weekStart: '',
  weekEnd: '',
  teacherId: null,
  description: ''
})

const timetableRules = {
  name: [{ required: true, message: '请输入课程表名称', trigger: 'blur' }],
  weekStart: [{ required: true, message: '请选择开始日期', trigger: 'change' }],
  weekEnd: [{ required: true, message: '请选择结束日期', trigger: 'change' }],
  teacherId: [{ required: true, message: '请选择教师', trigger: 'change' }]
}

async function fetchData() {
  loading.value = true
  try {
    const res = await scheduleApi.getList({ page: pagination.page, pageSize: pagination.pageSize, ...searchParams })
    tableData.value = res.data.items
    pagination.total = res.data.total
    semesters.value = res.data.semesters || []
    teachers.value = res.data.teachers || []
  } finally {
    loading.value = false
  }
}

function resetSearch() {
  Object.assign(searchParams, { keyword: '', semester: null })
  pagination.page = 1
  fetchData()
}

function viewTimetable(row) {
  router.push(`/schedules/${row.id}`)
}

function handleAddTimetable() {
  isEditTimetable.value = false
  Object.assign(timetableForm, {
    id: null,
    name: '',
    semester: '',
    weekStart: '',
    weekEnd: '',
    teacherId: null,
    description: ''
  })
  timetableDialogVisible.value = true
}

function handleEditTimetable(row) {
  isEditTimetable.value = true
  Object.assign(timetableForm, {
    id: row.id,
    name: row.name,
    semester: row.semester,
    weekStart: row.week_start,
    weekEnd: row.week_end,
    teacherId: row.teacher_id,
    description: row.description
  })
  timetableDialogVisible.value = true
}

async function handleSaveTimetable() {
  const valid = await timetableFormRef.value.validate().catch(() => false)
  if (!valid) return
  saving.value = true
  try {
    if (isEditTimetable.value) {
      await scheduleApi.update(timetableForm.id, timetableForm)
      showSuccess('更新成功')
    } else {
      await scheduleApi.create(timetableForm)
      showSuccess('创建成功')
    }
    timetableDialogVisible.value = false
    fetchData()
  } finally {
    saving.value = false
  }
}

async function handleDeleteTimetable(row) {
  try {
    await ElMessageBox.confirm(`确定要删除课程表"${row.name}"吗？`, '提示', { type: 'warning' })
    await scheduleApi.delete(row.id)
    showSuccess('删除成功')
    fetchData()
  } catch (e) {}
}

onMounted(() => { fetchData() })
</script>

<style scoped lang="scss">
.schedules-page {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    
    h1 {
      font-size: 22px;
      margin: 0;
    }
  }
  
  .card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
  }
  
  .search-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }
  
  .pagination-wrapper {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
  }
}
</style>
