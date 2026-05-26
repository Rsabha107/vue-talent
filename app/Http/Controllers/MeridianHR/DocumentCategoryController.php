<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentCategoryController extends BaseHRController
{
    public function index()
    {
        $categories = DocumentCategory::where('active_flag', 1)
            ->withCount(['documents' => function ($query) {
                $query->where('active_flag', 1);
            }])
            ->orderBy('title')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'description' => $category->description,
                    'documents_count' => $category->documents_count,
                    'created_at' => $category->created_at->format('d M Y'),
                ];
            });
        
        return Inertia::render('MeridianHR/DocumentCategories', array_merge(
            $this->getCommonProps(),
            [
                'categories' => $categories,
            ]
        ));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title,NULL,id,active_flag,1',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
        ], [
            'title.unique' => 'A category with this name already exists',
        ]);

        DocumentCategory::create([
            'title' => $request->title,
            'description' => $request->description,
            'icon' => $request->icon,
        ]);
        
        return back()->with('success', 'Category created successfully');
    }
    
    public function update(Request $request, $id)
    {
        $category = DocumentCategory::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:100|unique:document_categories,title,' . $id . ',id,active_flag,1',
            'description' => 'nullable|string|max:500',
        ], [
            'title.unique' => 'A category with this name already exists',
        ]);
        
        $category->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);
        
        return back()->with('success', 'Category updated successfully');
    }
    
    public function destroy($id)
    {
        $category = DocumentCategory::findOrFail($id);
        
        // Check if category has documents
        $documentsCount = $category->documents()->where('active_flag', 1)->count();
        
        if ($documentsCount > 0) {
            return back()->with('error', 'Cannot delete category with existing documents. Please reassign or delete the documents first.');
        }
        
        // Soft delete
        $category->update(['active_flag' => 0]);
        
        return back()->with('success', 'Category deleted successfully');
    }
}
