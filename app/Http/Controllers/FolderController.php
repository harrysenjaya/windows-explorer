<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\FileItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{
    public function index()
    {
        return view('explorer');
    }

    // Create a folder
    public function store(): JsonResponse
    {
        $parentId = request()->input('parent_id');

        $payload = request()->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('folders', 'name')
                    ->where(fn($q) => $q->where('parent_id', $parentId))
            ],
            'parent_id' => 'nullable|integer|exists:folders,id',
        ]);

        $folder = Folder::create([
            'name' => $payload['name'],
            'parent_id' => $parentId,
        ]);

        return response()->json($folder, 201);
    }

    // Update a folder
    public function update(int $id): JsonResponse
    {
        $payload = request()->validate([
            'name' => 'required|string|max:255',
        ]);

        $updated = DB::transaction(function () use ($id, $payload) {

            $folder = Folder::where('id', $id)->lockForUpdate()->firstOrFail();
            $newName = $payload['name'];

            $exists = Folder::where('parent_id', $folder->parent_id)
                ->where('name', $newName)
                ->where('id', '!=', $folder->id)
                ->exists();
            if ($exists) {
                abort(422, 'The name has already been taken in this parent.');
            }

            $folder->update([
                'name' => $newName,
            ]);
            return $folder;
        });

        return response()->json($updated);
    }

    // Delete a folder and its subfolders/files
    public function destroy(int $id): JsonResponse
    {
        DB::transaction(function () use ($id) {
            $folder = Folder::where('id', $id)->lockForUpdate()->firstOrFail();
            $folder->delete();
        });
        return response()->json(['status' => 'deleted']);
    }

    // Get children of a folder
    public function children(int $id): JsonResponse
    {
        // id 0 = root
        $query = $id === 0
            ? Folder::whereNull('parent_id')
            : Folder::where('parent_id', $id);

        $children = $query
            ->select(['id','name','parent_id'])
            ->withCount(['children as child_count'])
            ->orderBy('name')
            ->get();

        return response()->json($children);
    }

    // Get files in a folder
    public function files(int $id): JsonResponse
    {
        // id 0 = root and no files
        if ($id === 0) {
            return response()->json([]);
        }
        $folder = Folder::findOrFail($id);
        $files = FileItem::where('folder_id', $folder->id)->orderBy('name')->get();
        
        return response()->json($files);
    }
}
