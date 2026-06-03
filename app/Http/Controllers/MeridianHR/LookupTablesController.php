<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Country;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Directorate;
use App\Models\EmployeeContractType;
use App\Models\EmployeeEntity;
use App\Models\EmployeeJobLevel;
use App\Models\EmployeeSponsorship;
use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\Nationality;
use App\Models\Relationship;
use App\Models\SalaryBasis;
use App\Models\Salutation;
use App\Models\AddressType;
use App\Models\PayPeriod;
use App\Models\InvoiceNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LookupTablesController extends BaseHRController
{
    /**
     * Entity configuration
     * Define all lookup tables with their models, fields, and display settings
     */
    private function getEntityConfig()
    {
        return [
            'departments' => [
                'model' => Department::class,
                'title' => 'Departments',
                'singular' => 'Department',
                'icon' => 'briefcase',
                'color' => '#10b981',
                'description' => 'Organizational departments and units',
                'fields' => [
                    ['name' => 'name', 'label' => 'Department Name', 'type' => 'text', 'required' => true],
                    ['name' => 'parent_id', 'label' => 'Parent Department', 'type' => 'select', 'options' => 'departments'],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'parent', 'label' => 'Parent Department'],
                    ['key' => 'status', 'label' => 'Status', 'width' => '120px'],
                ],
                'hasActiveFlag' => true,
                'hasAudit' => true,
            ],
            'designations' => [
                'model' => Designation::class,
                'title' => 'Job Titles',
                'singular' => 'Job Title',
                'icon' => 'award',
                'color' => '#3b82f6',
                'description' => 'Employee job titles and positions',
                'fields' => [
                    ['name' => 'name', 'label' => 'Title Name', 'type' => 'text', 'required' => true],
                    ['name' => 'department_id', 'label' => 'Department', 'type' => 'select', 'options' => 'departments'],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'name', 'label' => 'Title'],
                    ['key' => 'department', 'label' => 'Department'],
                    ['key' => 'status', 'label' => 'Status', 'width' => '120px'],
                ],
                'hasActiveFlag' => true,
                'hasAudit' => true,
            ],
            'contract-types' => [
                'model' => EmployeeContractType::class,
                'title' => 'Contract Types',
                'singular' => 'Contract Type',
                'icon' => 'file-text',
                'color' => '#8b5cf6',
                'description' => 'Employment contract categories',
                'fields' => [
                    ['name' => 'title', 'label' => 'Contract Type', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Contract Type'],
                    ['key' => 'status', 'label' => 'Status', 'width' => '120px'],
                ],
                'hasActiveFlag' => true,
                'hasAudit' => true,
            ],
            'genders' => [
                'model' => Gender::class,
                'title' => 'Genders',
                'singular' => 'Gender',
                'icon' => 'users',
                'color' => '#ec4899',
                'description' => 'Gender options for employee records',
                'fields' => [
                    ['name' => 'title', 'label' => 'Gender', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Gender'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'marital-statuses' => [
                'model' => MaritalStatus::class,
                'title' => 'Marital Statuses',
                'singular' => 'Marital Status',
                'icon' => 'heart',
                'color' => '#f59e0b',
                'description' => 'Marital status options',
                'fields' => [
                    ['name' => 'title', 'label' => 'Marital Status', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Status'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'relationships' => [
                'model' => Relationship::class,
                'title' => 'Relationships',
                'singular' => 'Relationship',
                'icon' => 'link',
                'color' => '#06b6d4',
                'description' => 'Family relationship types for emergency contacts',
                'fields' => [
                    ['name' => 'title', 'label' => 'Relationship Type', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Relationship'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'directorates' => [
                'model' => Directorate::class,
                'title' => 'Directorates',
                'singular' => 'Directorate',
                'icon' => 'building',
                'color' => '#0891b2',
                'description' => 'Organizational directorates',
                'fields' => [
                    ['name' => 'title', 'label' => 'Directorate Name', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Directorate'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'salary-basis' => [
                'model' => SalaryBasis::class,
                'title' => 'Salary Basis',
                'singular' => 'Salary Basis',
                'icon' => 'dollar',
                'color' => '#059669',
                'description' => 'Salary calculation basis (hourly, monthly, etc.)',
                'fields' => [
                    ['name' => 'title', 'label' => 'Basis Type', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Basis'],
                    ['key' => 'status', 'label' => 'Status', 'width' => '120px'],
                ],
                'hasActiveFlag' => true,
                'hasAudit' => true,
            ],
            'countries' => [
                'model' => Country::class,
                'title' => 'Countries',
                'singular' => 'Country',
                'icon' => 'globe',
                'color' => '#6366f1',
                'description' => 'Country list with ISO codes',
                'fields' => [
                    ['name' => 'name', 'label' => 'Country Name', 'type' => 'text', 'required' => true],
                    ['name' => 'iso', 'label' => 'ISO Code', 'type' => 'text', 'required' => true, 'maxlength' => 2],
                    ['name' => 'iso3', 'label' => 'ISO3 Code', 'type' => 'text', 'maxlength' => 3],
                    ['name' => 'phonecode', 'label' => 'Phone Code', 'type' => 'text'],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'name', 'label' => 'Country'],
                    ['key' => 'iso', 'label' => 'ISO', 'width' => '80px'],
                    ['key' => 'phonecode', 'label' => 'Phone Code', 'width' => '120px'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => false,
            ],
            'nationalities' => [
                'model' => Nationality::class,
                'title' => 'Nationalities',
                'singular' => 'Nationality',
                'icon' => 'flag',
                'color' => '#d946ef',
                'description' => 'Nationality options for employees',
                'fields' => [
                    ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'required' => true],
                    ['name' => 'en_short_name', 'label' => 'Short Name', 'type' => 'text'],
                    ['name' => 'alpha_2_code', 'label' => 'Alpha-2 Code', 'type' => 'text', 'maxlength' => 2],
                    ['name' => 'alpha_3_code', 'label' => 'Alpha-3 Code', 'type' => 'text', 'maxlength' => 3],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'nationality', 'label' => 'Nationality'],
                    ['key' => 'alpha_2_code', 'label' => 'Code', 'width' => '80px'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => false,
            ],
            'sponsorships' => [
                'model' => EmployeeSponsorship::class,
                'title' => 'Sponsorships',
                'singular' => 'Sponsorship',
                'icon' => 'shield',
                'color' => '#14b8a6',
                'description' => 'Employee sponsorship types',
                'fields' => [
                    ['name' => 'title', 'label' => 'Sponsorship Type', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Sponsorship'],
                    ['key' => 'status', 'label' => 'Status', 'width' => '120px'],
                ],
                'hasActiveFlag' => true,
                'hasAudit' => true,
            ],
            'entities' => [
                'model' => EmployeeEntity::class,
                'title' => 'Entities',
                'singular' => 'Entity',
                'icon' => 'layers',
                'color' => '#f97316',
                'description' => 'Legal entities or organizational units',
                'fields' => [
                    ['name' => 'title', 'label' => 'Entity Name', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Entity'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'salutations' => [
                'model' => Salutation::class,
                'title' => 'Salutations',
                'singular' => 'Salutation',
                'icon' => 'user',
                'color' => '#a855f7',
                'description' => 'Title prefixes (Mr., Mrs., Dr., etc.)',
                'fields' => [
                    ['name' => 'title', 'label' => 'Salutation', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Salutation'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'address-types' => [
                'model' => AddressType::class,
                'title' => 'Address Types',
                'singular' => 'Address Type',
                'icon' => 'map-pin',
                'color' => '#ef4444',
                'description' => 'Address category types (Home, Work, etc.)',
                'fields' => [
                    ['name' => 'title', 'label' => 'Address Type', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Type'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'pay-periods' => [
                'model' => PayPeriod::class,
                'title' => 'Pay Cycles',
                'singular' => 'Pay Cycle',
                'icon' => 'calendar',
                'color' => '#84cc16',
                'description' => 'Payment frequency cycles (Weekly, Monthly, etc.)',
                'fields' => [
                    ['name' => 'title', 'label' => 'Pay Cycle', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Cycle'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'invoice-notes' => [
                'model' => InvoiceNote::class,
                'title' => 'Invoice Notes',
                'singular' => 'Invoice Note',
                'icon' => 'file-text',
                'color' => '#64748b',
                'description' => 'Standard notes for invoices',
                'fields' => [
                    ['name' => 'title', 'label' => 'Note Title', 'type' => 'text', 'required' => true],
                    ['name' => 'note', 'label' => 'Note Content', 'type' => 'text', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Title'],
                    ['key' => 'note', 'label' => 'Note'],
                ],
                'hasActiveFlag' => false,
                'hasAudit' => true,
            ],
            'job-levels' => [
                'model' => EmployeeJobLevel::class,
                'title' => 'Job Levels',
                'singular' => 'Job Level',
                'icon' => 'trending-up',
                'color' => '#6366f1',
                'description' => 'Employee job level categories (Junior, Senior, etc.)',
                'fields' => [
                    ['name' => 'title', 'label' => 'Job Level', 'type' => 'text', 'required' => true],
                    ['name' => 'active_flag', 'label' => 'Status', 'type' => 'status', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'width' => '80px'],
                    ['key' => 'title', 'label' => 'Level'],
                    ['key' => 'status', 'label' => 'Status', 'width' => '120px'],
                ],
                'hasActiveFlag' => true,
                'hasAudit' => true,
            ],
        ];
    }

    public function index($type)
    {
        $config = $this->getEntityConfig();
        
        if (!isset($config[$type])) {
            abort(404, 'Settings type not found');
        }
        
        $entity = $config[$type];
        $modelClass = $entity['model'];
        
        // Build query
        $query = $modelClass::query();
        
        // Add active flag filter if applicable
        if ($entity['hasActiveFlag']) {
            $query->where('active_flag', 1);
        }
        
        // Load relationships
        if ($type === 'departments') {
            $query->with('parent');
        } elseif ($type === 'designations') {
            $query->with('department');
        }
        
        // Get items
        $items = $query->orderBy('id', 'desc')->get()->map(function ($item) use ($type, $entity) {
            $data = [
                'id' => $item->id,
            ];
            
            // Map columns
            foreach ($entity['columns'] as $column) {
                $key = $column['key'];
                if ($key === 'id') continue;
                
                if ($key === 'status' && isset($item->active_flag)) {
                    $data['status'] = $item->active_flag;
                } elseif ($key === 'parent' && $type === 'departments') {
                    $data['parent'] = $item->parent ? $item->parent->name : null;
                } elseif ($key === 'department' && $type === 'designations') {
                    $data['department'] = $item->department ? $item->department->name : null;
                } elseif (isset($item->$key)) {
                    $data[$key] = $item->$key;
                } else {
                    // Try to get the first fillable field
                    $fillable = $item->getFillable();
                    if (count($fillable) > 0 && in_array($key, $fillable)) {
                        $data[$key] = $item->$key;
                    }
                }
            }
            
            return $data;
        });
        
        // Get dropdown options if needed
        $dropdownOptions = [];
        foreach ($entity['fields'] as $field) {
            if ($field['type'] === 'select' && isset($field['options'])) {
                $optionType = $field['options'];
                if ($optionType === 'departments') {
                    $dropdownOptions['departments'] = Department::where('active_flag', 1)
                        ->orderBy('name')
                        ->get(['id', 'name as label'])
                        ->toArray();
                }
            }
        }
        
        return Inertia::render('MeridianHR/LookupTables', array_merge($this->getCommonProps('setup'), [
            'entityType' => $type,
            'entityConfig' => $entity,
            'items' => $items,
            'dropdownOptions' => $dropdownOptions,
        ]));
    }

    public function store(Request $request, $type)
    {
        $config = $this->getEntityConfig();
        
        if (!isset($config[$type])) {
            abort(404, 'Settings type not found');
        }
        
        $entity = $config[$type];
        $modelClass = $entity['model'];
        
        // Build validation rules
        $rules = [];
        foreach ($entity['fields'] as $field) {
            $fieldRules = [];
            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            if ($field['type'] === 'text') {
                $fieldRules[] = 'string';
                if (isset($field['maxlength'])) {
                    $fieldRules[] = 'max:' . $field['maxlength'];
                } else {
                    $fieldRules[] = 'max:255';
                }
            } elseif ($field['type'] === 'select') {
                $fieldRules[] = 'integer';
            } elseif ($field['type'] === 'status') {
                $fieldRules[] = 'integer';
                $fieldRules[] = 'in:0,1';
            }
            
            $rules[$field['name']] = implode('|', $fieldRules);
        }
        
        $validated = $request->validate($rules);
        
        // Add active flag if applicable and not already set
        if ($entity['hasActiveFlag'] && !isset($validated['active_flag'])) {
            $validated['active_flag'] = 1;
        }
        
        // Add audit fields if applicable
        if ($entity['hasAudit']) {
            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();
        }
        
        $modelClass::create($validated);
        
        return redirect()->route('hr.lookup', $type)->with('success', $entity['singular'] . ' created successfully.');
    }

    public function update(Request $request, $type, $id)
    {
        $config = $this->getEntityConfig();
        
        if (!isset($config[$type])) {
            abort(404, 'Settings type not found');
        }
        
        $entity = $config[$type];
        $modelClass = $entity['model'];
        $item = $modelClass::findOrFail($id);
        
        // Build validation rules
        $rules = [];
        foreach ($entity['fields'] as $field) {
            $fieldRules = [];
            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            if ($field['type'] === 'text') {
                $fieldRules[] = 'string';
                if (isset($field['maxlength'])) {
                    $fieldRules[] = 'max:' . $field['maxlength'];
                } else {
                    $fieldRules[] = 'max:255';
                }
            } elseif ($field['type'] === 'select') {
                $fieldRules[] = 'integer';
            } elseif ($field['type'] === 'status') {
                $fieldRules[] = 'integer';
                $fieldRules[] = 'in:0,1';
            }
            
            $rules[$field['name']] = implode('|', $fieldRules);
        }
        
        $validated = $request->validate($rules);
        
        // Add audit fields if applicable
        if ($entity['hasAudit']) {
            $validated['updated_by'] = Auth::id();
        }
        
        $item->update($validated);
        
        return redirect()->route('hr.lookup', $type)->with('success', $entity['singular'] . ' updated successfully.');
    }

    public function destroy($type, $id)
    {
        $config = $this->getEntityConfig();
        
        if (!isset($config[$type])) {
            abort(404, 'Settings type not found');
        }
        
        $entity = $config[$type];
        $modelClass = $entity['model'];
        $item = $modelClass::findOrFail($id);
        
        // Soft delete via active_flag if applicable
        if ($entity['hasActiveFlag']) {
            $item->update([
                'active_flag' => 0,
                'updated_by' => Auth::id(),
            ]);
        } else {
            // Hard delete for tables without active_flag
            $item->delete();
        }
        
        return redirect()->route('hr.lookup', $type)->with('success', $entity['singular'] . ' deleted successfully.');
    }

    /**
     * Get counts for all lookup tables for the setup dashboard
     */
    public function getCounts()
    {
        $config = $this->getEntityConfig();
        $counts = [];
        
        foreach ($config as $key => $entity) {
            try {
                $modelClass = $entity['model'];
                $query = $modelClass::query();
                
                if ($entity['hasActiveFlag']) {
                    $query->where('active_flag', 1);
                }
                
                $counts[$key] = $query->count();
            } catch (\Exception $e) {
                // If table doesn't exist or query fails, default to 0
                $counts[$key] = 0;
            }
        }
        
        return $counts;
    }
}
