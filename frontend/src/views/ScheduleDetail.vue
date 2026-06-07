<template>
  <div class="schedule-detail-page">
    <div class="page-header">
      <div class="header-left">
        <el-button @click="goBack">
          <el-icon><ArrowLeft /></el-icon>返回
        </el-button>
        <h1>{{ timetable?.name || '课程表详情' }}</h1>
        <span class="date-range">{{ timetable?.week_start }} ~ {{ timetable?.week_end }}</span>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleAddItem">
          <el-icon><Plus /></el-icon>添加课程
        </el-button>
      </div>
    </div>
    
    <div class="schedule-container" v-loading="loading">
      <div class="timetable-wrapper">
        <div class="timetable-header">
          <div class="time-col-header"></div>
          <div class="day-col" v-for="day in weekDays" :key="day.value">
            <div class="day-name">{{ day.label }}</div>
          </div>
        </div>
        
        <div class="timetable-body">
          <div class="time-column">
            <div class="time-slot" v-for="slot in timeSlots" :key="slot.slot" :style="{ height: slotHeight + 'px' }">
              <div class="slot-label">
                <span class="slot-num">第{{ slot.slot }}节</span>
                <span class="slot-time">{{ slot.startTime }}</span>
              </div>
            </div>
          </div>
          
          <div class="day-columns">
            <div 
              class="day-column" 
              v-for="day in weekDays" 
              :key="day.value"
              @click.self="handleCellClick(day.value, 1)"
            >
              <div 
                class="day-cell" 
                v-for="slot in timeSlots" 
                :key="slot.slot"
                :data-day="day.value"
                :data-slot="slot.slot"
                :style="{ height: slotHeight + 'px' }"
                @click="handleCellClick(day.value, slot.slot)"
                @dragover.prevent
                @drop="handleDrop($event, day.value, slot.slot)"
              ></div>
              
              <div 
                v-for="item in getItemsByDay(day.value)" 
                :key="item.id"
                class="schedule-item"
                :style="getItemStyle(item)"
                :style="{ backgroundColor: item.color + '20', borderLeftColor: item.color }"
                draggable="true"
                @dragstart="handleDragStart($event, item)"
                @dragend="handleDragEnd"
                @click.stop="handleItemClick(item)"
              >
                <div class="item-content">
                  <div class="item-name">{{ item.course_name }}</div>
                  <div class="item-info" v-if="item.location">
                    <el-icon><Location /></el-icon>{{ item.location }}
                  </div>
                  <div class="item-info" v-if="item.teacher_name || item.teacher_name_ref">
                    <el-icon><User /></el-icon>{{ item.teacher_name || item.teacher_name_ref }}
                  </div>
                  <div class="item-info" v-if="item.class_name || item.class_name_ref">
                    <el-icon><School /></el-icon>{{ item.class_name || item.class_name_ref }}
                  </div>
                </div>
                <div class="item-actions">
                  <el-button 
                    type="primary" 
                    link 
                    size="small" 
                    @click.stop="handleEditItem(item)"
                  >编辑</el-button>
                  <el-button 
                    type="danger" 
                    link 
                    size="small" 
                    @click.stop="handleDeleteItem(item)"
                  >删除</el-button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <el-dialog v-model="itemDialogVisible" :title="isEditItem ? '编辑课程' : '添加课程'" width="600px" destroy-on-close>
      <el-form ref="itemFormRef" :model="itemForm" :rules="itemRules" label-width="100px">
        <el-form-item label="课程名称" prop="courseName">
          <el-select 
            v-model="itemForm.courseName" 
            placeholder="选择课程或手动输入" 
            filterable
            allow-create
            default-first-option
            style="width: 100%"
          >
            <el-option v-for="c in courses" :key="c.id" :label="c.name" :value="c.name" />
          </el-select>
        </el-form-item>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="星期" prop="dayOfWeek">
              <el-select v-model="itemForm.dayOfWeek" placeholder="选择星期" style="width: 100%">
                <el-option v-for="day in weekDays" :key="day.value" :label="day.label" :value="day.value" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="课程颜色">
              <el-color-picker v-model="itemForm.color" show-alpha />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="开始节次" prop="startSlot">
              <el-select v-model="itemForm.startSlot" placeholder="开始节次" style="width: 100%" @change="checkConflict">
                <el-option v-for="slot in timeSlots" :key="slot.slot" :label="`第${slot.slot}节`" :value="slot.slot" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="结束节次" prop="endSlot">
              <el-select v-model="itemForm.endSlot" placeholder="结束节次" style="width: 100%" @change="checkConflict">
                <el-option v-for="slot in timeSlots" :key="slot.slot" :label="`第${slot.slot}节`" :value="slot.slot" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="授课班级">
          <el-select v-model="itemForm.classId" placeholder="选择班级" clearable style="width: 100%">
            <el-option v-for="cls in classes" :key="cls.id" :label="cls.name" :value="cls.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="授课地点">
          <el-input v-model="itemForm.location" placeholder="如：教学楼A-301" />
        </el-form-item>
        <el-form-item label="授课教师">
          <el-select v-model="itemForm.teacherId" placeholder="选择教师" clearable style="width: 100%">
            <el-option v-for="t in teachers" :key="t.id" :label="t.real_name" :value="t.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="itemForm.remark" type="textarea" rows="2" placeholder="备注信息" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="itemDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSaveItem">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import { scheduleApi } from '@/api'
import { showSuccess, showError } from '@/utils/request'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const timetableId = computed(() => parseInt(route.params.id))
const timetable = ref(null)
const items = ref([])
const classes = ref([])
const teachers = ref([])
const courses = ref([])
const itemDialogVisible = ref(false)
const isEditItem = ref(false)
const itemFormRef = ref(null)
const draggedItem = ref(null)

const slotHeight = 80
const weekDays = [
  { label: '周一', value: 1 },
  { label: '周二', value: 2 },
  { label: '周三', value: 3 },
  { label: '周四', value: 4 },
  { label: '周五', value: 5 },
  { label: '周六', value: 6 },
  { label: '周日', value: 7 }
]
const timeSlots = [
  { slot: 1, startTime: '08:00', endTime: '08:45' },
  { slot: 2, startTime: '08:55', endTime: '09:40' },
  { slot: 3, startTime: '10:00', endTime: '10:45' },
  { slot: 4, startTime: '10:55', endTime: '11:40' },
  { slot: 5, startTime: '14:00', endTime: '14:45' },
  { slot: 6, startTime: '14:55', endTime: '15:40' },
  { slot: 7, startTime: '16:00', endTime: '16:45' },
  { slot: 8, startTime: '16:55', endTime: '17:40' },
  { slot: 9, startTime: '19:00', endTime: '19:45' },
  { slot: 10, startTime: '19:55', endTime: '20:40' }
]

const itemForm = reactive({
  id: null,
  courseName: '',
  classId: null,
  className: '',
  dayOfWeek: 1,
  startSlot: 1,
  endSlot: 2,
  startTime: '',
  endTime: '',
  location: '',
  teacherId: null,
  teacherName: '',
  color: '#409EFF',
  remark: ''
})

const itemRules = {
  courseName: [{ required: true, message: '请输入课程名称', trigger: 'blur' }],
  dayOfWeek: [{ required: true, message: '请选择星期', trigger: 'change' }],
  startSlot: [{ required: true, message: '请选择开始节次', trigger: 'change' }],
  endSlot: [{ required: true, message: '请选择结束节次', trigger: 'change' }]
}

async function fetchTimetable() {
  loading.value = true
  try {
    const res = await scheduleApi.getById(timetableId.value)
    timetable.value = res.data.timetable
    items.value = res.data.timetable.items || []
    classes.value = res.data.classes || []
    teachers.value = res.data.teachers || []
    courses.value = res.data.courses || []
  } finally {
    loading.value = false
  }
}

function getItemsByDay(day) {
  return items.value.filter(item => item.day_of_week === day)
}

function getItemStyle(item) {
  const top = (item.start_slot - 1) * slotHeight
  const height = (item.end_slot - item.start_slot + 1) * slotHeight - 4
  return {
    top: top + 'px',
    height: height + 'px'
  }
}

function goBack() {
  router.push('/schedules')
}

function handleAddItem() {
  isEditItem.value = false
  Object.assign(itemForm, {
    id: null,
    courseName: '',
    classId: null,
    className: '',
    dayOfWeek: 1,
    startSlot: 1,
    endSlot: 2,
    startTime: '',
    endTime: '',
    location: '',
    teacherId: null,
    teacherName: '',
    color: '#409EFF',
    remark: ''
  })
  itemDialogVisible.value = true
}

function handleCellClick(day, slot) {
  if (draggedItem.value) return
  isEditItem.value = false
  Object.assign(itemForm, {
    id: null,
    courseName: '',
    classId: null,
    className: '',
    dayOfWeek: day,
    startSlot: slot,
    endSlot: slot,
    startTime: '',
    endTime: '',
    location: '',
    teacherId: null,
    teacherName: '',
    color: '#409EFF',
    remark: ''
  })
  itemDialogVisible.value = true
}

function handleItemClick(item) {
}

function handleEditItem(item) {
  isEditItem.value = true
  Object.assign(itemForm, {
    id: item.id,
    courseName: item.course_name,
    classId: item.class_id,
    className: item.class_name,
    dayOfWeek: item.day_of_week,
    startSlot: item.start_slot,
    endSlot: item.end_slot,
    startTime: item.start_time,
    endTime: item.end_time,
    location: item.location,
    teacherId: item.teacher_id,
    teacherName: item.teacher_name,
    color: item.color,
    remark: item.remark
  })
  itemDialogVisible.value = true
}

async function handleDeleteItem(item) {
  try {
    await ElMessageBox.confirm(`确定要删除课程"${item.course_name}"吗？`, '提示', { type: 'warning' })
    const res = await scheduleApi.deleteItem(timetableId.value, item.id)
    items.value = res.data.items
    showSuccess('删除成功')
  } catch (e) {}
}

async function checkConflict() {
  if (itemForm.startSlot > itemForm.endSlot) {
    showError('开始节次不能大于结束节次')
    return false
  }
  
  try {
    const res = await scheduleApi.checkConflict(timetableId.value, {
      dayOfWeek: itemForm.dayOfWeek,
      startSlot: itemForm.startSlot,
      endSlot: itemForm.endSlot,
      itemId: isEditItem.value ? itemForm.id : null
    })
    
    if (res.data.hasConflict) {
      showError('该时间段与现有课程冲突，请调整时间')
      return false
    }
    return true
  } catch (e) {
    return false
  }
}

async function handleSaveItem() {
  const valid = await itemFormRef.value.validate().catch(() => false)
  if (!valid) return
  
  if (!await checkConflict()) return
  
  saving.value = true
  try {
    if (isEditItem.value) {
      const res = await scheduleApi.updateItem(timetableId.value, itemForm.id, itemForm)
      items.value = res.data.items
      showSuccess('更新成功')
    } else {
      const res = await scheduleApi.addItem(timetableId.value, itemForm)
      items.value = res.data.items
      showSuccess('添加成功')
    }
    itemDialogVisible.value = false
  } finally {
    saving.value = false
  }
}

function handleDragStart(e, item) {
  draggedItem.value = item
  e.dataTransfer.effectAllowed = 'move'
}

function handleDragEnd() {
  draggedItem.value = null
}

async function handleDrop(e, day, slot) {
  if (!draggedItem.value) return
  
  const item = draggedItem.value
  const duration = item.end_slot - item.start_slot
  const newEndSlot = slot + duration
  
  if (newEndSlot > timeSlots.length) {
    showError('超出时间范围')
    return
  }
  
  try {
    const res = await scheduleApi.moveItem(timetableId.value, item.id, {
      dayOfWeek: day,
      startSlot: slot,
      endSlot: newEndSlot
    })
    items.value = res.data.items
    showSuccess('移动成功')
  } catch (e) {}
  
  draggedItem.value = null
}

onMounted(() => { fetchTimetable() })
</script>

<style scoped lang="scss">
.schedule-detail-page {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    
    .header-left {
      display: flex;
      align-items: center;
      gap: 15px;
      
      h1 {
        font-size: 22px;
        margin: 0;
      }
      
      .date-range {
        color: #666;
        font-size: 14px;
      }
    }
  }
  
  .schedule-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
    overflow-x: auto;
  }
  
  .timetable-wrapper {
    min-width: 900px;
  }
  
  .timetable-header {
    display: flex;
    border-bottom: 2px solid #ebeef5;
    
    .time-col-header {
      width: 100px;
      flex-shrink: 0;
    }
    
    .day-col {
      flex: 1;
      text-align: center;
      padding: 12px 0;
      font-weight: 600;
      color: #303133;
      border-left: 1px solid #ebeef5;
    }
  }
  
  .timetable-body {
    display: flex;
    
    .time-column {
      width: 100px;
      flex-shrink: 0;
      border-right: 1px solid #ebeef5;
      
      .time-slot {
        border-bottom: 1px solid #ebeef5;
        display: flex;
        align-items: center;
        justify-content: center;
        
        .slot-label {
          text-align: center;
          font-size: 12px;
          color: #606266;
          
          .slot-num {
            display: block;
            font-weight: 600;
            margin-bottom: 2px;
          }
          
          .slot-time {
            display: block;
            color: #909399;
          }
        }
      }
    }
    
    .day-columns {
      flex: 1;
      display: flex;
      position: relative;
    }
    
    .day-column {
      flex: 1;
      position: relative;
      border-left: 1px solid #ebeef5;
      
      .day-cell {
        border-bottom: 1px solid #ebeef5;
        cursor: pointer;
        transition: background-color 0.2s;
        
        &:hover {
          background-color: #f5f7fa;
        }
      }
      
      .schedule-item {
        position: absolute;
        left: 4px;
        right: 4px;
        border-radius: 6px;
        padding: 8px;
        cursor: move;
        border-left: 4px solid;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
        z-index: 10;
        
        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
          z-index: 20;
          
          .item-actions {
            opacity: 1;
          }
        }
        
        .item-content {
          .item-name {
            font-weight: 600;
            font-size: 14px;
            color: #303133;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          
          .item-info {
            font-size: 12px;
            color: #606266;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
        }
        
        .item-actions {
          position: absolute;
          bottom: 4px;
          right: 8px;
          opacity: 0;
          transition: opacity 0.2s;
          display: flex;
          gap: 4px;
        }
      }
    }
  }
}
</style>
