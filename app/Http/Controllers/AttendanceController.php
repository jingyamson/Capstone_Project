<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassCard;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{

public function index(Request $request)
{
    // Fetch the subject ID from the request
    $subjectId = $request->get('subject_id');
    
    // Fetch the section ID from the request
    $sectionId = $request->get('section_id');
    
    // Fetch the student ID from the request
    $studentId = $request->get('student_id');
    
    // Retrieve the subject name for the title
    $subject = Subject::find($subjectId);
    $subjectName = $subject ? $subject->name : 'Unknown Subject';

    // Fetch the section data, if a section is selected
    $sections = Section::all();
    
    // Fetch the enrolled students for the subject
    $enrolledStudents = ClassCard::where('subject_id', $subjectId)->with('student')->get();
    
    // If a section is selected, filter the students by section
    if ($sectionId) {
        $enrolledStudents = $enrolledStudents->filter(function ($enrollment) use ($sectionId) {
            return $enrollment->student->section_id == $sectionId;
        });
    }

    // If a student is selected, get the specific student
    $student = null;
    $message = null;
    if ($studentId) {
        $student = Student::find($studentId);
        if (!$student) {
            $message = "No student found";
        }
    } elseif ($enrolledStudents->isNotEmpty()) {
        // If no student is selected, choose the first enrolled student
        $student = $enrolledStudents->first()->student;
    }

    // Fetch attendance records for the lecture and laboratory
    $attendanceRecords = Attendance::where('subject_id', $subjectId)
        ->where('type', 1) // 1 for lecture
        ->where('student_id', $student ? $student->id : null)
        ->get();
    
    $labAttendanceRecords = Attendance::where('subject_id', $subjectId)
        ->where('type', 2) // 2 for laboratory
        ->where('student_id', $student ? $student->id : null)
        ->get();

    // Get the previous and next student IDs for navigation
    $prevStudentId = $enrolledStudents->where('student_id', '<', $studentId)->max('student_id');
    $nextStudentId = $enrolledStudents->where('student_id', '>', $studentId)->min('student_id');

    // Return the view with the necessary data
    return view('attendance.index', compact(
        'subjectId',
        'subjectName',
        'sections',
        'enrolledStudents',
        'student',
        'attendanceRecords',
        'labAttendanceRecords',
        'message',
        'prevStudentId',
        'nextStudentId'
    ));
}


    public function showAttendance(Request $request)
    {
        // Retrieve the student details from the database
        $student = Student::find($request->student_id);

        // Additional logic here (e.g., getting attendance data)

        return view('attendance.index', compact('student'));
    }

    public function updateAttendancePeriod(Request $request)
    {
        // Retrieve selected period from the request
        $selectedPeriod = $request->input('periodic');

        // Fetch attendance records based on the selected period
        $attendanceRecords = Attendance::where('period', $selectedPeriod)->get();

        // Return data as JSON for front-end processing
        return response()->json([
            'attendanceRecords' => $attendanceRecords,
            'message' => 'Attendance records updated successfully.'
        ]);
    }

    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'student_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'section_id' => 'required|integer',
            'day' => 'required|integer',
            'attendance_date' => 'required|integer', // Ensure this matches the request
            'type' => 'required|integer',
            'status' => 'required|integer',
        ]);

        // Create or update attendance logic
        try {
            Attendance::updateOrCreate(
                [
                    'student_id' => $request->student_id,
                    'subject_id' => $request->subject_id,
                    'section_id' => $request->section_id,
                    'day' => $request->day,
                    'attendance_date' => $request->attendance_date,
                    'type' => $request->type,
                ],
                [
                    'status' => $request->status,
                ]
            );

            return response()->json(['success' => 'Attendance recorded successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            // Find and delete the attendance record based on the provided criteria
            Attendance::where('student_id', $request->student_id)
                ->where('subject_id', $request->subject_id)
                ->where('section_id', $request->section_id)
                ->where('day', $request->day)
                ->where('attendance_date', $request->attendance_date)
                ->where('type', $request->type)
                ->delete();

            return response()->json(['success' => 'Attendance deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAttendanceApi(Request $request)
    {
        // Fetch all attendance records (this may not be ideal for large datasets)
        $attendanceRecords = Attendance::all();

        // Return the attendance records in a JSON response
        return response()->json([
            'success' => true,
            'attendances' => $attendanceRecords
        ]);
    }

    public function getAttendanceDetailsApi($id)
    {
        $attendance = Attendance::where('id', $id)->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance not found or you are not authorized to view it.',
            ]);
        }

        return response()->json([
            'success' => true,
            'attendance' => $attendance,
        ]);
    }

    public function storeAttendanceApi(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'student_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'section_id' => 'required|integer',
            'day' => 'required|integer',
            'attendance_date' => 'required|integer', // Ensure this matches the request
            'type' => 'required|integer',
            'status' => 'required|integer',
        ]);

        $attendanceExists = Attendance::where('student_id', $request->student_id)
            ->where('subject_id', $request->subject_id)
            ->where('section_id', $request->section_id)
            ->where('day', $request->day)
            ->where('attendance_date', $request->attendance_date)
            ->where('type', $request->type)
            ->exists();

        if ($attendanceExists) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already exists.'
            ]);
        }

        // Create the student
        $attendance = Attendance::create([
            'student_id' => $request->student_id,
            'subject_id' => $request->subject_id,
            'section_id' => $request->section_id,
            'day' => $request->day,
            'attendance_date' => $request->attendance_date,
            'type' => $request->type,
            'status' => $request->status
        ]);

        // Return a success response
        return response()->json([
            'success' => true,
            'attendance' => $attendance, // Include the newly created student in the response
        ], 201); // 201 Created
    }

    public function updateAttendanceDetailsApi(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'status' => 'required|integer',
        ]);

        // Find the subject by ID
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance not found',
            ]);
        }

        // Update the subject fields
        $attendance->status = $validatedData['status'];
        $attendance->save();

        // Return a success response
        return response()->json([
            'success' => true,
            'attendance' => $attendance,
        ]);

    }
    public function getStudents($sectionId, $subjectId)
    {
        // Fetch students based on section and subject
        $students = Student::whereHas('sections', function ($query) use ($sectionId) {
            $query->where('id', $sectionId);
        })
        ->whereHas('subjects', function ($query) use ($subjectId) {
            $query->where('id', $subjectId);
        })
        ->get();
    
        // Return students as JSON
        return response()->json($students);
    }
}
