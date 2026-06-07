<?php
/**
 * 课程表模型
 */

namespace App\Models;

class Schedule extends BaseModel
{
    protected string $table = 'schedule_timetables';
    protected string $itemTable = 'schedule_items';
    
    /**
     * 获取课程表列表（分页）
     */
    public function getTimetables(int $page, int $pageSize, ?int $teacherId = null, ?string $semester = null, ?string $keyword = null): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($teacherId) {
            $where[] = "st.teacher_id = ?";
            $params[] = $teacherId;
        }
        
        if ($semester) {
            $where[] = "st.semester = ?";
            $params[] = $semester;
        }
        
        if ($keyword) {
            $where[] = "st.name LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT st.*, u.real_name as teacher_name
                FROM {$this->table} st
                LEFT JOIN users u ON st.teacher_id = u.id
                WHERE {$whereStr}
                ORDER BY st.week_start DESC, st.created_at DESC
                LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->query($sql, $params);
        
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} st WHERE {$whereStr}";
        $countResult = $this->query($countSql, $params);
        $total = (int) $countResult[0]['count'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
    
    /**
     * 创建课程表
     */
    public function createTimetable(array $data): int
    {
        return $this->create([
            'name' => $data['name'],
            'week_start' => $data['weekStart'],
            'week_end' => $data['weekEnd'],
            'semester' => $data['semester'] ?? null,
            'teacher_id' => $data['teacherId'],
            'description' => $data['description'] ?? null,
            'status' => 1
        ]);
    }
    
    /**
     * 更新课程表
     */
    public function updateTimetable(int $id, array $data): bool
    {
        return $this->update($id, [
            'name' => $data['name'],
            'week_start' => $data['weekStart'],
            'week_end' => $data['weekEnd'],
            'semester' => $data['semester'] ?? null,
            'description' => $data['description'] ?? null
        ]);
    }
    
    /**
     * 获取课程表详情（包含课程项）
     */
    public function getTimetableWithItems(int $id): ?array
    {
        $timetable = $this->find($id);
        if (!$timetable) {
            return null;
        }
        
        $sql = "SELECT si.*, c.name as class_name_ref, u.real_name as teacher_name_ref
                FROM {$this->itemTable} si
                LEFT JOIN classes c ON si.class_id = c.id
                LEFT JOIN users u ON si.teacher_id = u.id
                WHERE si.timetable_id = ?
                ORDER BY si.day_of_week, si.start_slot";
        $items = $this->query($sql, [$id]);
        
        $timetable['items'] = $items;
        return $timetable;
    }
    
    /**
     * 添加课程项
     */
    public function addItem(array $data): int
    {
        $sql = "INSERT INTO {$this->itemTable} 
                (timetable_id, course_name, class_id, class_name, day_of_week, start_slot, end_slot, 
                 start_time, end_time, location, teacher_id, teacher_name, color, remark)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->execute($sql, [
            $data['timetableId'],
            $data['courseName'],
            $data['classId'] ?? null,
            $data['className'] ?? null,
            $data['dayOfWeek'],
            $data['startSlot'],
            $data['endSlot'],
            $data['startTime'] ?? null,
            $data['endTime'] ?? null,
            $data['location'] ?? null,
            $data['teacherId'] ?? null,
            $data['teacherName'] ?? null,
            $data['color'] ?? '#409EFF',
            $data['remark'] ?? null
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * 更新课程项
     */
    public function updateItem(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->itemTable} SET
                course_name = ?, class_id = ?, class_name = ?, day_of_week = ?, 
                start_slot = ?, end_slot = ?, start_time = ?, end_time = ?, 
                location = ?, teacher_id = ?, teacher_name = ?, color = ?, remark = ?
                WHERE id = ?";
        
        return $this->execute($sql, [
            $data['courseName'],
            $data['classId'] ?? null,
            $data['className'] ?? null,
            $data['dayOfWeek'],
            $data['startSlot'],
            $data['endSlot'],
            $data['startTime'] ?? null,
            $data['endTime'] ?? null,
            $data['location'] ?? null,
            $data['teacherId'] ?? null,
            $data['teacherName'] ?? null,
            $data['color'] ?? '#409EFF',
            $data['remark'] ?? null,
            $id
        ]);
    }
    
    /**
     * 删除课程项
     */
    public function deleteItem(int $id): bool
    {
        $sql = "DELETE FROM {$this->itemTable} WHERE id = ?";
        return $this->execute($sql, [$id]);
    }
    
    /**
     * 检查时间冲突
     */
    public function checkConflict(int $timetableId, int $dayOfWeek, int $startSlot, int $endSlot, ?int $excludeItemId = null): array
    {
        $where = [
            'timetable_id = ?',
            'day_of_week = ?',
            'start_slot < ?',
            'end_slot > ?'
        ];
        $params = [$timetableId, $dayOfWeek, $endSlot, $startSlot];
        
        if ($excludeItemId) {
            $where[] = 'id != ?';
            $params[] = $excludeItemId;
        }
        
        $whereStr = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->itemTable} WHERE {$whereStr}";
        return $this->query($sql, $params);
    }
    
    /**
     * 获取所有学期
     */
    public function getSemesters(): array
    {
        $sql = "SELECT DISTINCT semester FROM {$this->table} WHERE semester IS NOT NULL ORDER BY semester DESC";
        $result = $this->query($sql);
        return array_column($result, 'semester');
    }
    
    /**
     * 批量获取课程项
     */
    public function getItemsByTimetable(int $timetableId): array
    {
        $sql = "SELECT * FROM {$this->itemTable} WHERE timetable_id = ? ORDER BY day_of_week, start_slot";
        return $this->query($sql, [$timetableId]);
    }
}
