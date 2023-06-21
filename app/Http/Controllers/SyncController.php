<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use GuzzleHttp\Client;


class SyncController extends Controller
{
    public function index()
    {
        $googleDisk = \Storage::disk('google');
        $files = $googleDisk->allFiles();
    }


    public function getDropboxTopFolder()
    {
        // Use the Dropbox API to fetch the top-level folder
        $dropboxClient = new Client([
            'base_uri' => 'https://api.dropboxapi.com/2/',
            'headers' => [
                'Authorization' => 'Bearer sl.BgvBPd1IN2lFlkAA4GNAbl0ORwVvDFHaYPmxAyZzpuCo-yPkjIyFErQHOohFDfBGPLvcnMA4SikWY71uWiDtviMVD9iBPRVeEedlnrWAozuTE2uzegDI762eQ_6AI7HpC1pH9_YZHJtC',
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $dropboxClient->post('files/list_folder', [
            'json' => [
                'path' => '',
                'recursive' => false,
                'include_media_info' => false,
                'include_deleted' => false,
                'include_has_explicit_shared_members' => false,
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        $files = $body['entries'];

        // Find the top-level folder (assuming it's the first folder in the list)
        return $files[0]['path_display'];
    }

    public function syncGoogleFolders()
    {
        // Fetch the top-level folders from Google Drive and Dropbox
        $googleDriveFolder = $this->getGoogleDriveTopFolder();
        $dropboxFolder = $this->getDropboxTopFolder();
        // Sync the folders
        // $this->syncFilesGoogleDrive($googleDriveFolder, $dropboxFolder);
        $this->syncFilesDropBox($dropboxFolder, $googleDriveFolder);
        return redirect()->back();
    }

     public function syncDropBoxFolders()
    {
        // Fetch the top-level folders from Google Drive and Dropbox
        $googleDriveFolder = $this->getGoogleDriveTopFolder();
        $dropboxFolder = $this->getDropboxTopFolder();
        // Sync the folders
        $this->syncFilesGoogleDrive($googleDriveFolder, $dropboxFolder);
        // $this->syncFilesDropBox($dropboxFolder, $googleDriveFolder);
    }

    private function getGoogleDriveTopFolder()
    {
        // Use the Google Drive API to fetch the top-level folder
        $drive = Storage::disk('google');
        $files = $drive->allFiles();

        // Find the top-level folder (assuming it's the first folder in the list)
        return $files[0];
    }


    private function syncFilesGoogleDrive($sourceFolder, $destinationFolder)
    {
        // dd($sourceFolder);
        // Fetch files in the source folder
        $sourceFiles = Storage::disk('google')->allFiles('');
        // dd($sourceFolder, $destinationFolder);

        foreach ($sourceFiles as $sourceFile) {
            // Determine the corresponding file path in the destination folder
            $destinationFile = str_replace($sourceFolder, $destinationFolder, $sourceFile);

            // Check if the file exists in the destination folder
            if (!Storage::disk('dropbox')->exists($destinationFile)) {
                // File doesn't exist in the destination, so upload it
                $fileContents = Storage::disk('google')->get($sourceFile);
                Storage::disk('dropbox')->put($destinationFile, $fileContents);
            }
        }
    }
    private function syncFilesDropBox($sourceFolder, $destinationFolder)
    {
        // Fetch files in the source folder
        $sourceFiles = Storage::disk('dropbox')->allFiles('');
        
        foreach ($sourceFiles as $sourceFile) {
            // Determine the corresponding file path in the destination folder
            $destinationFile = str_replace($sourceFolder, $destinationFolder, $sourceFile);

            // Check if the file exists in the destination folder
            if (!Storage::disk('google')->exists($destinationFile)) {
                // File doesn't exist in the destination, so upload it
                $fileContents = Storage::disk('dropbox')->get($sourceFile);
                Storage::disk('google')->put($destinationFile, $fileContents);
            }
        }
    }
}
