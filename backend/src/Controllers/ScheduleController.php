<?php
/**
 * 课程表管理控制器
 */

namespace App\Controllers;

use App\Models\Schedule;
use App\Models\User;
use App\Models\Classes;
use App\Models\Course;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class ScheduleController
{
    private Schedule $scheduleModel;
    private User $userModel;
    private Classes $classModel;
    private Course $courseModel;
    
    public function __construct()
    {
        $this->scheduleModel = new Schedule();
        $this->userModel = new User();
        $this->classModel = new Classes();
        $this->courseModel = new Course();
    }
    
    /**
     * 获取课程表列表
     */
    public function index(array $params): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $keyword = $_GET['keyword'] ?? null;
        $semester = $_GET['semester'] ?? null;
        $teacherId = isset($_GET['teacherId']) ? (int)$_GET['teacherId'] : null;
        
        $result = $this->scheduleModel->getTimetables($page, $pageSize, $teacherId, $semester, $keyword);
        $semesters = $this->scheduleModel->getSemesters();
        $teachers = $this->userModel->getTeachers();
        
        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => ceil($result['total'] / $result['pageSize']),
            'semesters' => $semesters,
            'teachers' => $teachers
        ]);
    }
    
    /**
     * 获取课程表详情（包含课程项）
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $timetable = $this->scheduleModel->getTimetableWithItems($id);
        
        if (!$timetable) {
            Response::error('课程表不存在', 404);
            return;
        }
        
        $classes = $this->classModel->getAllForSelect();
        $teachers = $this->userModel->getTeachers();
        $courses = $this->courseModel->getAllForSelect();
        
        Response::success([
            'timetable' => $timetable,
            'classes' => $classes,
            'teachers' => $teachers,
            'courses' => $courses
        ]);
    }
    
    /**
     * 创建课程表
     */
    public function store(array $params): void
    {
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('name', '课程表名称不能为空')
                  ->required('weekStart', '周开始日期不能为空')
                  ->required('weekEnd', '周结束日期不能为空')
                  ->required('teacherId', '请选择创建教师');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        $timetableId = $this->scheduleModel->createTimetable($data);
        
        Logger::info("Schedule timetable created: {$data['name']}");
        
        Response::success(['id' => $timetableId], '创建成功');
    }
    
    /**
     * 更新课程表
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $timetable = $this->scheduleModel->find($id);
        
        if (!$timetable) {
            Response::error('课程表不存在', 404);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('name', '课程表名称不能为空')
                  ->required('weekStart', '周开始日期不能为空')
                  ->required('weekEnd', '周结束日期不能为空');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        $this->scheduleModel->updateTimetable($id, $data);
        
        Logger::info("Schedule timetable updated: {$data['name']}");
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 删除课程表
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $timetable = $this->scheduleModel->find($id);
        
        if (!$timetable) {
            Response::error('课程表不存在', 404);
            return;
        }
        
        $this->scheduleModel->delete($id);
        
        Logger::info("Schedule timetable deleted: {$timetable['name']}");
        
        Response::success(null, '删除成功');
    }
    
    /**
     * 添加课程项
     */
    public function addItem(array $params): void
    {
        $timetableId = (int) $params['id'];
        $data = $this->getInput();
        $data['timetableId'] = $timetableId;
        
        $validator = new Validator($data);
        $validator->required('courseName', '课程名称不能为空')
                  ->required('dayOfWeek', '请选择星期')
                  ->required('startSlot', '请选择开始节次')
                  ->required('endSlot', '请选择结束节次');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        if ($data['startSlot'] > $data['endSlot']) {
            Response::error('开始节次不能大于结束节次', 400);
            return;
        }
        
        $conflicts = $this->scheduleModel->checkConflict(
            $timetableId,
            $data['dayOfWeek'],
            $data['startSlot'],
            $data['endSlot']
        );
        
        if (!empty($conflicts)) {
            Response::error('该时间段与现有课程冲突，请调整时间', 400);
            return;
        }
        
        $itemId = $this->scheduleModel->addItem($data);
        
        Logger::info("Schedule item added: {$data['courseName']}");
        
        $item = $this->scheduleModel->getItemsByTimetable($timetableId);
        
        Response::success(['id' => $itemId, 'items' => $item], '添加成功');
    }
    
    /**
     * 更新课程项
     */
    public function updateItem(array $params): void
    {
        $timetableId = (int) $params['id'];
        $itemId = (int) $params['itemId'];
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('courseName', '课程名称不能为空')
                  ->required('dayOfWeek', '请选择星期')
                  ->required('startSlot', '请选择开始节次')
                  ->required('endSlot', '请选择结束节次');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        if ($data['startSlot'] > $data['endSlot']) {
            Response::error('开始节次不能大于结束节次', 400);
            return;
        }
        
        $conflicts = $this->scheduleModel->checkConflict(
            $timetableId,
            $data['dayOfWeek'],
            $data['startSlot'],
            $data['endSlot'],
            $itemId
        );
        
        if (!empty($conflicts)) {
            Response::error('该时间段与现有课程冲突，请调整时间', 400);
            return;
        }
        
        $this->scheduleModel->updateItem($itemId, $data);
        
        Logger::info("Schedule item updated: {$data['courseName']}");
        
        $items = $this->scheduleModel->getItemsByTimetable($timetableId);
        
        Response::success(['items' => $items], '更新成功');
    }
    
    /**
     * 删除课程项
     */
    public function deleteItem(array $params): void
    {
        $timetableId = (int) $params['id'];
        $itemId = (int) $params['itemId'];
        
        $this->scheduleModel->deleteItem($itemId);
        
        Logger::info("Schedule item deleted: {$itemId}");
        
        $items = $this->scheduleModel->getItemsByTimetable($timetableId);
        
        Response::success(['items' => $items], '删除成功');
    }
    
    /**
     * 拖拽更新课程项位置
     */
    public function moveItem(array $params): void
    {
        $timetableId = (int) $params['id'];
        $itemId = (int) $params['itemId'];
        $data = $this->getInput();
        
        $conflicts = $this->scheduleModel->checkConflict(
            $timetableId,
            $data['dayOfWeek'],
            $data['startSlot'],
            $data['endSlot'],
            $itemId
        );
        
        if (!empty($conflicts)) {
            Response::error('该时间段与现有课程冲突，请调整时间', 400);
            return;
        }
        
        $sql = "UPDATE schedule_items SET day_of_week = ?, start_slot = ?, end_slot = ? WHERE id = ?";
        $this->scheduleModel->execute($sql, [
            $data['dayOfWeek'],
            $data['startSlot'],
            $data['endSlot'],
            $itemId
        ]);
        
        Logger::info("Schedule item moved: {$itemId}");
        
        $items = $this->scheduleModel->getItemsByTimetable($timetableId);
        
        Response::success(['items' => $items], '移动成功');
    }
    
    /**
     * 检查冲突
     */
    public function checkConflict(array $params): void
    {
        $timetableId = (int) $params['id'];
        $dayOfWeek = (int) ($_GET['dayOfWeek'] ?? 0);
        $startSlot = (int) ($_GET['startSlot'] ?? 0);
        $endSlot = (int) ($_GET['endSlot'] ?? 0);
        $excludeItemId = isset($_GET['itemId']) ? (int)$_GET['itemId'] : null;
        
        $conflicts = $this->scheduleModel->checkConflict($timetableId, $dayOfWeek, $startSlot, $endSlot, $excludeItemId);
        
        Response::success([
            'hasConflict' => !empty($conflicts),
            'conflicts' => $conflicts
        ]);
    }
    
    /**
     * 获取选项数据
     */
    public function options(array $params): void
    {
        $classes = $this->classModel->getAllForSelect();
        $teachers = $this->userModel->getTeachers();
        $courses = $this->courseModel->getAllForSelect();
        $semesters = $this->scheduleModel->getSemesters();
        
        Response::success([
            'classes' => $classes,
            'teachers' => $teachers,
            'courses' => $courses,
            'semesters' => $semesters
        ]);
    }
    
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
