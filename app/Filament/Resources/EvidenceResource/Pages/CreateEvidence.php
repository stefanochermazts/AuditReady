<?php

namespace App\Filament\Resources\EvidenceResource\Pages;

use App\Filament\Resources\EvidenceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateEvidence extends CreateRecord
{
    protected static string $resource = EvidenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploader_id'] = auth()->id();
        $data['version'] = 1;

        // Extract file information from uploaded file
        if (isset($data['stored_path']) && $data['stored_path']) {
            // Get the file path from Filament FileUpload
            $filePath = is_array($data['stored_path']) ? $data['stored_path'][0] : $data['stored_path'];
            
            // Get original filename if stored
            $originalFilename = $data['original_filename'] ?? null;
            if (is_array($originalFilename)) {
                $originalFilename = $originalFilename[0] ?? null;
            }
            
            // Get the file from storage
            $disk = Storage::disk('local');
            
            // Try to get file info using Storage methods (more reliable with tenancy)
            try {
                if ($disk->exists($filePath)) {
                    $data['filename'] = $originalFilename ?: basename($filePath);
                    
                    // Try to get mime type and size using Storage methods
                    try {
                        $data['mime_type'] = $disk->mimeType($filePath) ?: 'application/octet-stream';
                    } catch (\Exception $e) {
                        // Fallback: try to determine from filename
                        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                        $data['mime_type'] = match(strtolower($extension)) {
                            'pdf' => 'application/pdf',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'doc' => 'application/msword',
                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            default => 'application/octet-stream',
                        };
                    }
                    
                    try {
                        $data['size'] = $disk->size($filePath);
                    } catch (\Exception $e) {
                        $data['size'] = 0;
                    }
                    
                    $data['stored_path'] = $filePath;
                } else {
                    // Fallback: extract from path and original filename
                    $data['filename'] = $originalFilename ?: basename($filePath);
                    $data['mime_type'] = 'application/octet-stream';
                    $data['size'] = 0;
                    $data['stored_path'] = $filePath;
                }
            } catch (\Exception $e) {
                // If all else fails, use minimal defaults
                $data['filename'] = $originalFilename ?: basename($filePath);
                $data['mime_type'] = 'application/octet-stream';
                $data['size'] = 0;
                $data['stored_path'] = $filePath;
            }
            
            // Set default values for encryption fields (will be populated by EvidenceService later)
            $data['checksum'] = '';
            $data['encrypted_key'] = '';
            $data['iv'] = '';
            
            // Remove original_filename from data as it's not a database field
            unset($data['original_filename']);
        } elseif (isset($data['stored_path']) && empty($data['stored_path'])) {
            // If stored_path is empty, remove it to avoid validation errors
            unset($data['stored_path']);
        }

        return $data;
    }
}
