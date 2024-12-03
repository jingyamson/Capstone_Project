<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Section;
use App\Models\Student;
use App\Models\ClassCard;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SubjectsImport; // Import the SubjectsImport class
use Illuminate\Support\Facades\Auth; // Correct import for Auth facade
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function index()
    {
        $userId = Auth::id();  // Get the authenticated user's ID
        $query = "SELECT user_subject.id, user_subject.subject_id, user_subject.user_id, subjects.course_code, subjects.name FROM user_subject 
                JOIN subjects ON user_subject.subject_id = subjects.id 
                WHERE user_subject.user_id = ?";
        $subjects = Subject::all();
        
        // $sections = Section::where('user_id', Auth::id())->get();
        $sections = Section::get();
        return view('subjects.index', compact('subjects', 'sections'));
    }
    

    public function addSubject()
    {
        $subjects = Subject::all();  // Get all subjects
        return view('subjects.addsubject', compact('subjects'));  // Pass subjects to the view
    }

    public function store(Request $request)
    {
        // Validate inputs
        $request->validate([
            'course_code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
        ]);

        // Check if the course_code already exists pa access
        $courseExists = Subject::where('course_code', $request->course_code)->exists();

        // Check if the name already exists
        $nameExists = Subject::where('name', $request->name)->exists();

        // If either course_code or name already exists, return an error
        if ($courseExists) {
            return redirect()->route('subjects.addsubject')->with('error', 'Subject with this course code already exists.');
        }

        if ($nameExists) {
            return redirect()->route('subjects.addsubject')->with('error', 'Subject with this name already exists.');
        }

        // Create the new subject
        Subject::create([
            'course_code' => $request->course_code,
            'name' => $request->name,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('subjects.addsubject')->with('success', 'Subject added successfully!');
    }

    public function create()
    {
        return view('subjects.create');
    }

    public function selectSubjects(Request $request)
    {
        $request->validate([
            'selected_subjects' => 'required|array',  // Ensure subjects are selected
            'selected_subjects.*' => 'exists:subjects,id',  // Validate each subject exists
        ]);

        $selectedSubjects = $request->input('selected_subjects');

        return redirect()->route('subjects.choose')->with('success', 'Subjects selected successfully.');
    }

    public function import(Request $request)
    {
        if ($request->hasFile('subject_file')) {
            try {
                Excel::import(new SubjectsImport, $request->file('subject_file'));
                return redirect()->route('subjects.index')->with('success', 'Subjects imported successfully from file.');
            } catch (\Exception $e) {
                return redirect()->route('subjects.index')->with('error', 'An error occurred during file import: ' . $e->getMessage());
            }
        }

        return redirect()->route('subjects.index')->with('error', 'Please upload a valid file.');
    }

    public function edit(Subject $subject)
    {
        return view('subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'course_code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($subject->user_id !== Auth::id()) {
            return redirect()->route('subjects.index')->with('error', 'You are not authorized to update this subject.');
        }

        $subjectExists = Subject::where('course_code', $request->course_code)
            ->where('name', $request->name)
            ->exists();

        if ($subjectExists) {
            return redirect()->back()->with('error', 'Subject already exists.');
        }

        $subject->update([
            'course_code' => $request->course_code,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('subjects.index')->with('success', 'Subject updated successfully.');
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return redirect()->route('subjects.addsubject')->with('success', 'Subject deleted successfully!');
    }

    public function destroySelected($id)
    {
        DB::table('user_subject')->where('id', $id)->delete();

        return redirect()->route('subjects.index')->with('success', 'Selected Subject deleted successfully!');
    }

    public function chooseSubjects()
    {
        $subjects = Subject::all();

        $sections = Section::all();
        return view('subjects.choose', compact('subjects', 'sections'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $subjectExists = Subject::where('course_code', $request->course_code)
            ->where('name', $request->name)
            ->exists();

        if ($subjectExists) {
            return redirect()->back()->with('error', 'Subject already exists.');
        }

        Subject::create([
            'course_code' => $request->course_code,
            'name' => $request->name,
            'description' => $request->description,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('subjects.choose')->with('success', 'Subject added successfully!');
    }

    public function addSelected(Request $request)
    {
        $request->validate([
            'subjects' => 'required|array|min:1',
            'subjects.*' => 'exists:subjects,id',
        ]);

        $userId = Auth::id();
        $selectedSubjects = $request->input('subjects');

        $existingSubjects = DB::table('user_subject')
            ->whereIn('subject_id', $selectedSubjects)
            ->where('user_id', $userId)
            ->pluck('subject_id')
            ->toArray();

        if (count($existingSubjects) > 0) {
            return redirect()->back()->with('error', 'Some of the selected subjects are already added.');
        }

        $insertData = [];
        foreach ($selectedSubjects as $subjectId) {
            $insertData[] = [
                'user_id' => $userId,
                'subject_id' => $subjectId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('user_subject')->insert($insertData);

        return redirect()->route('subjects.index')->with('success', 'Selected subjects added successfully!');
    }

    public function enrollStudents(Request $request)
    {
        // Validation for subject_id and section_id
        $request->validate([
            'subject_id' => 'required|integer',
            'section_id' => 'required|integer',
        ]);

        // Debugging the request inputs (Uncomment to test during development)
        // dd($request->all());

        $students = Student::where('section_id', $request->section_id)->get();

        // Debug to check retrieved students
        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'No student found in the selected section.');
        }

        // Debugging the student list
        dd($students);

        foreach ($students as $student) {
            $enrollmentExists = ClassCard::where('student_id', $student->id)
                ->where('subject_id', $request->subject_id)
                ->exists();

            // Debugging the enrollment check
            if ($enrollmentExists) {
                continue; // Skip if already enrolled
            }

            ClassCard::create([
                'student_id' => $student->id,
                'user_id' => Auth::id(),
                'subject_id' => (int) $request->subject_id,
                'section_id' => (int) $request->section_id,
                
            ]);
            
        }

        // Debugging the latest enrollment
        $latestEnrollment = ClassCard::latest()->first();
        // Uncomment below for debugging:
        // dd($latestEnrollment);

        return redirect()->route('subjects.index')->with('success', 'All students from the selected section enrolled successfully.');
    }
}