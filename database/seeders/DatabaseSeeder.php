<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Folder;
use App\Models\FileItem;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $docs = Folder::create(['name' => 'Documents', 'parent_id' => null]);
        $pics = Folder::create(['name' => 'Pictures', 'parent_id' => null]);
        $music = Folder::create(['name' => 'Music', 'parent_id' => null]);

        // Second-level
        Folder::create(['name' => 'Work', 'parent_id' => $docs->id]);
        Folder::create(['name' => 'Personal', 'parent_id' => $docs->id]);
        Folder::create(['name' => '2025', 'parent_id' => $pics->id]);
        Folder::create(['name' => 'Rock', 'parent_id' => $music->id]);
        Folder::create(['name' => 'Jazz', 'parent_id' => $music->id]);

        // Sample files
        FileItem::create(['name' => 'resume.docx', 'folder_id' => $docs->id]);
        FileItem::create(['name' => 'notes.txt', 'folder_id' => $docs->id]);

        FileItem::create(['name' => 'holiday.jpg', 'folder_id' => $pics->id]);
        FileItem::create(['name' => 'family.png', 'folder_id' => $pics->id]);

        FileItem::create(['name' => 'classic.mp3', 'folder_id' => $music->id]);
        FileItem::create(['name' => 'rock anthem.mp3', 'folder_id' => $music->id]);
    }
}
