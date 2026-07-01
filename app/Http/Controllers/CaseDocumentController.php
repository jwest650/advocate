<?php

namespace App\Http\Controllers;

use App\Models\CaseDocument;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CaseDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = CaseDocument::query()
            ->with(['creator', 'case', 'documentType'])
            ->where('created_by', createdBy());

        // Filter by case_id if provided
        if ($request->has('case_id') && !empty($request->case_id)) {
            $query->where('case_id', $request->case_id);
        }

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('document_name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('document_id', 'like', '%' . $request->search . '%');
            });
        }

        // Handle document type filter
        if ($request->has('document_type') && !empty($request->document_type) && $request->document_type !== 'all') {
            $query->where('document_type_id', $request->document_type);
        }

        // Handle confidentiality filter
        if ($request->has('confidentiality') && !empty($request->confidentiality) && $request->confidentiality !== 'all') {
            $query->where('confidentiality', $request->confidentiality);
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle sorting
        if ($request->has('sort_field') && !empty($request->sort_field)) {
            $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $caseDocuments = $query->paginate($request->per_page ?? 10);
        $documentTypes = DocumentType::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name', 'color']);

        return Inertia::render('advocate/case-documents/index', [
            'caseDocuments' => $caseDocuments,
            'documentTypes' => $documentTypes,
            'filters' => $request->all(['search', 'document_type', 'confidentiality', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {


        $validated = $request->validate([
            'document_name' => 'required|string|max:255',
            'file' => 'required|string',
            'document_type_id' => 'required|exists:document_types,id',
            'description' => 'nullable|string',
            'confidentiality' => 'required|in:public,confidential,privileged',
            'document_date' => 'nullable|date',
            'case_id' => 'nullable|exists:cases,id',
            'status' => 'nullable|in:active,archived',
        ]);

  if (!empty($validated['file'])) {
            $validated['file'] = $this->convertToRelativePath($validated['file']);
        }
        // Extract filename from URL
        // $fileUrl = $request->file;
        // $fileName = basename(parse_url($fileUrl, PHP_URL_PATH)) ?: 'document_' . time();

        CaseDocument::create([
            'document_name' => $validated['document_name'],
            'document_type_id' => $validated['document_type_id'],
            'description' => $validated['description'],
            'confidentiality' => $validated['confidentiality'],
            'document_date' => $validated['document_date'],
            'case_id' => $validated['case_id'],
            'status' => $validated['status'] ?? 'active',
            'file_path' => $validated['file'],
            'created_by' => createdBy(),
        ]);

        return redirect()->back()->with('success', 'Case document created successfully.');
    }
     private function convertToRelativePath(string $url): string
    {
        if (!$url) return $url;

        // If it's already a relative path, return as is
        if (!str_starts_with($url, 'http')) {
            return $url;
        }

        // Extract the path after /storage/
        $storageIndex = strpos($url, '/storage/');
        if ($storageIndex !== false) {
            return substr($url, $storageIndex);
        }

        return $url;
    }

    public function update(Request $request, $documentId)
    {
        $document = CaseDocument::where('id', $documentId)
            ->where('created_by', createdBy())
            ->first();

        if ($document) {
            try {
                $validated = $request->validate([
                    'document_name' => 'required|string|max:255',
                    'file' => 'nullable|string',
                    'document_type_id' => 'required|exists:document_types,id',
                    'description' => 'nullable|string',
                    'confidentiality' => 'required|in:public,confidential,privileged',
                    'document_date' => 'nullable|date',
                    'case_id' => 'nullable|exists:cases,id',
                    'status' => 'nullable|in:active,archived',
                ]);

                // Handle file replacement from media library
                if (!empty($validated['file'])) {
                    $validated['file_path'] = $this->convertToRelativePath($validated['file']);
                    unset($validated['file']);
                }

                $document->update($validated);

                return redirect()->back()->with('success', 'Case document updated successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: 'Failed to update case document');
            }
        } else {
            return redirect()->back()->with('error', 'Case document not found.');
        }
    }

    public function destroy($documentId)
    {
        $document = CaseDocument::where('id', $documentId)
            ->where('created_by', createdBy())
            ->first();

        if ($document) {
            try {
                // File deletion is handled by media library, no need to manually delete

                $document->delete();
                return redirect()->back()->with('success', 'Case document deleted successfully');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: 'Failed to delete case document');
            }
        } else {
            return redirect()->back()->with('error', 'Case document not found.');
        }
    }
    public function download($documentId)
    {
        $document = CaseDocument::whereHas('case', function ($q) {
                $q->where('created_by', createdBy());
            })
            ->where('id', $documentId)
            ->first();

        if (!$document || !$document->file_path) {
            return redirect()->back()->with('error', 'Document not found.');
        }

        $originalPath = $document->file_path;
        $originalFilename = basename($originalPath);

        // Handle full URLs (like DemoMedia files)
        if (str_starts_with($originalPath, 'http')) {
            $parsedUrl = parse_url($originalPath);
            if (isset($parsedUrl['path'])) {
                $publicPath = public_path(ltrim($parsedUrl['path'], '/'));
                if (file_exists($publicPath)) {
                    return response()->download($publicPath, $originalFilename);
                }
            }
        }

        // Handle /storage/ paths (Laravel storage)
        if (str_starts_with($originalPath, '/storage/')) {
            $storagePath = str_replace('/storage/', '', $originalPath);
            if (Storage::disk('public')->exists($storagePath)) {
                return response()->download(storage_path('app/public/' . $storagePath), $originalFilename);
            }
        }

        // Try as direct storage path
        if (Storage::disk('public')->exists($originalPath)) {
            return response()->download(storage_path('app/public/' . $originalPath), $originalFilename);
        }

        return redirect()->back()->with('error', 'Document not found.');
    }
}
