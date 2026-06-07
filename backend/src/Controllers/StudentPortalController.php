<?php
/**
 * 学生端控制器
 */

namespace App\Controllers;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Course;
use App\Models\ClassModel;
use App\Utils\Response;
use App\Utils\Logger;

class StudentPortalController
{
    private Grade $gradeModel;
    private Student $studentModel;
    private Course $courseModel;
    private ClassModel $classModel;
    
    public function __construct()
    {
        $this->gradeModel = new Grade();
        $this->studentModel = new Student();
        $this->courseModel = new Course();
        $this->classModel = new ClassModel();
    }
    
    /**
     * 获取学生个人信息
     */
    public function profile(array $params): void
    {
        $studentId = (int) $params['_user']['id'];
        
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        $class = $student['class_id'] ? $this->classModel->find($student['class_id']) : null;
        
        unset($student['password']);
        
        Response::success([
            'student' => $student,
            'class' => $class
        ]);
    }
    
    /**
     * 获取学生成绩总览（仪表盘）
     */
    public function overview(array $params): void
    {
        $studentId = (int) $params['_user']['id'];
        $semester = $_GET['semester'] ?? null;
        
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        $class = $student['class_id'] ? $this->classModel->find($student['class_id']) : null;
        
        $where = "g.student_id = ?";
        $queryParams = [$studentId];
        
        if ($semester) {
            $where .= " AND g.semester = ?";
            $queryParams[] = $semester;
        }
        
        $sql = "SELECT 
                    AVG(g.score) as avg_score,
                    MAX(g.score) as max_score,
                    MIN(g.score) as min_score,
                    COUNT(*) as total_courses,
                    SUM(CASE WHEN g.score >= 60 THEN 1 ELSE 0 END) as pass_count
                FROM grades g
                WHERE {$where}";
        $statsResult = $this->gradeModel->query($sql, $queryParams);
        $stats = $statsResult[0] ?? [];
        
        $passRate = $stats['total_courses'] > 0 
            ? round(($stats['pass_count'] / $stats['total_courses']) * 100, 2) 
            : 0;
        
        $classRank = null;
        $totalStudents = 0;
        $gradeRank = null;
        $totalGradeStudents = 0;
        
        if ($class) {
            $classRankWhere = "s.class_id = ?";
            $rankParams = [$class['id']];
            
            if ($semester) {
                $classRankWhere .= " AND g.semester = ?";
                $rankParams[] = $semester;
            }
            
            $sql = "SELECT s.id as student_id, AVG(g.score) as avg_score
                    FROM grades g
                    JOIN students s ON g.student_id = s.id
                    WHERE {$classRankWhere}
                    GROUP BY s.id
                    ORDER BY avg_score DESC";
            $classRankings = $this->gradeModel->query($sql, $rankParams);
            
            $totalStudents = count($classRankings);
            
            foreach ($classRankings as $index => $rank) {
                if ($rank['student_id'] == $studentId) {
                    $classRank = $index + 1;
                    break;
                }
            }
            
            if ($class['grade']) {
                $gradeRankWhere = "c.grade = ?";
                $gradeParams = [$class['grade']];
                
                if ($semester) {
                    $gradeRankWhere .= " AND g.semester = ?";
                    $gradeParams[] = $semester;
                }
                
                $sql = "SELECT s.id as student_id, AVG(g.score) as avg_score
                        FROM grades g
                        JOIN students s ON g.student_id = s.id
                        JOIN classes c ON s.class_id = c.id
                        WHERE {$gradeRankWhere}
                        GROUP BY s.id
                        ORDER BY avg_score DESC";
                $gradeRankings = $this->gradeModel->query($sql, $gradeParams);
                
                $totalGradeStudents = count($gradeRankings);
                
                foreach ($gradeRankings as $index => $rank) {
                    if ($rank['student_id'] == $studentId) {
                        $gradeRank = $index + 1;
                        break;
                    }
                }
            }
        }
        
        Response::success([
            'student' => [
                'id' => $student['id'],
                'studentNo' => $student['student_no'],
                'name' => $student['name'],
                'gender' => $student['gender']
            ],
            'class' => $class,
            'summary' => [
                'avgScore' => round($stats['avg_score'] ?? 0, 2),
                'maxScore' => $stats['max_score'] ?? 0,
                'minScore' => $stats['min_score'] ?? 0,
                'totalCourses' => $stats['total_courses'] ?? 0,
                'passCount' => $stats['pass_count'] ?? 0,
                'passRate' => $passRate
            ],
            'ranking' => [
                'classRank' => $classRank,
                'totalStudentsInClass' => $totalStudents,
                'gradeRank' => $gradeRank,
                'totalStudentsInGrade' => $totalGradeStudents
            ]
        ]);
    }
    
    /**
     * 获取学生课程成绩列表
     */
    public function courses(array $params): void
    {
        $studentId = (int) $params['_user']['id'];
        $semester = $_GET['semester'] ?? null;
        
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        $where = "g.student_id = ?";
        $queryParams = [$studentId];
        
        if ($semester) {
            $where .= " AND g.semester = ?";
            $queryParams[] = $semester;
        }
        
        $sql = "SELECT 
                    g.id,
                    g.course_id,
                    c.code as course_code,
                    c.name as course_name,
                    c.credits,
                    c.course_type,
                    g.score,
                    g.grade_level,
                    g.semester,
                    g.exam_type,
                    g.remark
                FROM grades g
                JOIN courses c ON g.course_id = c.id
                WHERE {$where}
                ORDER BY g.semester DESC, c.code";
        $grades = $this->gradeModel->query($sql, $queryParams);
        
        foreach ($grades as &$grade) {
            $courseRank = $this->getCourseRank($studentId, $grade['course_id'], $grade['semester']);
            $grade['classRank'] = $courseRank['classRank'];
            $grade['classTotal'] = $courseRank['classTotal'];
            $grade['gradeRank'] = $courseRank['gradeRank'];
            $grade['gradeTotal'] = $courseRank['gradeTotal'];
        }
        
        $sql = "SELECT DISTINCT semester FROM grades WHERE student_id = ? ORDER BY semester DESC";
        $semesters = $this->gradeModel->query($sql, [$studentId]);
        
        Response::success([
            'grades' => $grades,
            'semesters' => array_column($semesters, 'semester')
        ]);
    }
    
    /**
     * 获取课程成绩详情
     */
    public function courseDetail(array $params): void
    {
        $studentId = (int) $params['_user']['id'];
        $courseId = (int) $params['courseId'];
        $semester = $_GET['semester'] ?? null;
        
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            Response::error('学生不存在', 404);
            return;
        }
        
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            Response::error('课程不存在', 404);
            return;
        }
        
        $where = "g.student_id = ? AND g.course_id = ?";
        $queryParams = [$studentId, $courseId];
        
        if ($semester) {
            $where .= " AND g.semester = ?";
            $queryParams[] = $semester;
        }
        
        $sql = "SELECT 
                    g.*,
                    c.code as course_code,
                    c.name as course_name,
                    c.credits,
                    c.course_type
                FROM grades g
                JOIN courses c ON g.course_id = c.id
                WHERE {$where}
                ORDER BY g.semester DESC";
        $grades = $this->gradeModel->query($sql, $queryParams);
        
        if (empty($grades)) {
            Response::error('暂无该课程成绩', 404);
            return;
        }
        
        foreach ($grades as &$grade) {
            $courseRank = $this->getCourseRank($studentId, $courseId, $grade['semester']);
            $grade['classRank'] = $courseRank['classRank'];
            $grade['classTotal'] = $courseRank['classTotal'];
            $grade['gradeRank'] = $courseRank['gradeRank'];
            $grade['gradeTotal'] = $courseRank['gradeTotal'];
            
            $sql = "SELECT 
                        AVG(score) as avg_score,
                        MAX(score) as max_score,
                        MIN(score) as min_score
                    FROM grades
                    WHERE course_id = ? AND semester = ?";
            $stats = $this->gradeModel->query($sql, [$courseId, $grade['semester']]);
            $grade['courseAvg'] = round($stats[0]['avg_score'] ?? 0, 2);
            $grade['courseMax'] = $stats[0]['max_score'] ?? 0;
            $grade['courseMin'] = $stats[0]['min_score'] ?? 0;
        }
        
        Response::success([
            'course' => $course,
            'grades' => $grades
        ]);
    }
    
    /**
     * 获取学生成绩趋势
     */
    public function trend(array $params): void
    {
        $studentId = (int) $params['_user']['id'];
        
        $sql = "SELECT 
                    semester,
                    AVG(score) as avg_score,
                    COUNT(*) as course_count,
                    SUM(CASE WHEN score >= 90 THEN 1 ELSE 0 END) as excellent_count,
                    SUM(CASE WHEN score >= 60 THEN 1 ELSE 0 END) as pass_count
                FROM grades
                WHERE student_id = ?
                GROUP BY semester
                ORDER BY semester";
        $trend = $this->gradeModel->query($sql, [$studentId]);
        
        foreach ($trend as &$item) {
            $item['excellentRate'] = $item['course_count'] > 0 
                ? round(($item['excellent_count'] / $item['course_count']) * 100, 2) 
                : 0;
            $item['passRate'] = $item['course_count'] > 0 
                ? round(($item['pass_count'] / $item['course_count']) * 100, 2) 
                : 0;
        }
        
        Response::success([
            'trend' => $trend
        ]);
    }
    
    /**
     * 获取课程排名
     */
    private function getCourseRank(int $studentId, int $courseId, string $semester): array
    {
        $result = [
            'classRank' => null,
            'classTotal' => 0,
            'gradeRank' => null,
            'gradeTotal' => 0
        ];
        
        $student = $this->studentModel->find($studentId);
        if (!$student || !$student['class_id']) {
            return $result;
        }
        
        $class = $this->classModel->find($student['class_id']);
        
        $sql = "SELECT s.id as student_id, g.score
                FROM grades g
                JOIN students s ON g.student_id = s.id
                WHERE s.class_id = ? AND g.course_id = ? AND g.semester = ?
                ORDER BY g.score DESC";
        $classRankings = $this->gradeModel->query($sql, [$student['class_id'], $courseId, $semester]);
        
        $result['classTotal'] = count($classRankings);
        
        foreach ($classRankings as $index => $rank) {
            if ($rank['student_id'] == $studentId) {
                $result['classRank'] = $index + 1;
                break;
            }
        }
        
        if ($class && $class['grade']) {
            $sql = "SELECT s.id as student_id, g.score
                    FROM grades g
                    JOIN students s ON g.student_id = s.id
                    JOIN classes c ON s.class_id = c.id
                    WHERE c.grade = ? AND g.course_id = ? AND g.semester = ?
                    ORDER BY g.score DESC";
            $gradeRankings = $this->gradeModel->query($sql, [$class['grade'], $courseId, $semester]);
            
            $result['gradeTotal'] = count($gradeRankings);
            
            foreach ($gradeRankings as $index => $rank) {
                if ($rank['student_id'] == $studentId) {
                    $result['gradeRank'] = $index + 1;
                    break;
                }
            }
        }
        
        return $result;
    }
}
