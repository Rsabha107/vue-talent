<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\DocumentCategory;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Ems\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $hrRole = $this->getHRRole();
        $isAdmin = $hrRole === 'admin';
        $selectedEventId = $this->getSelectedEventId();
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Note: Event filtering is now handled on frontend via dropdown
        // Backend returns all documents, frontend filters by selected event
        
        // Employees and managers see only their own documents
        // Only admins see all documents
        if (!$isAdmin) {
            $currentEmployee = Employee::where('user_id', $user->id)->first();
            if ($currentEmployee) {
                $query->where('employee_id', $currentEmployee->id);
            }
        }
        
        $documents = $query->latest()->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'employee_id' => $doc->employee_id,
                'employee_name' => $doc->employee ? $doc->employee->full_name : 'N/A',
                'event_id' => $doc->event_id,
                'event_name' => $doc->event ? $doc->event->name : null,
                'category_id' => $doc->category_id,
                'category_name' => $doc->category ? $doc->category->title : 'N/A',
                'file_name' => $doc->file_name,
                'file_size' => $doc->file_size,
                'file_size_human' => $doc->file_size_human,
                'mime_type' => $doc->mime_type,
                'description' => $doc->description,
                'uploaded_by' => $doc->uploadedBy ? $doc->uploadedBy->name : 'N/A',
                'uploaded_at' => $doc->created_at->format('d M Y'),
                'active_flag' => $doc->active_flag,
            ];
        });
        
        $categories = DocumentCategory::where('active_flag', 1)
            ->orderBy('title')
            ->get();
        
        $employees = null;
        $events = null;
        $currentEmployee = null;
        
        if ($isAdmin) {
            // Use same employee list format as leave requests
            $employees = $selectedEventId 
                ? $this->getEventEmployees()->where('archived', 'N')->orderBy('full_name')->get(['id', 'full_name', 'employee_number'])
                : Employee::where('archived', 'N')->orderBy('full_name')->get(['id', 'full_name', 'employee_number']);
            
            $events = Event::where('active_flag', 1)
                ->orderBy('name')
                ->get(['id', 'name']);
        } else {
            // For non-admin users, get their employee record
            $currentEmployee = Employee::where('user_id', $user->id)->first();
            if ($currentEmployee) {
                $currentEmployee = [
                    'id' => $currentEmployee->id,
                    'name' => $currentEmployee->full_name,
                ];
            }
        }
        
        return Inertia::render('MeridianHR/Documents', array_merge(
            $this->getCommonProps('documents'),
            [
                'documents' => $documents,
                'categories' => $categories,
                'employees' => $employees,
                'events' => $events,
                'currentEmployee' => $currentEmployee,
            ]
        ));
    }
    
    public function store(Request $request)
    {
        $hrRole = $this->getHRRole();
        $user = Auth::user();
        
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ], [
            'file.required' => 'Please select a PDF file to upload',
            'file.mimes' => 'Only PDF files are allowed',
            'file.max' => 'File size must not exceed 10MB',
        ]);
        
        // Employees and managers can only upload documents for themselves
        // Only admins can upload documents for other employees
        if ($hrRole !== 'admin') {
            $currentEmployee = Employee::where('user_id', $user->id)->first();
            if (!$currentEmployee || $request->employee_id != $currentEmployee->id) {
                return back()->with('error', 'You can only upload documents for yourself');
            }
        }
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents');
        
        EmployeeDocument::create([
            'employee_id' => $request->employee_id,
            'event_id' => $request->event_id,
            'category_id' => $request->category_id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => Auth::id(),
            'description' => $request->description,
        ]);
        
        return back()->with('success', 'Document uploaded successfully');
    }
    
    public function download($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        // Authorization check
        $this->authorizeDocument($document);
        
        // Check if file exists
        if (!Storage::disk('documents')->exists($document->file_path)) {
            return back()->with('error', 'File not found');
        }
        
        $filePath = Storage::disk('documents')->path($document->file_path);
        return response()->download($filePath, $document->file_name);
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Check if file exists
        if (!Storage::disk('documents')->exists($document->file_path)) {
            abort(404, 'File not found');
        }
        
        // Return inline (opens in browser instead of download)
        $filePath = Storage::disk('documents')->path($document->file_path);
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
        ]);
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Optional: Hard delete the file from storage
        // if (Storage::disk('documents')->exists($document->file_path)) {
        //     Storage::disk('documents')->delete($document->file_path);
        // }
        // $document->delete();
        
        return back()->with('success', 'Document deleted successfully');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $hrRole = $this->getHRRole();
        
        // Only admins can access all documents
        // Employees and managers can only access their own documents
        if ($hrRole !== 'admin') {
            $currentEmployee = Employee::where('user_id', $user->id)->first();
            if (!$currentEmployee || $document->employee_id != $currentEmployee->id) {
                abort(403, 'Unauthorized access to document');
            }
        }
    }
}
