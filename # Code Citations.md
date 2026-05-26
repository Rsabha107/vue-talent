# Code Citations

## License: MIT
https://github.com/eznxxy/hrms/blob/858612b11bbd0110ce79bbdd1f5192a4cbf7fa02/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (
```


## License: unknown
https://github.com/ZulhamAbidin/Skripsi-Edocuments/blob/f6b7c9cba897008c60bf46edf7ca1ab96dd2f315/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role
```


## License: MIT
https://github.com/eznxxy/hrms/blob/858612b11bbd0110ce79bbdd1f5192a4cbf7fa02/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (
```


## License: unknown
https://github.com/ZulhamAbidin/Skripsi-Edocuments/blob/f6b7c9cba897008c60bf46edf7ca1ab96dd2f315/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role
```


## License: MIT
https://github.com/eznxxy/hrms/blob/858612b11bbd0110ce79bbdd1f5192a4cbf7fa02/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role
```


## License: unknown
https://github.com/ZulhamAbidin/Skripsi-Edocuments/blob/f6b7c9cba897008c60bf46edf7ca1ab96dd2f315/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role
```


## License: unknown
https://github.com/geoff-raymann/doc-manager/blob/1d0dc59a565b5a9bb4491f0653cdfbbd070a60d0/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('document-categories', DocumentCategoryController::class);
    });
});
```

## 📋 Summary

**✅ This approach gives you:**
1. **Secure storage** - files in `storage/app/private/`,
```


## License: MIT
https://github.com/eznxxy/hrms/blob/858612b11bbd0110ce79bbdd1f5192a4cbf7fa02/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('document-categories', DocumentCategoryController::class);
    });
});
```

## 📋 Summary

**✅ This approach gives you:**
1. **Secure storage** - files in `storage/app/private/`,
```


## License: unknown
https://github.com/geoff-raymann/doc-manager/blob/1d0dc59a565b5a9bb4491f0653cdfbbd070a60d0/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('document-categories', DocumentCategoryController::class);
    });
});
```

## 📋 Summary

**✅ This approach gives you:**
1. **Secure storage** - files in `storage/app/private/`,
```


## License: unknown
https://github.com/ZulhamAbidin/Skripsi-Edocuments/blob/f6b7c9cba897008c60bf46edf7ca1ab96dd2f315/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('document-categories', DocumentCategoryController::class);
    });
});
```

## 📋 Summary

**✅ This approach gives you:**
1. **Secure storage** - files in `storage/app/private/`,
```


## License: MIT
https://github.com/eznxxy/hrms/blob/858612b11bbd0110ce79bbdd1f5192a4cbf7fa02/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('document-categories', DocumentCategoryController::class);
    });
});
```

## 📋 Summary

**✅ This approach gives you:**
1. **Secure storage** - files in `storage/app/private/`,
```


## License: unknown
https://github.com/geoff-raymann/doc-manager/blob/1d0dc59a565b5a9bb4491f0653cdfbbd070a60d0/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('document-categories', DocumentCategoryController::class);
    });
});
```

## 📋 Summary

**✅ This approach gives you:**
1. **Secure storage** - files in `storage/app/private/`,
```


## License: unknown
https://github.com/ZulhamAbidin/Skripsi-Edocuments/blob/f6b7c9cba897008c60bf46edf7ca1ab96dd2f315/routes/web.php

```
I'll outline the **best practice approach** for implementing a secure, scalable document management system in your Laravel/Vue app:

## 🏗️ Recommended Architecture

### 1. **Database Schema**

**Two tables needed:**

**`document_categories` table:**
```php
// Migration
Schema::create('document_categories', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});

// Seed with defaults: Contracts, Identity Documents, Bank Documents, Certificates, etc.
```

**`employee_documents` table:**
```php
Schema::create('employee_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees_all');
    $table->foreignId('event_id')->nullable()->constrained('events');
    $table->foreignId('category_id')->constrained('document_categories');
    $table->string('file_name');          // Original filename
    $table->string('file_path');          // Storage path
    $table->integer('file_size');         // Bytes
    $table->string('mime_type');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->text('description')->nullable();
    $table->tinyInteger('active_flag')->default(1);
    $table->timestamps();
});
```

### 2. **Private Storage Configuration**

**✅ Best Practice: Store files OUTSIDE public directory**

**In `config/filesystems.php`:**
```php
'disks' => [
    // ...existing disks
    
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/private/documents'),
        'visibility' => 'private',
    ],
],
```

**Why this approach?**
- ✅ Files are **NOT publicly accessible** via URL
- ✅ Forces all access through **authenticated controller** (authorization checks)
- ✅ Can log all downloads/views for audit trail
- ✅ Easy to add virus scanning, watermarking, etc.

### 3. **Controllers**

**DocumentController.php:**
```php
namespace App\Http\Controllers\MeridianHR;

class DocumentController extends BaseHRController
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        $query = EmployeeDocument::with(['employee', 'category', 'uploadedBy', 'event'])
            ->where('active_flag', 1);
        
        // Employees only see their own documents
        if (!$isAdmin) {
            $query->where('employee_id', $user->employee_id);
        }
        
        $documents = $query->latest()->get();
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/Documents', [
            'documents' => $documents,
            'categories' => $categories,
            // Pass employees/events for admin
            'employees' => $isAdmin ? Employee::active()->get() : null,
            'events' => $isAdmin ? Event::active()->get() : null,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'employee_id' => 'required|exists:employees_all,id',
            'category_id' => 'required|exists:document_categories,id',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:500',
        ]);
        
        $file = $request->file('file');
        
        // Store with unique name to prevent collisions
        $path = $file->store('', 'documents'); // Stores in storage/app/private/documents
        
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
        
        // Log download (optional, for audit)
        // DocumentDownloadLog::create([...]);
        
        return Storage::disk('documents')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    public function view($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Return inline (opens in browser instead of download)
        return Storage::disk('documents')->response(
            $document->file_path,
            $document->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
    
    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        
        $this->authorizeDocument($document);
        
        // Soft delete by setting active_flag
        $document->update(['active_flag' => 0]);
        
        // Or hard delete the file:
        // Storage::disk('documents')->delete($document->file_path);
        // $document->delete();
        
        return back()->with('success', 'Document deleted');
    }
    
    private function authorizeDocument($document)
    {
        $user = Auth::user();
        $isAdmin = in_array($this->getHRRole(), ['admin', 'manager']);
        
        // Admin/Manager can access all, employees only their own
        if (!$isAdmin && $document->employee_id != $user->employee_id) {
            abort(403, 'Unauthorized access to document');
        }
    }
}
```

**DocumentCategoryController.php** (admin only):
```php
class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        // Admin/manager only
        $categories = DocumentCategory::where('active_flag', 1)->get();
        
        return Inertia::render('MeridianHR/DocumentCategories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title',
            'description' => 'nullable|string|max:500',
        ]);
        
        DocumentCategory::create($request->only(['title', 'description']));
        
        return back()->with('success', 'Category created');
    }
    
    // update, destroy methods...
}
```

### 4. **Vue Component Structure**

**Documents.vue** should have:
```vue
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  documents: Array,
  categories: Array,
  employees: Array,  // For admin
  events: Array,     // For admin
  hrRole: String,
})

const uploading = ref(false)
const uploadForm = ref({
  file: null,
  employee_id: null,
  category_id: null,
  event_id: null,
  description: '',
})

function handleFileUpload(event) {
  uploadForm.value.file = event.target.files[0]
}

function submitDocument() {
  const formData = new FormData()
  formData.append('file', uploadForm.value.file)
  formData.append('employee_id', uploadForm.value.employee_id)
  formData.append('category_id', uploadForm.value.category_id)
  if (uploadForm.value.event_id) formData.append('event_id', uploadForm.value.event_id)
  if (uploadForm.value.description) formData.append('description', uploadForm.value.description)
  
  uploading.value = true
  
  router.post(route('hr.documents.store'), formData, {
    onSuccess: () => {
      uploadForm.value = { file: null, employee_id: null, category_id: null, event_id: null, description: '' }
    },
    onFinish: () => {
      uploading.value = false
    }
  })
}

function viewDocument(doc) {
  window.open(route('hr.documents.view', doc.id), '_blank')
}

function downloadDocument(doc) {
  window.location.href = route('hr.documents.download', doc.id)
}
</script>
```

### 5. **Routes (web.php)**

```php
Route::prefix('hr')->middleware(['auth', 'otp.verified'])->name('hr.')->group(function () {
    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories (admin only)
    Route::middleware('role:admin|manager')->group(function () {
        Route::resource('document-categories', DocumentCategoryController::class);
    });
});
```

## 📋 Summary

**✅ This approach gives you:**
1. **Secure storage** - files in `storage/app/private/`,
```

